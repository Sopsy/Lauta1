<?php

class posts
{
    public function getPosts(array $ids)
    {
        $posts = [];
        foreach ($ids AS $id) {
            $posts[] = $this->getPost($id);
        }

        return $posts;
    }

    public function getThreads(array $ids)
    {
        global $db;

        foreach ($ids AS &$id) {
            $id = (int)$id;
        }

        $ids = implode(',', $ids);
        $q = $db->q("SELECT * FROM thread WHERE id IN (" . $ids . ") ORDER BY FIELD(id, " . $ids . ")");

        if (!$q or $q->num_rows == 0) {
            return false;
        }

        return $q->fetch_all(MYSQLI_ASSOC);
    }

    public function getThreadListThreads(array $ids)
    {
        global $db;

        foreach ($ids AS &$id) {
            $id = (int)$id;
        }

        $ids = implode(',', $ids);
        $q = $db->q('
                SELECT *, f.id AS fileid, t.id AS id, p.id AS post_id,
                    (SELECT COUNT(*) FROM post_upvote WHERE post_id = p.id) AS upvote_count
                FROM thread t
                LEFT JOIN post p ON p.thread_id = t.id AND p.op_post = 1
                LEFT JOIN post_file pf ON pf.post_id = p.id
                LEFT JOIN file f ON f.id = pf.file_id
                WHERE t.id IN (' . $ids . ')
                ORDER BY FIELD(t.id, ' . $ids . ')');

        if (!$q or $q->num_rows == 0) {
            return false;
        }

        return $q->fetch_all(MYSQLI_ASSOC);
    }

    public function getThread(int $id)
    {
        global $db;

        $q = $db->q("SELECT * FROM thread WHERE id = " . (int)$id . " LIMIT 1");

        if (!$q or $q->num_rows == 0) {
            return false;
        }

        return $q->fetch_assoc();
    }

    public function getThreadWithBoard(int $id)
    {
        global $db;

        $q = $db->q("SELECT thread.*, thread.id AS id, board.id AS board_id, board.name AS board_name, board.url AS board_url
            FROM thread
            LEFT JOIN board ON board.id = thread.board_id
            WHERE thread.id = " . (int)$id . "
            LIMIT 1");

        if (!$q or $q->num_rows == 0) {
            return false;
        }

        return $q->fetch_assoc();
    }

    public function getOpPostId(int $threadId): int
    {
        global $db;

        $q = $db->q("SELECT id FROM post WHERE thread_id = " . (int)$threadId . " AND op_post = 1 LIMIT 1");

        if (!$q or $q->num_rows == 0) {
            return false;
        }

        return $q->fetch_assoc()['id'];
    }

    public function getThreadIdByPostId(int $postId): int
    {
        global $db;

        $q = $db->q("SELECT thread_id FROM post WHERE post.id = " . (int)$postId . " LIMIT 1");

        if (!$q or $q->num_rows == 0) {
            return false;
        }

        return $q->fetch_assoc()['thread_id'];
    }

    public function getBoardIdByPostId(int $postId): int
    {
        global $db;

        $q = $db->q("SELECT thread.board_id
            FROM post
            LEFT JOIN thread ON thread.id = post.thread_id 
            WHERE post.id = " . (int)$postId . "
            LIMIT 1");

        if (!$q or $q->num_rows == 0) {
            return false;
        }

        return $q->fetch_assoc()['board_id'];
    }

    public function getPost(int $id, $doJoins = false)
    {
        global $engine, $db;

        $postId = (int)$id;

        $query = "SELECT p1.id, p1.user_id, p1.thread_id,
            p1.ip, p1.remote_port, p1.country_code, p1.name, p1.public_user_id,
            p1.time, p1.message, p1.edited, p1.admin_post, p1.gold_hide, p1.gold_get, p1.op_post";
        if ($doJoins) {
            $query .= ", t.board_id AS board, url, d.name AS boardname, orig_name,
                c.id AS fileid, extension, duration, has_sound, op.id AS op_post_id";
        }
        $query .= ", UNIX_TIMESTAMP(p1.time) AS `time`";

        if ($doJoins) {
            $query .= ", (SELECT GROUP_CONCAT(post_id) FROM post_reply WHERE post_id_replied = p1.id) AS post_replies";
            $query .= ", (SELECT GROUP_CONCAT(tag_id) FROM post_tag WHERE post_id = p1.id) AS post_tags";
            $query .= ", (SELECT COUNT(*) FROM post_upvote WHERE post_id = p1.id) AS upvote_count";
        }

        $query .= " FROM post p1";
        if ($doJoins) {
            $query .= "
            LEFT JOIN thread t ON t.id = p1.thread_id
            LEFT JOIN `post_file` b ON b.`post_id` = p1.id
            LEFT JOIN `file` c ON b.`file_id` = c.`id`
            LEFT JOIN `board` d ON t.board_id = d.`id`
            LEFT JOIN post op ON op.thread_id = t.id AND op.op_post = 1";
        }

        $query .= " WHERE p1.id = " . $postId . " LIMIT 1";

        $q = $db->q($query);

        if (!$q or $q->num_rows == 0) {
            return false;
        }

        return $q->fetch_assoc();
    }

    public function storeThreadView(int $userId, int $thread)
    {
        global $db, $cache;

        if ($cache->exists($userId . '-' . $thread)) {
            return true;
        }

        $cache->set($userId . '-' . $thread, true, 300);
        $db->q("UPDATE thread SET read_count = read_count+1 WHERE id = " . (int)$thread . " LIMIT 1");

        return true;
    }

    public function scramble($str)
    {
        $str = strip_tags($str);
        $str = preg_replace('/[^a-zA-Z0-9.\-,:;\s]/i', '', $str);
        $str = preg_replace_callback(
            '/[A-ZÅÄÖ]/',
            function () {
                return mb_substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
            },
            $str
        );
        $str = preg_replace_callback(
            '/[a-zåäö]/',
            function () {
                return mb_substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 1);
            },
            $str
        );
        $str = preg_replace_callback(
            '/[0-9]/',
            function () {
                return mb_substr(str_shuffle('0123456789'), 0, 1);
            },
            $str
        );

        return $str;
    }

    public function fileIsProcessing(int $fileId): bool
    {
        global $db;

        $q = $db->q("SELECT file_id FROM file_processing WHERE file_id = " . (int)$fileId . " LIMIT 1");

        return $q->num_rows !== 0;
    }

    public function getThreadPosts($id, $count = false, $start = false)
    {
        global $engine, $db, $user;

        if ($count) {
            $limit = 'DESC LIMIT ' . (int)$count;
        } else {
            $limit = 'ASC';
        }
        $from_id = '';
        if ($start) {
            $from_id = ' AND p1.id < ' . (int)$start;
        }

        $where = '';
        if ($user->hasGoldAccount) {
            $where = ' AND (p1.name IS NULL OR p1.name NOT IN (SELECT name FROM user_name_hide WHERE user_id = ' . (int)$user->id . '))';
        }

        $id = $db->escape($id);
        $q = $db->q(
            "
            SELECT p1.id, p1.user_id, p1.thread_id, p1.ip, p1.remote_port, p1.country_code, p1.name, p1.public_user_id,
                p1.time, p1.message, p1.edited, p1.admin_post, p1.gold_hide, p1.gold_get,
                t.board_id AS board, d.url, d.name AS boardname, b.orig_name, c.id AS fileid, c.extension, c.duration, c.has_sound,
                UNIX_TIMESTAMP(p1.`time`) AS `time`,
                (SELECT GROUP_CONCAT(post_id) FROM post_reply WHERE post_id_replied = p1.id) AS post_replies,
                (SELECT GROUP_CONCAT(tag_id) FROM post_tag WHERE post_id = p1.id) AS post_tags,
                (SELECT COUNT(*) FROM post_upvote WHERE post_id = p1.id) AS upvote_count,
                op.id AS op_post_id
            FROM `post` p1
            LEFT JOIN `post_file` b ON b.`post_id` = p1.`id`
			LEFT JOIN `file` c ON b.`file_id` = c.`id`
			LEFT JOIN thread t ON t.id = p1.thread_id
			LEFT JOIN `board` d ON d.id = t.board_id
            LEFT JOIN post op ON op.thread_id = t.id AND op.op_post = 1
            WHERE p1.op_post = 0 AND p1.`thread_id` = '" . $id . "'" . $from_id . $where . " ORDER BY p1.`id` " . $limit
        );

        if (!$q or $q->num_rows == 0) {
            return false;
        }

        $posts = $q->fetch_all(MYSQLI_ASSOC);

        if ($limit) {
            $posts = array_reverse($posts);
        }

        return $posts;
    }

    public function postExists($id)
    {
        global $db;

        $id = $db->escape($id);
        $q = $db->q("SELECT `id` FROM `post` WHERE `id` = '" . $id . "' LIMIT 1");

        if ($q->num_rows == 0) {
            return false;
        } else {
            return true;
        }
    }

    public function isReported($id)
    {
        global $db, $user;

        $id = $db->escape($id);
        $q = $db->q(
            "SELECT `post_id` FROM `post_report` WHERE `post_id` = " . (int)$id . " AND reported_by = INET6_ATON('" . $db->escape(
                $user->getIp()
            ) . "') AND cleared = 0 LIMIT 1"
        );

        if ($q->num_rows == 0) {
            return false;
        } else {
            return true;
        }
    }

    public function reportPost($id, $reason)
    {
        global $db, $user;

        $q = $db->q("INSERT INTO post_report (post_id, reason, reported_by, reported_by_user)
            VALUES (" . (int)$id . ", '" . $db->escape($reason) . "', INET6_ATON('" . $db->escape($user->getIp()) . "')," . (int)$user->id . ')
            ON DUPLICATE KEY UPDATE cleared = 0, cleared_by = NULL, report_time = NOW(), reason = VALUES(reason)');

        return $q !== false;
    }

    public function deleteThreads($ids, int $reason = 0)
    {
        global $db;

        if (empty($ids)) {
            return true;
        }

        $db->q('DELETE FROM thread WHERE id IN (' . $ids . ')');
        $db->q("UPDATE thread_deleted SET delete_reason = " . (int)$reason . " WHERE id IN (" . $ids . ")");

        return true;
    }

    public function deletePosts($ids)
    {
        global $db;
        $ids = $db->escape($ids);

        if (empty($ids)) {
            return false;
        }

        $c = $db->q("DELETE FROM `post` WHERE `id` IN (" . $ids . ")");

        return $c !== false;
    }

    public function deleteFileFromPosts($ids)
    {
        global $db, $files;

        if (empty($ids)) {
            return false;
        }

        if (is_array($ids)) {
            foreach ($ids as &$id) {
                $id = (int)$id;
            }
        } else {
            $ids = (int)$ids;
        }

        return ($db->q("DELETE FROM `post_file` WHERE `post_id` IN (" . $ids . ")") !== false);
    }

    public function checkFloodPrevention($isReply)
    {
        global $user, $cache;

        if ($user->isMod) {
            return true;
        }

        if (!$cache->exists('flood-' . ($isReply ? 'reply-' : 'thread-') . $user->id)) {
            return true;
        }

        return false;
    }

    public function activateFloodPrevention($isReply, $customTime = false)
    {
        global $engine, $user, $cache;

        if ($isReply) {
            if (!$user->hasGoldAccount) {
                $delay = $engine->cfg->replyDelay;
            } else {
                $delay = $engine->cfg->goldReplyDelay;
            }
        } else {
            if (!$user->hasGoldAccount) {
                $delay = $engine->cfg->threadDelay;
            } else {
                $delay = $engine->cfg->goldThreadDelay;
            }
        }
        if ($customTime) {
            $delay = (int)$customTime;
        }

        $cache->set('flood-' . ($isReply ? 'reply-' : 'thread-') . $user->id, true, $delay);
    }

    public function activateTempFloodPrevention($isReply)
    {
        global $user, $cache;
        $cache->set('flood-' . ($isReply ? 'reply-' : 'thread-') . $user->id, true, 2);
    }

    public function formatMessage($text)
    {
        global $engine;

        $text = trim($text);
        $text = str_replace("\r\n", "\n", $text);
        $text = $this->removeForbiddenUnicode($text);
        $text = preg_replace('/(\n){3,}/', "\n\n", $text);
        $text = $this->removeDisallowedBbCode($text);
        $text = mb_substr($text, 0, $engine->cfg->messageMaxLength);
        $text = trim($text); // Trim again because we might have added spaces

        return $text;
    }

    public function embeddableYoutubeLinks($str)
    {
        if (strpos($str, 'https://') === false && strpos($str, 'http://') === false) {
            return $str;
        }

        return preg_replace(
            '%https?://(?:m\.|www\.)?(?:youtube(?:-nocookie)?\.com/watch\?v=|youtu\.be/)([a-zA-Z\d\-_]+)(?:(?:\?|&amp;)t=([\d]+)s?)?(?:[?a-zA-Z_\-=&0-9;]+)?%ui',
            '<div class="inline-embed"><div class="embed-img" data-e="playYoutube" data-id="$1" data-time="$2"><img src="https://t.ylilauta.org/ytimg/$1.jpg" alt="" loading="lazy"></div><span>$0</span></div>',
            $str
        );
    }

    public function clickableLinks($str)
    {
        if (strpos($str, 'https://') === false && strpos($str, 'http://') === false) {
            return $str;
        }

        return preg_replace(
            '/(^|\s|\n|>)(https?:\/\/[^\s<]+)/i',
            '$1<a href="$2" target="_blank" rel="ugc noopener">$2</a>',
            $str
        );
    }

    public function removeForbiddenUnicode($text)
    {
        // Remove invisible characters and characters that mess up the formatting.
        // Or.. change them into regular spaces.
        $unicode = [
            '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', // Unicode control characters
            '/[\x{0080}-\x{009F}]/u',             // Unicode control characters, disallowed in HTML
            '/[\x{00A0}]/u',                      // 'NO-BREAK SPACE' (U+00A0)
            '/[\x{00AD}]/u',                      // 'SOFT HYPHEN' (U+00AD)
            '/[\x{034F}]/u',                      // 'COMBINING GRAPHEME JOINER' (U+034F)
            '/[\x{115F}]/u',                      // 'HANGUL CHOSEONG FILLER' (U+115F)
            '/[\x{180E}]/u',                      // 'MONGOLIAN VOWEL SEPARATOR' (U+180E)
            '/[\x{2000}-\x{200F}]/u',             // Spaces and LTR + RTL marks (U+2000 - U+200F)
            '/[\x{2028}]/u',                      // 'LINE SEPARATOR' (U+2028)
            '/[\x{2029}]/u',                      // 'PARAGRAPH SEPARATOR' (U+2029)
            '/[\x{202A}-\x{202F}]/u',             // LTR + RTL embedding and overrides (U+202A - U+202F)
            '/[\x{205F}-\x{2064}]/u',             // Invisible math chars (U+205F - U+2064)
            '/[\x{206A}-\x{206F}]/u',             // 'INHIBIT SYMMETRIC SWAPPING' etc. (U+206A - U+206F)
            '/[\x{2800}]/u',                      // 'BRAILLE PATTERN BLANK' (U+2800)
            '/[\x{3000}]/u',                      // 'IDEOGRAPHIC SPACE' (U+3000)
            '/[\x{FE00}-\x{FE0F}]/u',             // Variation selectors (U+FE00 - U+FE0F)
            '/[\x{FEFF}]/u',                      // 'ZERO WIDTH NO-BREAK SPACE' (U+FEFF)
            '/[\x{FFFE}-\x{FFFF}]/u',             // Invalid unicode (U+FFFE - U+FFFF)
            '/[\x{E0000}-\x{E007F}]/u',           // Unicode tags (U+E0000 - U+E007F)
        ];
        $text = preg_replace($unicode, ' ', $text);

        return $text;
    }

    public function removeDisallowedBbCode($str)
    {
        global $user;

        $remove = [];
        if (!$user->hasGoldAccount) {
            $remove = [
                '[shadow]',
                '[/shadow]',
                '[green]',
                '[/green]',
                '[blue]',
                '[/blue]',
                '[red]',
                '[/red]',
                '[pink]',
                '[/pink]',
                '[yellow]',
                '[/yellow]',
                '[black]',
                '[/black]',
                '[white]',
                '[/white]',
                '[brown]',
                '[/brown]',
                '[orange]',
                '[/orange]',
                '[purple]',
                '[/purple]',
                '[gray]',
                '[/gray]',
                '[u]',
                '[/u]',
                '[o]',
                '[/o]',
                '[s]',
                '[/s]',
                '[sup]',
                '[/sup]',
                '[sub]',
                '[/sub]',
                '[big]',
                '[/big]',
                '[small]',
                '[/small]',
            ];
        }

        return str_ireplace($remove, ' ', $str);
    }

    public function printMessage($text, $postId, $opPostId)
    {
        $text = htmlspecialchars($text);
        $text = $this->addQuotes($text, $postId, $opPostId);
        $text = $this->bbcodeFormat($text);
        $text = nl2br($text);
        $text = $this->embeddableYoutubeLinks($text);
        $text = $this->clickableLinks($text);

        echo $text;
    }

    public function addQuotes($str, $postId, $opPostId)
    {
        if (strpos($str, '&gt;') === false && strpos($str, '&lt;') === false) {
            return $str;
        }

        $search = [
            '/(^|[\n\]])(&gt;)(?!&gt;[0-9]+)([^\n]+)/is',
            '/(^|[\n\]])(&lt;)([^\n]+)/is',
            '/(&gt;&gt;)([0-9]+)/is',
        ];
        $replace = [
            '$1<span class="quote">$2$3</span>',
            '$1<span class="bluequote">$2$3</span>',
            '<a href="/scripts/redirect.php?id=$2" class="ref" data-id="$2">$1$2</a>',
        ];

        if ($opPostId !== null) {
            $search[] = '/(&gt;&gt;)(' . $opPostId . ')/is';
            $replace[] = '$1$2 (' . _('OP') . ')';
        }

        return preg_replace($search, $replace, $str);
    }

    public function bbcodeFormat($str)
    {
        if (strpos($str, '[') === false || strpos($str, ']') === false || strpos($str, '/') === false) {
            return $str;
        }

        if (!preg_match('#\[[^\]]+\](.+?)\[/[^\]]+\]#si', $str)) {
            return $str;
        }

        $search = [
            '#\[b\]\s*(.+?)\s*\[/b\]#is',
            '#\[em\]\s*(.+?)\s*\[/em\]#is',
            '#\[u\]\s*(.+?)\s*\[/u\]#is',
            '#\[o\]\s*(.+?)\s*\[/o\]#is',
            '#\[s\]\s*(.+?)\s*\[/s\]#is',
            '#\[spoiler\]\s*(.+?)\s*\[/spoiler\]#is',
            '#\[quote\]\s*(.+?)\s*\[/quote\]#is',
            '#\[code\]\s*(.+?)\s*\[/code\]#is',
            '#\[sup\]\s*(.+?)\s*\[/sup\]#is',
            '#\[sub\]\s*(.+?)\s*\[/sub\]#is',
            '#\[big\]\s*(.+?)\s*\[/big\]#is',
            '#\[small\]\s*(.+?)\s*\[/small\]#is',
            '#\[shadow\]\s*(.+?)\s*\[/shadow\]#is',
            '#\[green\]\s*(.+?)\s*\[/green\]#is',
            '#\[blue\]\s*(.+?)\s*\[/blue\]#is',
            '#\[red\]\s*(.+?)\s*\[/red\]#is',
            '#\[pink\]\s*(.+?)\s*\[/pink\]#is',
            '#\[yellow\]\s*(.+?)\s*\[/yellow\]#is',
            '#\[black\]\s*(.+?)\s*\[/black\]#is',
            '#\[white\]\s*(.+?)\s*\[/white\]#is',
            '#\[brown\]\s*(.+?)\s*\[/brown\]#is',
            '#\[orange\]\s*(.+?)\s*\[/orange\]#is',
            '#\[purple\]\s*(.+?)\s*\[/purple\]#is',
            '#\[gray\]\s*(.+?)\s*\[/gray\]#is',
        ];

        $replace = [
            '<strong>$1</strong>',
            '<em>$1</em>',
            '<span class="underline">$1</span>',
            '<span class="overline">$1</span>',
            '<span class="linethrough">$1</span>',
            '<span class="spoiler">$1</span>',
            '<span class="quote-block">$1</span>',
            '<pre class="code-block">$1</pre>',
            '<sup>$1</sup>',
            '<sub>$1</sub>',
            '<span class="bigtext">$1</span>',
            '<span class="smalltext">$1</span>',
            '<span class="goldtext">$1</span>',
            '<span class="green">$1</span>',
            '<span class="blue">$1</span>',
            '<span class="red">$1</span>',
            '<span class="pink">$1</span>',
            '<span class="yellow">$1</span>',
            '<span class="black">$1</span>',
            '<span class="white">$1</span>',
            '<span class="brown">$1</span>',
            '<span class="orange">$1</span>',
            '<span class="purple">$1</span>',
            '<span class="gray">$1</span>',
        ];

        return preg_replace($search, $replace, $str);
    }

    public function truncate($text, $length = 128)
    {
        $curlength = mb_strlen($text);

        if ($curlength <= $length) {
            return $text;
        }

        $text = mb_substr($text, 0, $length);
        $text .= '...';

        return $text;
    }

    public function strip_bbcode($str)
    {
        return preg_replace('#\[[a-z/]+\]+#si', '$1', $str);
    }

    public function updateThread($do, $bool, $id)
    {
        global $db;

        if ($do == 'lock') {
            $col = 'is_locked';
        } elseif ($do == 'stick') {
            $col = 'is_sticky';
        } else {
            return false;
        }

        if ($bool) {
            $bool = 1;
        } else {
            $bool = 0;
        }

        $id = $db->escape($id);
        $q = $db->q("UPDATE thread SET `" . $col . "` = " . (int)$bool . " WHERE `id` = '" . $id . "' LIMIT 1");

        if ($q) {
            return true;
        } else {
            return false;
        }
    }

    public function addThis($postId)
    {
        global $engine, $db, $user;

        $postId = $db->escape($postId);
        $q = $db->q(
            "INSERT IGNORE INTO `post_upvote`(`user_id`, `post_id`) VALUES (" . (int)$user->id . ", " . (int)$postId . ")"
        );
        if (!$q) {
            return false;
        }

        return $db->mysql0->affected_rows == 1;
    }

    public function getPostAuthor($postId)
    {
        global $db;

        $postId = $db->escape($postId);
        $q = $db->q("SELECT user_id FROM post WHERE id = " . (int)$postId . " LIMIT 1");
        if ($q->num_rows == 0) {
            return false;
        }

        $author = $q->fetch_assoc()['user_id'];

        return (int)$author;
    }

    public function incrementGoldDonateStats($postId)
    {
        global $db;

        $postId = $db->escape($postId);
        $q = $db->q("UPDATE post SET gold_get = gold_get+1 WHERE id = " . (int)$postId . " LIMIT 1");

        return $q !== false;
    }

    public function clearRepliesByReplyingPost(int $postId)
    {
        global $db;

        $q = $db->q("DELETE FROM post_reply WHERE post_id = " . (int)$postId);

        return $q !== false;
    }

    public function saveReplies($post_id, $post_id_replied)
    {
        global $db;

        if (!is_array($post_id_replied)) {
            $post_id_replied = [$post_id_replied];
        }

        $vals = '';
        foreach ($post_id_replied as $replied) {
            if (!empty($vals)) {
                $vals .= ',';
            }
            $vals .= '(' . (int)$post_id . ', ' . (int)$replied . ')';
        }

        if (empty($vals)) {
            return true;
        }

        $q = $db->q("INSERT IGNORE INTO post_reply (post_id, post_id_replied) VALUES " . $vals);

        return $q !== false;
    }
}

