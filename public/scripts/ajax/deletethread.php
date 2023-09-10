<?php

$loadClasses = [
    'cache' => '',
    'db' => '',
    'user' => false,
    'posts' => '',
    'fileupload' => '',
];
include("../../inc/engine.class.php");
new Engine($loadClasses);

if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) || !hash_equals($user->csrf_token, $_SERVER['HTTP_X_CSRF_TOKEN'])) {
    http_response_code(401);
    die(_('Your session has expired. Please refresh the page and try again.'));
}

if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
    http_response_code(400);
    die(_('Bad request'));
}
$msgid = $_POST['id'];

// Deleting of posts
$thread = $posts->getThreadWithBoard($msgid);

if (!$thread) {
    http_response_code(400);
    die(_('Thread does not exist'));
}

if ($thread['user_id'] != $user->info->id && !$user->isMod) {
    http_response_code(400);
    die(_('You can only delete your own messages.'));
}

if (
    !in_array($thread['board_id'], [1,2,65,74])
    && $thread['reply_count'] > 50
    && $thread['distinct_reply_count'] > 10
    && strtotime($thread['bump_time']) > time() - 604800
    && !$user->isMod
) {
    http_response_code(403);
    die(_('You cannot delete this thread now because it\'s still active.'));
}

if ($user->isMod && $thread['user_id'] != $user->info->id) {
    $reason = 3;
    $engine->writeModlog(11, '', $posts->getOpPostId($thread['id']), $thread['id'], $thread['board_id']);
} else {
    $reason = 2;
}

if ($posts->deleteThreads((int)$msgid, $reason)) {
    die();
} else {
    http_response_code(500);
    die(_('Deleting the thread failed.'));
}