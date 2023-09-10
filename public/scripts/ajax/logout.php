<?php

// Initialize the board engine
$loadClasses = [
    'cache' => '',
    'db' => '',
    'user' => false,
];
include("../../inc/engine.class.php");
new Engine($loadClasses);


if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) || !hash_equals($user->csrf_token, $_SERVER['HTTP_X_CSRF_TOKEN'])) {
    http_response_code(401);
    $engine->dieWithError(_('Your session has expired. Please refresh the page and try again.'));
}

$user->destroySession($user->id, $user->session_id);
$user->updateCookie('user', '', true);