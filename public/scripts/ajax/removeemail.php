<?php

$loadClasses = [
    'cache' => '',
    'db' => '',
    'user' => false,
];
include '../../inc/engine.class.php';
new Engine($loadClasses);

if (!isset($user)) {
    http_response_code(400);
    die(_('Please refresh this page and try again.'));
}

if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) || !hash_equals($user->csrf_token, $_SERVER['HTTP_X_CSRF_TOKEN'])) {
    http_response_code(401);
    die(_('Your session has expired. Please refresh the page and try again.'));
}

if (empty($_POST['password'])) {
    http_response_code(400);
    die(_('Please give your current password'));
}

if (!password_verify($_POST['password'], $user->info->password)) {
    http_response_code(403);
    die(_('Invalid password'));
}

$user->removeEmail();