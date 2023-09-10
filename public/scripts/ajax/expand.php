<?php

// Initialize the board engine
$loadClasses = [
    'cache' => '',
    'db' => '',
    'html' => true,
    'posts' => '',
    'fileupload' => '',
    'user' => [false, true],
    'board' => [false, false, false],
];
include("../../inc/engine.class.php");
new Engine($loadClasses);

if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    die(_('Bad request'));
}

if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) || !hash_equals($user->csrf_token, $_SERVER['HTTP_X_CSRF_TOKEN'])) {
    http_response_code(401);
    die(_('Your session has expired. Please refresh the page and try again.'));
}

$start = false;
if (!empty($_POST['start'])) {
    $start = (int)$_POST['start'];
}
$count = 50;
if (!empty($_POST['count'])) {
    $count = (int)$_POST['count'];
}

$post = $posts->getThreadPosts($_POST['id'], $count, $start);
if (!$post) {
    die();
}

$board->getBoardInfo($post[0]['board'], false);

foreach ($post AS $singlePost) {
    $html->printPost($singlePost, true, false);
}