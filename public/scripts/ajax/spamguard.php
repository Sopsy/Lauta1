<?php

// Initialize the board engine
$loadClasses = [
    'cache' => '',
    'db' => '',
    'html' => true,
    'posts' => '',
    'user' => [false, true],
    'fileupload' => '',
    'board' => [false, false, false],
];
include("../../inc/engine.class.php");
new Engine($loadClasses);

if (!isset($_POST['lock']) && !isset($_POST['unlock']) && !isset($_POST['antispam'])) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    die(_('Bad request'));
}

if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) || !hash_equals($user->csrf_token, $_SERVER['HTTP_X_CSRF_TOKEN'])) {
    http_response_code(401);
    die(_('Your session has expired. Please refresh the page and try again.'));
}

if (!$user->isMod) {
    $engine->dieWithError(_('Unauthorized'));
}

if (isset($_POST['lock'])) {
    if ((int)$_POST['lock'] === 0) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
        die(_('Bad request'));
    }
    $db->q('UPDATE user SET is_suspended = 1 WHERE user_class = 0 AND id = ' . (int)$_POST['lock']);
    $db->q('DELETE FROM user_session WHERE user_id = ' . (int)$_POST['lock']);
    $engine->writeModlog(28, (int)$_POST['lock']);
    die(_('Account locked'));
}

if (isset($_POST['unlock'])) {
    if ((int)$_POST['unlock'] === 0) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
        die(_('Bad request'));
    }
    $db->q('UPDATE user SET is_suspended = 0 WHERE user_class = 0 AND id = ' . (int)$_POST['unlock']);
    $engine->writeModlog(29, (int)$_POST['unlock']);
    die(_('Account unlocked'));
}

if (isset($_POST['antispam'])) {
    if ($_POST['antispam'] === '1') {
        $db->q('UPDATE antispam SET enabled = 1, enabled_time = NOW()');
        $engine->writeModlog(30);
        die(_('Antispam enabled'));
    } else {
        $db->q('UPDATE antispam SET enabled = 0');
        $engine->writeModlog(31);
        die(_('Antispam disabled'));
    }
}

die(_('Unknown command'));