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

if (!$user->isMod) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    die(_('Bad request'));
}

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
$post = $posts->getPost($msgid);

if (!$post) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    die(_('Post does not exist'));
}

$q = $db->q("UPDATE post_report SET cleared = 1, cleared_by = " . (int)$user->id . " WHERE post_id = " . $msgid);

if (!$q) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
    die(_('Action failed'));
}

$engine->writeModlog(25, '', $msgid, $posts->getThreadIdByPostId($msgid), $posts->getBoardIdByPostId($msgid));