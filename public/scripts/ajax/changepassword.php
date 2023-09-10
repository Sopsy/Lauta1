<?php

$loadClasses = [
    'cache' => '',
    'db' => '',
    'user' => false,
];
include '../../inc/engine.class.php';
new Engine($loadClasses);

if (empty($_POST['password']) || empty($_POST['passwordconfirm'])) {
    $engine->dieWithError(_('Bad request'));
}

if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) || !hash_equals($user->csrf_token, $_SERVER['HTTP_X_CSRF_TOKEN'])) {
    http_response_code(401);
    $engine->dieWithError(_('Your session has expired. Please refresh the page and try again.'));
}

if (mb_strlen($_POST['password']) < 6) {
    $engine->dieWithError(_('Your password needs to be at least six characters long'));
}

if ($_POST['password'] !== $_POST['passwordconfirm']) {
    $engine->dieWithError(_('New passwords do not match'));
}

if (!empty($_POST['current'])) {
    if (!password_verify($_POST['current'], $user->info->password)) {
        $engine->dieWithError(_('Wrong current password'));
    }
    if (!$user->changePassword($_POST['password'])) {
        $engine->dieWithError(_('Bad request'));
    }
} elseif (!empty($_POST['recoverykey'])) {

    if ($user->useCaptcha()) {
        if (empty($_POST['captcha'])) {
            error_log('NO CAPTCHA');
            $engine->dieWithError(_('Your browser did not send us a Google reCAPTCHA response. Check that you are not blocking it. Please refresh this page and try again.'));
        }

        $captchaOk = $engine->verifyReCaptchaV3($_POST['captcha']);
        if (!$captchaOk) {
            $engine->dieWithError(_('Google reCAPTCHA thinks you are a robot. Please refresh this page and try again.'));
        }
    }

    $query = $db->q("
        SELECT b.id
        FROM user_recovery_key a
        LEFT JOIN user b ON b.id = a.user_id
        WHERE
            a.recovery_key = UNHEX('" . $db->escape($_POST["recoverykey"]) . "')
            AND b.username = '" . $db->escape($_POST["username"]) . "'
            AND a.time > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $uid = $query->fetch_assoc();

    if (empty($uid)) {
        $engine->dieWithError(_('Account recovery information was incorrect'));
    }

    $uid = (int)$uid['id'];

    if (!$user->changePassword($_POST['password'], $uid)) {
        $engine->dieWithError(_('Bad request'));
    } else {
        $db->q("DELETE FROM user_recovery_key WHERE user_id = " . $uid);

        // Login with the new details
        $session_id = $user->createSession($uid);
        $user->updateCookie('user', $session_id . $uid);
    }
} else {
    $engine->dieWithError(_('Bad request'));
}

