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

if (empty($_POST) || empty($_POST['captcha'])) {
    http_response_code(400);
    echo _('Your browser did not send us a Google reCAPTCHA response. Check that you are not blocking it. Please refresh this page and try again.');
    die();
}

$captchaOk = $engine->verifyReCaptchaV3($_POST['captcha']);
if (!$captchaOk) {
    http_response_code(403);
    echo _('Google reCAPTCHA thinks you are a robot. Please refresh this page and try again.');
    die();
}

echo $engine->getAccessCookieKey();