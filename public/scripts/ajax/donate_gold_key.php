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

if (empty($_POST['key']) || empty($_POST['post_id']) || !is_numeric($_POST['post_id'])) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    die(_('Bad request'));
}

$new_owner_id = $posts->getPostAuthor($_POST['post_id']);

$q = $db->q("SELECT id FROM user WHERE id = " . (int)$new_owner_id . " LIMIT 1");
if ($q->num_rows == 0) {
    http_response_code(404);
    die(_('The user account this post was made with does not exist anymore...'));
}

if (!$new_owner_id) {
    http_response_code(404);
    die(_('The user account this post was made with does not exist anymore...'));
}

if ($new_owner_id == $user->id) {
    http_response_code(403);
    die(_('You cannot donate Gold account keys to yourself.'));
}

$donateLimitReached = $user->keyDonateLimitReached($_POST['key']);
if ($donateLimitReached) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    die(_('This Gold account key has already been donated!'));
}

$donate = $user->donateKeyToUser($_POST['key'], $new_owner_id, $_POST['post_id']);

if (!$donate) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    die(_('An error occurred'));
} else {
    $posts->incrementGoldDonateStats($_POST['post_id']);
}
