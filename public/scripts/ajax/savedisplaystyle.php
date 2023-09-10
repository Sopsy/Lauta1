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

if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) || !hash_equals($user->csrf_token, $_SERVER['HTTP_X_CSRF_TOKEN'])) {
    http_response_code(401);
    die(_('Your session has expired. Please refresh the page and try again.'));
}

if (!isset($_POST['style_id']) || !in_array($_POST['style_id'], [0,1,2])) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    die(_('Bad request'));
}

if ((int)$_POST['style_id'] === 1) {
    $style = 'grid';
} elseif ((int)$_POST['style_id'] === 2) {
    $style = 'compact';
} else {
    $style = 'default';
}

$user->updatePreferences('board_display_style', $style);
