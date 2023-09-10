<?php

// Initialize the board engine
include("../../inc/engine.class.php");
new Engine(['cache' => '', 'db' => '', 'user' => '']);

if (empty($_POST['id']) || !is_numeric($_POST['id']) && $_POST['id'] != 'all') {
    die();
}

if (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'];
} elseif (!empty($_POST['csrfToken'])) {
    $csrfToken = $_POST['csrfToken'];
}

if (empty($csrfToken) || !hash_equals($user->csrf_token, $csrfToken)) {
    http_response_code(401);
    die();
}

if ($_POST['id'] == 'all') {
    $user->markAllNotificationsAsRead();
    die();
}

$user->markNotificationAsRead((int)$_POST['id']);