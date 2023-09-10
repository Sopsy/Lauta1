<?php
header('Content-Type: text/json');

$loadClasses = [
    'cache' => '',
    'db' => '',
    'html' => true,
    'posts' => '',
    'user' => [false, true],
    'fileupload' => '',
    'board' => [false, false, false],
];
include("../../inc/engine.class.php");
new Engine($loadClasses);

if (!isset($_POST['fromId']) || !is_numeric($_POST['fromId']) || empty($_POST['threadId']) || !is_numeric($_POST['threadId'])) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    die(_('Bad request'));
}

if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) || !hash_equals($user->csrf_token, $_SERVER['HTTP_X_CSRF_TOKEN'])) {
    http_response_code(401);
    die(_('Your session has expired. Please refresh the page and try again.'));
}

$where = '';
if ($user->hasGoldAccount) {
    $where = ' AND (name IS NULL OR name NOT IN (SELECT name FROM user_name_hide WHERE user_id = ' . (int)$user->id . '))';
}

$thread = $posts->getThread($_POST['threadId']);
if (!$thread) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 410 Gone');
    die();
}

$load = sys_getloadavg();
if ($load[1] < 10) {
    $visibleReplies = !empty($_POST['visibleReplies']) ? explode(',', $_POST['visibleReplies']) : null;
    if ($visibleReplies !== null) {
        $threadPosts = $db->q('SELECT id FROM post WHERE thread_id = ' . (int)$thread['id']);
        $threadPosts = $threadPosts->fetch_all(MYSQLI_NUM);
        $threadPosts = array_map('current', $threadPosts);

        $deleted = array_diff($visibleReplies, $threadPosts);

        if (!empty($deleted)) {
            header('X-Deleted-Replies: ' . implode(',', $deleted));
        }
    }
}

$post_ids = $db->q("SELECT `id` FROM `post` WHERE `thread_id` = " . (int)$_POST['threadId'] . " AND `id` > " . (int)$_POST['fromId'] . $where . " ORDER BY `id` ASC LIMIT 100");
if ($post_ids->num_rows == 0) {
    header('X-Response-Time: ' . round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 4) . 's');
    echo json_encode(['html' => '']);
    die();
}

$post_ids = $post_ids->fetch_all(MYSQLI_NUM);
$post_ids = array_map('current', $post_ids);

$boards = $db->q("SELECT *, name AS boardname FROM board");
$boards = $db->fetchAll($boards, false, 'id');
ob_start();
foreach ($post_ids AS $post) {
    $post = $posts->getPost($post, true);
    if (!$post) {
        continue;
    }

    if (empty($board->info)) {
        $board->info = $boards[$post['board']];
    }

    if ($board->info['url'] == 'kulta' AND !$user->hasGoldAccount) {
        continue;
    }
    if ($board->info['url'] == 'platina' AND !$user->hasPlatinumAccount) {
        continue;
    }

    $html->printPost($post, true, true);
}
$html = ob_get_clean();

header('X-Response-Time: ' . round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 4) . 's');
echo json_encode(['html' => $html]);

// Set last seen id for followed threads
$last_reply_id = array_pop($post_ids);
$user->followed_update_last_seen_reply($_POST['threadId'], $last_reply_id);

// Clear unread_count from followed threads
$user->followed_clear_unread_count($_POST['threadId']);
