<?php
if (isset($_GET['nojs'])) {
    error_log('NOJS ' . $_SERVER['REMOTE_ADDR']);

    header('Content-Type: image/gif');
    echo base64_decode('R0lGODlhAQABAJAAAP8AAAAAACH5BAUQAAAALAAAAAABAAEAAAICBAEAOw==');
} elseif (isset($_GET['nocookie'])) {
    error_log('NOCOOKIE ' . $_SERVER['REMOTE_ADDR']);
} else {

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
}