<?php

// Initialize the board engine
$loadClasses = [
    'cache' => '',
    'db' => '',
    'user' => false,
    'html' => true,
];
include("../../inc/engine.class.php");
new Engine($loadClasses);

if (!$user->is_anonymous) {
    $engine->redirectExit('/');
}

if (empty($_POST)) {
    $engine->redirectExit('/');
}

if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) || !hash_equals($user->csrf_token, $_SERVER['HTTP_X_CSRF_TOKEN'])) {
    http_response_code(401);
    $engine->dieWithError(_('Your session has expired. Please refresh the page and try again.'));
}

if (empty($_POST['username']) || empty($_POST['password'])) {
    http_response_code(403);
    $engine->dieWithError(_('Invalid username or password'));
}

if ($user->useCaptcha()) {
    if (empty($_POST['captcha'])) {
        http_response_code(403);
        $engine->dieWithError(
            _(
                'Your browser did not send us a Google reCAPTCHA response. Check that you are not blocking it. Please refresh this page and try again.'
            )
        );
    }

    $captchaOk = $engine->verifyReCaptchaV3($_POST['captcha']);
    if (!$captchaOk) {
        http_response_code(403);
        $engine->dieWithError(_('Google reCAPTCHA thinks you are a robot. Please refresh this page and try again.'));
    }
}

$q = $db->q("SELECT id, password, user_class, is_suspended FROM user
    WHERE username = '" . $db->escape($_POST['username']) . "' LIMIT 1");

if ($q->num_rows == 0) {
    $engine->dieWithError(_('User account does not exist'));
}

$user_new = $q->fetch_object();
if (empty($user_new->password)) {
    $engine->dieWithError(_('User account does not exist'));
}

if (!password_verify($_POST['password'], $user_new->password)) {
    if ($user_new->user_class != 0) {
        $engine->writeModlog(6, 'Login: ' . $_POST['username']);
    }
    $engine->dieWithError(_('Invalid password'));
}

if ($user_new->is_suspended) {
    $engine->dieWithError(_('This user account has been suspended'));
}

// Update password hash if the options have changed
if (password_needs_rehash($user_new->password, $engine->cfg->passwordHashType, $engine->cfg->passwordHashOptions)) {
    $user->changePassword($_POST['password'], $user_new->id);
}

if ($user_new->user_class != 0) {
    $engine->writeModlog(27, 'Login: ' . $_POST['username']);
}

$session_id = $user->createSession($user_new->id);
$user->updateCookie('user', $session_id . $user_new->id);

$db->q("UPDATE user SET last_login = NOW() WHERE id = " . (int)$user_new->id . " LIMIT 1");