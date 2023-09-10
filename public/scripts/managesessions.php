<?php

$loadClasses = [
    'cache' => '',
    'db' => '',
    'user' => false,
    'html' => true,
];
include("../inc/engine.class.php");
new Engine($loadClasses);

if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) || !hash_equals($user->csrf_token, $_SERVER['HTTP_X_CSRF_TOKEN'])) {
    $engine->dieWithError(_('Bad request'));
}

if (!empty($_POST['destroy'])) {
    if ($_POST['destroy'] == 'all') {
        $user->destroyAllSessions($user->id);
    } else {
        $user->destroySession($user->id, $_POST['destroy']);
    }
}
