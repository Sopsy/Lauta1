<?php

$loadClasses = [
    'cache' => '',
    'db' => '',
    'user' => false,
    'html' => true,
];

include("../../inc/engine.class.php");
new Engine($loadClasses);

if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) || !hash_equals($user->csrf_token, $_SERVER['HTTP_X_CSRF_TOKEN'])) {
    http_response_code(401);
    $engine->dieWithError(_('Your session has expired. Please refresh the page and try again.'));
}

if (empty($_POST['password']) || mb_strlen($_POST['password']) < 6) {
    $engine->dieWithError(_('Your password needs to be at least six characters long'));
}

if (empty($_POST['username'])) {
    $engine->dieWithError(_('Please type in a login name'));
}

if (preg_match('/[^A-ZÅÄÖa-zåäö0-9_\-]/u', $_POST['username'])) {
    $engine->dieWithError(_('Allowed characters are: a-Ö 0-9 _ -'));
}

if (mb_strlen($_POST['username']) > $engine->cfg->nameMaxLength) {
    http_response_code(400);
    $engine->dieWithError(sprintf(_('Your name is too long. Max allowed length is %s characters'), $engine->cfg->nameMaxLength));
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

if ($user->isFreeName($_POST['username'])) {
    if (!empty($user->info->password)) {
        $engine->dieWithError(_('You are already logged in.'));
    }

    if ($user->info->username != $_POST['username']) {
        $user->updateAccount('username', $_POST['username']);
        $db->q("UPDATE user SET last_name_change = NOW() WHERE id = " . (int)$user->id . " LIMIT 1");
    }
} else {
    $engine->dieWithError(_('This name is already in use. Please choose another one.'));
}

if ($user->changePassword($_POST['password'])) {
    if (!empty($_POST['email'])) {
        $user->changeEmail($_POST['email']);

        include '../../inc/email.class.php';
        $body = _("Welcome to Ylilauta %s,\n\nThis message confirms that this email address was added to your new account and can now be used for password recovery.\n\nWe do not save your email address in a readable form. We can never see it or use it to contact you. We cannot even search user accounts by email addresses. It can only be used for password recovery purposes.\n\nBest regards,\nYlilauta.org");
        $body = sprintf($body, $_POST['username']);
        $email = new Email($_POST['email'], true);
        $email->from($engine->cfg->siteName, $engine->cfg->noreplyEmail);
        $email->subject(_('Welcome to Ylilauta'));
        $email->replyTo($engine->cfg->replyToEmail);
        $email->addPart($email->htmlToPlainText($body), 'text/plain');
        $send = $email->send();
    }

    $session_id = $user->createSession($user->id);
    $user->updateCookie('user', $session_id . $user->id);
} else {
    $engine->dieWithError(_('Unexpected error'));
}