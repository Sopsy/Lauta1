<?php

// Initialize the board engine
$loadClasses = [
    'cache' => '',
    'db' => '',
    'html' => true,
    'posts' => '',
    'user' => false,
];

include '../../inc/engine.class.php';
new Engine($loadClasses);

if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) || !hash_equals($user->csrf_token, $_SERVER['HTTP_X_CSRF_TOKEN'])) {
    http_response_code(401);
    $engine->dieWithError(_('Your session has expired. Please refresh the page and try again.'));
}

if (empty($_POST['username']) || empty($_POST['email'])) {
    $engine->dieWithError(_('Bad request'));
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

$query = $db->q("SELECT id, email FROM user WHERE username = '" . $db->escape($_POST['username']) . "'");
$result = $query->fetch_assoc();

if (!$result || !password_verify($_POST['email'], $result['email'])) {
    usleep(random_int(random_int(1600000, 1800000), random_int(2400000, 2800000)));
    die();
}

$key = bin2hex(random_bytes(10));
$q = $db->q("INSERT INTO user_recovery_key (user_id, recovery_key) VALUES (" . $result['id'] . ", UNHEX('" . $key . "'))");

include '../../inc/email.class.php';
$body = _("Hi %s,\n\nSomeone has requested an account recovery key using your username and email address. If this wasn't you, you can just ignore this email.\n\nRecovery key: %s\nThe recovery key is valid for one hour.\n\nBest regards,\nYlilauta.org");
$body = sprintf($body, $_POST['username'], $key);
$email = new Email($_POST['email'], true);
$email->from($engine->cfg->siteName, $engine->cfg->noreplyEmail);
$email->subject(_('Ylilauta account recovery'));
$email->replyTo($engine->cfg->replyToEmail);
$email->addPart($email->htmlToPlainText($body), 'text/plain');
$send = $email->send();