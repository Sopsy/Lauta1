<?php

$loadClasses = [
    'cache' => '',
    'db' => '',
    'user' => false,
    'posts' => '',
    'fileupload' => '',
];
include("../../inc/engine.class.php");
new Engine($loadClasses);

if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) || !hash_equals($user->csrf_token, $_SERVER['HTTP_X_CSRF_TOKEN'])) {
    http_response_code(401);
    die(_('Your session has expired. Please refresh the page and try again.'));
}

if (!$user->isWhitelisted() && !$engine->ipIsAllowed($_SERVER['REMOTE_ADDR'])) {
    http_response_code(403);
    $engine->dieWithError(_('The internet connection you are using is commonly used for abuse, so reporting from it is not allowed.'));
}

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

// Reporting of posts
if ($user->isBanned) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
    die(_('You are banned.'));
}

if (empty($_POST['reason'])) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    die(_('You have to give a reason for your report.'));
}
if (!empty($_POST['reasonadd'])) {
    $_POST['reason'] .= ': ' . htmlspecialchars($_POST['reasonadd']);
}
$reportReason = htmlspecialchars($_POST['reason']);

if (!$posts->postExists((int)$_POST['postId'])) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    die(_('Post does not exist'));
}

// Blocked users
$blockedUsers = $engine->cfg->reportBlockedUsers;
if (in_array($user->id, $blockedUsers)) {
    die();
}

if ($posts->isReported((int)$_POST['postId'])) {
    die();
}

if (!$posts->reportPost((int)$_POST['postId'], $reportReason)) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
    die(_('Reporting the message failed.'));
}


