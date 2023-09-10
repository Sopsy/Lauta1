<?php

class board
{

    public $threadsPerPage;

    public function __construct($confVars)
    {
        global $engine, $user;


        if (!empty($user)) {
            if ($user->getDisplayStyle() == 'style-replies') {
                $this->threadsPerPage = $engine->cfg->threadsPerPage;
            } elseif ($user->getDisplayStyle() == 'style-box') {
                $this->threadsPerPage = $engine->cfg->threadsPerPageBox;
            } elseif ($user->getDisplayStyle() == 'style-compact') {
                $this->threadsPerPage = $engine->cfg->threadsPerPageCompact;
            }
        }
        else {
            $this->threadsPerPage = $engine->cfg->threadsPerPage;
        }

        $board = $confVars[0];
        $pageOrThread = $confVars[1];
        $threadNotOpen = $confVars[2];

        $this->isLoaded = false;
        if ($board != false) {
            $this->getBoardInfo($board);
            if (!$this->isLoaded) {
                return false;
            }

            if ($threadNotOpen && $pageOrThread > $engine->cfg->boardPages) {
                $engine->return_not_found();
            }

            if ($pageOrThread != false) {
                $this->threads = $this->getThreads($threadNotOpen, $pageOrThread);
            }

            if (isset($user) && $user->id != false && (empty($user->info->last_board) || $user->info->last_board != $this->info['boardid'] || $user->info->last_active < time() - 59 || $user->info->account_created > time() - 1)) {
                $user->updateAccount('last_board', $this->info['boardid']);
            }
        }
    }

    public function getBoardInfo($board, $isUrl = true, $extendedInfo = true)
    {
        global $engine, $db;

        $board = $db->escape($board);

        if ($isUrl) {
            $q = $db->q("SELECT *, id as boardid, name AS boardname FROM `board` WHERE `url` = '" . $board . "' LIMIT 1");
        } else {
            $q = $db->q("SELECT *, id as boardid, name AS boardname FROM `board` WHERE `id` = '" . $board . "' LIMIT 1");
        }

        if ($q AND $q->num_rows == 1) {
            $board = $q->fetch_assoc();

            if ($extendedInfo) {
                $board['pageCount'] = $engine->cfg->boardPages;
            }

            if (!$board['is_locked']) {
                $this->isLocked = false;
            } else {
                $this->isLocked = true;
            }

            $this->info = $board;
            $this->isLoaded = true;

        }
    }

