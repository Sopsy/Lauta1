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
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    die(_('Bad request'));
}
$msgid = (int)$_POST['id'];

// Deleting of posts
$post = $posts->getPost($msgid, true);

if (!$post) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    die(_('Post does not exist'));
}

if ($post['user_id'] != $user->info->id && !$user->isMod) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    die(_('You can only delete your own messages.'));
}

if (!empty($_POST['onlyfile']) && $_POST['onlyfile'] == 'true' && !empty($post['message'])) {
    $posts->deleteFileFromPosts($msgid);
    if ($user->isMod && $post['user_id'] != $user->info->id) {
        $engine->writeModlog(14, '', $post['id'], $post['thread_id'], $post['board']);
    }
} else {
    if ($posts->deletePosts($msgid)) {
        $q = $db->q('SELECT time FROM post WHERE thread_id = ' . (int)$post['thread_id'] . ' ORDER BY id DESC LIMIT 1');
        if ($q->num_rows == 1) {
            $lastPost = $q->fetch_assoc();
            $db->q("UPDATE thread SET `reply_count` = `reply_count`-1, `bump_time` = '" . $lastPost['time'] . "' WHERE `id` = '" . $post['thread_id'] . "' LIMIT 1");
        }

        if ($user->isMod && $post['user_id'] != $user->info->id) {
            $engine->writeModlog(12, '', $post['id'], $post['thread_id'], $post['board']);
        }
        die();
    } else {
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
        die(_('Deleting the message failed.'));
    }
}