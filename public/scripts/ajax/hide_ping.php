<?php


// Initialize the board engine
$loadClasses = [
    'cache' => '',
    'db' => '',
    'html' => true,
    'posts' => '',
    'user' => false,
];
include("../../inc/engine.class.php");
new Engine($loadClasses);

if (empty($_POST['add']) || empty($_POST['id']) || !is_numeric($_POST['id'])) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    die(_('Bad request'));
}

if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) || !hash_equals($user->csrf_token, $_SERVER['HTTP_X_CSRF_TOKEN'])) {
    http_response_code(401);
    die(_('Your session has expired. Please refresh the page and try again.'));
}

$thread = $db->escape((int)$_POST['id']);
if (!$posts->getThread($thread)) // Check it exists
{
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    die(_('Thread does not exist'));
}

if ($_POST['add'] == 'true') {
    $q = $db->q("INSERT IGNORE INTO user_thread_hide (user_id, thread_id) VALUES (" . (int)$user->id . ", " . $thread . ")");

    // Delete threads that go over the limit of 10000 hidden threads
    $db->q("DELETE a FROM user_thread_hide a JOIN (SELECT thread_id, user_id FROM user_thread_hide WHERE user_id = " . (int)$user->id . " ORDER BY added DESC LIMIT 1000 OFFSET 10000) b USING(user_id, thread_id)");
} else {
    $q = $db->q("DELETE FROM user_thread_hide WHERE user_id = " . (int)$user->id . " AND thread_id = " . $thread);
}