    public function getThreads($threadNotOpen, $pageOrThread)
    {
        global $engine, $db, $user;

        $threadOpen = !$threadNotOpen; // For clarity
        $pageOrThread = $db->escape($pageOrThread);

        if ($user->getDisplayStyle() == 'style-box' && !$threadOpen) {
            $q = $db->q('
                SELECT *, f.id AS fileid, t.id AS id, p.id AS post_id,
                    (SELECT COUNT(*) FROM post_upvote WHERE post_id = p.id) AS upvote_count
                FROM thread t
                LEFT JOIN post p ON p.thread_id = t.id AND p.op_post = 1
                LEFT JOIN post_file pf ON pf.post_id = p.id
                LEFT JOIN file f ON f.id = pf.file_id
                WHERE t.board_id = ' . (int)$this->info['boardid'] . '
                AND t.id NOT IN (SELECT thread_id FROM user_thread_hide WHERE user_id = ' . (int)$user->id . ')
                ORDER BY t.is_sticky DESC, t.bump_time DESC
                LIMIT ' . ($pageOrThread - 1) * $this->threadsPerPage . ', ' . $this->threadsPerPage);
        } elseif ($threadOpen) {
            $q = $db->q('
                SELECT *
                FROM thread
                WHERE id = ' . (int)$pageOrThread . '
                LIMIT 1');
        } else {
            $q = $db->q('
                SELECT *
                FROM thread
                WHERE board_id = ' . (int)$this->info['boardid'] . '
                AND id NOT IN (SELECT thread_id FROM user_thread_hide WHERE user_id = ' . (int)$user->id . ')
                ORDER BY is_sticky DESC, bump_time DESC
                LIMIT ' . ($pageOrThread - 1) * $this->threadsPerPage . ', ' . $this->threadsPerPage);
        }
        $qResult = $q->fetch_all(MYSQLI_ASSOC);

        $threads = [];
        foreach ($qResult as $thread) {
            $thread['replies'] = [];

            if (mb_strlen($thread['subject']) == 0) {
                $thread['subject'] = _('Thread') . ' ' . $thread['id'];
            }
            $thread['lastReplyId'] = 0;

            if ($user->getDisplayStyle() == 'style-replies' || $threadOpen) {
                // Display: style-replies

                $replyCount = (int)$engine->cfg->replyCount;
                if ($replyCount == 0) {
                    $replyCount = 1;
                }
                // Get thread replies
                if (!$threadOpen) {
                    $limit = $replyCount;
                } else {
                    $limit = 1000;
                }

                $where = '';
                if ($user->hasGoldAccount) {
                    $where = " AND (p.name IS NULL OR p.name NOT IN (SELECT name FROM user_name_hide WHERE user_id = " . (int)$user->id . "))";
                }

                $qb = $db->q("
                SELECT
                    id, user_id, thread_id, ip, remote_port, country_code, name, public_user_id,
                    timestamp AS time, message, op_post, admin_post, gold_hide, gold_get, edited, orig_name,
                    fileid, extension, duration, has_sound, post_replies, post_tags, real_upvote_count AS upvote_count, op_post_id,
                    op_post AS is_op_post
                FROM (
                    SELECT p.*, b.orig_name, c.id AS fileid, c.extension, c.duration, c.has_sound,
                        UNIX_TIMESTAMP(p.`time`) AS timestamp,
                        (SELECT GROUP_CONCAT(post_id) FROM post_reply WHERE post_id_replied = p.id) AS post_replies,
                        (SELECT GROUP_CONCAT(tag_id) FROM post_tag WHERE post_id = p.id) AS post_tags,
                        (SELECT COUNT(*) FROM post_upvote WHERE post_id = p.id) AS real_upvote_count,
                        p.id AS op_post_id
                    FROM `post` p
                    LEFT JOIN `post_file` b ON b.`post_id` = p.`id`
                    LEFT JOIN `file` c ON b.`file_id` = c.`id`
                    WHERE p.`thread_id` = " . $thread['id'] . " AND p.op_post = 1" . $where . "
                    ORDER BY p.`id` ASC LIMIT 1
                ) AS op
                UNION
                SELECT
                    id, user_id, thread_id, ip, remote_port, country_code, name, public_user_id,
                    timestamp AS time, message, op_post, admin_post, gold_hide, gold_get, edited, orig_name,
                    fileid, extension, duration, has_sound, post_replies, post_tags, real_upvote_count AS upvote_count, op_post_id,
                    op_post AS is_op_post
                FROM (
                    SELECT p.*, b.orig_name, c.id AS fileid, c.extension, c.duration, c.has_sound,
                        UNIX_TIMESTAMP(p.`time`) AS timestamp,
                        (SELECT GROUP_CONCAT(post_id) FROM post_reply WHERE post_id_replied = p.id) AS post_replies,
                        (SELECT GROUP_CONCAT(tag_id) FROM post_tag WHERE post_id = p.id) AS post_tags,
                        (SELECT COUNT(*) FROM post_upvote WHERE post_id = p.id) AS real_upvote_count,
                        op.id AS op_post_id
                    FROM `post` p
                    LEFT JOIN `post_file` b ON b.`post_id` = p.`id`
                    LEFT JOIN `file` c ON b.`file_id` = c.`id`
                    LEFT JOIN post op ON op.thread_id = p.thread_id AND op.op_post = 1
                    WHERE p.`thread_id` = " . $thread['id'] . " AND p.op_post = 0" . $where . "
                    ORDER BY p.`id` DESC LIMIT " . $limit . "
                ) AS replies
                ORDER BY id ASC");
                $replies = $qb->fetch_all(MYSQLI_ASSOC);

                if (!empty($replies)) {
                    $thread['lastReplyId'] = $replies[array_key_last($replies)]['id'];
                }

                if (!empty($replies) && $replies[0]['is_op_post']) {
                    $thread['op_post'] = array_shift($replies);
                } else {
                    $thread['op_post'] = false;
                }

                if ($threadOpen || (int)$engine->cfg->replyCount !== 0) {
                    $thread['replies'] = $replies;
                }
            } else {
                $thread['op_post'] = false;
            }

            // Insert the values into the threads-array
            $threads[] = $thread;
        }

        return $threads;
    }

    public function getBoardUrl(int $boardid)
    {
        global $db;
        $boardid = $db->escape($boardid);

        $q = $db->q("SELECT `url` FROM `board` WHERE `id` = " . $boardid . " LIMIT 1");
        $boardurl = $q->fetch_assoc();

        return $boardurl['url'];
    }
}
