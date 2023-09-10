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

if (empty($_POST['id']) || (!is_numeric($_POST['id']) && $_POST['id'] != 'all')) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    die(_('Bad request'));
}

if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) || !hash_equals($user->csrf_token, $_SERVER['HTTP_X_CSRF_TOKEN'])) {
    http_response_code(401);
    die(_('Your session has expired. Please refresh the page and try again.'));
}

if (!empty($_POST['do']) AND $_POST['do'] == 'remove') {
    // Remove
    if (!$user->unfollowThread($_POST['id'])) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
        echo _('There was an error removing the thread from followed threads.');
    }
} elseif ($_POST['id'] != 'all') {
    // Add
    $post = $posts->getThread($_POST['id']);
    if (!$post) {
        die(_('Thread does not exist.'));
    }

    if (array_key_exists($post['id'], $user->info->followedThreads)) {
        die();
    }

    if (!$user->followThread($_POST['id'])) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
        echo _('There was an error adding the thread to followed threads.');
    }
}
