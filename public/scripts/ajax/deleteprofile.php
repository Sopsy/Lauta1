<?php

$loadClasses = [
    'cache' => '',
    'db' => '',
    'user' => false,
    'posts' => '',
];
include("../../inc/engine.class.php");
new Engine($loadClasses);

if (empty($_POST['confirm'])) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    die(_('Bad request'));
}

if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) || !hash_equals($user->csrf_token, $_SERVER['HTTP_X_CSRF_TOKEN'])) {
    http_response_code(401);
    die(_('Your session has expired. Please refresh the page and try again.'));
}

if ($_POST['confirm'] !== 'on') {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    die(_('You did not confirm the deletion of your profile. No actions taken.'));
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

$userId = $user->id;

if (!$user->deleteProfile()) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
    die(_('Unexpected error'));
}

$q = $db->q("SELECT id FROM post WHERE user_id = " . (int)$userId);
$deleteIds = $db->fetchAll($q, 'id');
$deleteIds = implode(',', $deleteIds);
$msgid = $deleteIds;
if (!empty($msgid) && !empty($_POST['alsoPosts']) && $_POST['alsoPosts'] === 'true') {
    if (!$posts->deletePosts($msgid)) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
        error_log('POST DELETION FAILED: ' . $userId);
        die(_('Your user account was deleted successfully, but an error occurred while trying to delete your posts. Please contact us to continue with the deletion of your posts.'));
    }
}