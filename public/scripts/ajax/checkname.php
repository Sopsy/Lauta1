<?php

$loadClasses = [
    'cache' => '',
    'db' => '',
    'user' => false,
];
include("../../inc/engine.class.php");
new Engine($loadClasses);

if (empty($_POST['name'])) {
    die();
}

if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) || !hash_equals($user->csrf_token, $_SERVER['HTTP_X_CSRF_TOKEN'])) {
    http_response_code(401);
    die(_('Your session has expired. Please refresh the page and try again.'));
}

$_POST['name'] = preg_replace('/\s\s+/i', ' ', trim($_POST['name']));

if (preg_match('/[^A-ZÅÄÖ a-zåäö0-9\_\-]/', $_POST['name'])) {
    die(_('Allowed characters are: a-Ö 0-9 _ -'));
}

if (mb_strlen($_POST['name']) > $engine->cfg->nameMaxLength) {
    die(sprintf(_('Too long - max allowed length is %s characters'), $engine->cfg->nameMaxLength));
}

if (!$user->isFreeName($_POST['name'])) {
    die(_('Already in use'));
}
