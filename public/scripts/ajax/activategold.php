<?php

$loadClasses = [
    'cache' => '',
    'db' => '',
    'user' => false,
];
include("../../inc/engine.class.php");
new Engine($loadClasses);

if (empty($_POST['code'])) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    die(_('Bad request'));
}

if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) || !hash_equals($user->csrf_token, $_SERVER['HTTP_X_CSRF_TOKEN'])) {
    http_response_code(401);
    die(_('Your session has expired. Please refresh the page and try again.'));
}

$activate = $user->activateGoldKey(trim($_POST['code']));

if ($activate === false) {
    echo _('Invalid Gold account key');
} elseif ($activate !== true) {
    echo $activate;
}