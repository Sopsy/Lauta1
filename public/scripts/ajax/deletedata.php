<?php

$loadClasses = [
    'cache' => '',
    'db' => '',
    'user' => false,
    'posts' => '',
];
include("../../inc/engine.class.php");
new Engine($loadClasses);

if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) || !hash_equals($user->csrf_token, $_SERVER['HTTP_X_CSRF_TOKEN'])) {
    http_response_code(401);
    die(_('Your session has expired. Please refresh the page and try again.'));
}

if (!empty($user->info->password)) {
    if (empty($_POST['password'])) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
        die(_('Invalid password'));
    }

    if (!password_verify($_POST['password'], $user->info->password)) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
        die(_('Invalid password'));
    }
}

if (!empty($_POST['threads']) && $_POST['threads'] === 'true') {
    $q = $db->q("SELECT `id` FROM thread WHERE `user_id` = " . (int)$user->id);
    $deleteIds = $q->fetch_all(MYSQLI_NUM);
    $deleteIds = array_map('current', $deleteIds);

    $deleteIds = implode(',', $deleteIds);
    $msgid = $deleteIds;
    if (!empty($msgid)) {
        if (!$posts->deleteThreads($msgid, 2)) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
            error_log('DATA (threads) DELETION FAILED: ' . $user->id);
            die(_('Deleting your threads failed!'));
        }
    }
}

if (!empty($_POST['replies']) && $_POST['replies'] === 'true') {
    $q = $db->q("SELECT `id` FROM `post` a
        WHERE `user_id` = " . (int)$user->id . "
        AND (SELECT COALESCE(user_id, 0) FROM thread WHERE id = a.thread_id) != " . (int)$user->id);
    $deleteIds = $q->fetch_all(MYSQLI_NUM);
    $deleteIds = array_map('current', $deleteIds);

    $deleteIds = implode(',', $deleteIds);
    $msgid = $deleteIds;
    if (!empty($msgid)) {
        if (!$posts->deletePosts($msgid)) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
            error_log('DATA (replies) DELETION FAILED: ' . $user->id);
            die(_('Deleting your replies failed!'));
        }
    }
}

if (!empty($_POST['upvotes']) && $_POST['upvotes'] === 'true') {
    $qa = $db->q("SELECT post_id FROM post_upvote WHERE user_id = " . (int)$user->id);

    $q = $db->q("DELETE FROM post_upvote WHERE user_id = " . (int)$user->id);
    if (!$q) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
        error_log('DATA (upvotes) DELETION FAILED: ' . $user->id);
        die(_('Deleting your upvotes failed!'));
    }
}