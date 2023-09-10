<?php

// There should always be a page number
if (empty($_GET['page']) OR !is_numeric($_GET['page'])) {
    $_GET['page'] = 1;
}

// Initialize the board engine
$loadClasses = [
    'cache' => '',
    'db' => '',
    'html' => '',
    'posts' => '',
    'user' => false,
    'board' => [false, false, false],
];
include("inc/engine.class.php");
new Engine($loadClasses);

if (!isset($_GET['action'])) {
    $engine->return_not_found();
}

$hideThreads = ' AND t.id NOT IN (SELECT thread_id FROM user_thread_hide WHERE user_id = ' . (int)$user->id . ')';
if (!$user->hasGoldAccount) {
    $hideThreads .= ' AND t.board_id != 74 AND t.board_id NOT IN (SELECT id FROM board WHERE is_hidden = 1)';
}
if (!$user->hasPlatinumAccount) {
    $hideThreads .= ' AND t.board_id != 98';
}

if ($_GET['page'] > 50) {
    $engine->return_not_found();
}

$ids = [];
$limit = ($_GET['page'] - 1) * $board->threadsPerPage . ", " . $board->threadsPerPage;
if ($_GET['action'] == 'mythreads') {
    $url = "mythreads";
    $title = _('My threads');
    $description = _('The threads you have made are shown on this page.');

    $q = $db->q("SELECT id FROM thread t WHERE user_id = " . (int)$user->id . "" . $hideThreads . " ORDER BY bump_time DESC LIMIT " . $limit);
    while ($thread = $q->fetch_assoc()) {
        $ids[] = $thread['id'];
    }
} elseif ($_GET['action'] == 'repliedthreads') {
    $url = "repliedthreads";
    $title = _('Replied threads');
    $description = _('The threads you have replied to are shown on this page.');

    $q = $db->q("SELECT b.id FROM (
            SELECT DISTINCT a.thread_id
            FROM post a
            LEFT JOIN thread t ON t.id = a.thread_id
            WHERE t.user_id != " . (int)$user->id . " AND a.user_id = " . (int)$user->id . str_replace('AND t.id NOT IN','AND a.thread_id NOT IN', $hideThreads) . "
        ) a
        JOIN thread b ON b.id = a.thread_id
        ORDER BY b.bump_time DESC LIMIT " . $limit);

    while ($thread = $q->fetch_assoc()) {
        $ids[] = $thread['id'];
    }
} elseif ($_GET['action'] == 'followedthreads') {
    $url = "followedthreads";
    $title = _('Followed threads');
    $description = _('The threads you are following are shown on this page.');

    if (!empty($user->getFollowedThreads())) {
        $followedUnreadCount = [];
        foreach ($user->getFollowedThreads() as $thread) {
            $followedUnreadCount[$thread['thread_id']] = $thread['unread_count'];
        }
        $ids = array_keys($followedUnreadCount);
        $ids = array_splice($ids, (($_GET['page'] - 1) * $board->threadsPerPage), $board->threadsPerPage);
    }
} elseif ($_GET['action'] == 'allthreads') {
    $url = "allthreads";
    $title = _('All threads');
    $description = _('Threads from all boards are shown on this page.');

    $notIn = '';
    if (!empty($user->getHiddenBoards())) {
        $notIn .= ' AND board_id NOT IN (' . implode(',', $user->getHiddenBoards()) . ')';
    } else {
        $notIn .= ' AND board_id NOT IN(154)';
    }

    $q = $db->q("SELECT `id` FROM thread t WHERE 1" . $notIn . $hideThreads . " ORDER BY `bump_time` DESC LIMIT " . $limit);

    while ($thread = $q->fetch_assoc()) {
        $ids[] = $thread['id'];
    }
} elseif ($_GET['action'] == 'hiddenthreads') {
    $url = "hiddenthreads";
    $title = _('Hidden threads');
    $description = _('Threads you have hidden are shown on this page.');

    $ids = $user->getHiddenThreads();
    $ids = array_splice($ids, (($_GET['page'] - 1) * $board->threadsPerPage), $board->threadsPerPage);
} else {
    $engine->return_not_found();
}

$pageCount = 50;

$html->printHeader($title . ' | ' . $engine->cfg->siteName);
$html->printSidebar();

echo '<div id="right" class="customboard">';

$html->printBoardHeader($title, $description);
$html->printPostForm();
$html->printNavigationBar(false, $_GET['page'], $pageCount, [$url], [$title]);

$html->print_ad('customboard', 4);

if (empty($ids)) {
    echo '<p class="infobar">' . _('You have no threads here.') . '</p>';
} else {
    if ($user->getDisplayStyle() == 'style-box') {
        $threads = $posts->getThreadListThreads($ids);
    } else {
        $threads = $posts->getThreads($ids);
    }
    $boards = $db->q("SELECT *, name AS boardname FROM board");
    $boards = $db->fetchAll($boards, false, 'id');
    echo '<div class="threads ' . $user->getDisplayStyle() . '">';
    foreach ($threads as $thread) {
        $board->info = $boards[$thread['board_id']];

        if (!empty($followedUnreadCount) && $followedUnreadCount[$thread['id']] != 0) {
            $thread['subject'] = '(' . $followedUnreadCount[$thread['id']] . ') ' . $thread['subject'];
        }

        // Get thread replies
        $thread['lastReplyId'] = 0;
        $thread['replies'] = [];

        if ($user->getDisplayStyle() == 'style-replies') {
            $replyCount = (int)$engine->cfg->replyCount;
            if ($replyCount == 0) {
                $replyCount = 1;
            }

            $orderlimit = 'DESC LIMIT ' . $replyCount;
            $notIn = '';
            if ($user->hasGoldAccount) {
                $notIn .= " AND (p.name IS NULL OR p.name NOT IN (SELECT name FROM user_name_hide WHERE user_id = " . (int)$user->id . "))";
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
                    WHERE p.op_post = 1 AND p.`thread_id` = " . $thread['id'] . "" . $notIn . "
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
                    WHERE p.op_post = 0 AND p.`thread_id` = " . $thread['id'] . "" . $notIn . "
                    ORDER BY p.`id` " . $orderlimit . "
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

            if ((int)$engine->cfg->replyCount !== 0) {
                $thread['replies'] = $replies;
            }
        } else {
            $thread['op_post'] = false;
        }

        $html->printThread($thread, false, $board->info['url'], $board->info['boardname']);
    }
    echo '</div>';
}

$html->print_ad('customboard', 7);
$html->printNavigationBar(false, $_GET['page'], $pageCount, [$url], [$title]);

echo '</div>';
