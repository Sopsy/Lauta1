<?php

$loadClasses = [
    'cache' => '',
    'db' => '',
    'user' => false,
];
include '../../inc/engine.class.php';
new Engine($loadClasses);

if (!isset($user)) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    die(_('Please refresh this page and try again.'));
}

if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) || !hash_equals($user->csrf_token, $_SERVER['HTTP_X_CSRF_TOKEN'])) {
    http_response_code(401);
    die(_('Your session has expired. Please refresh the page and try again.'));
}

if (empty($_POST['password'])) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    die(_('Invalid password'));
}

if (!password_verify($_POST['password'], $user->info->password)) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    die(_('Invalid password'));
}

// Maybe change username
if (!empty($_POST['username']) && $user->info->username !== $_POST['username']) {
    if (mb_strlen($_POST['username']) > $engine->cfg->nameMaxLength) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
        die(sprintf(_('Your name is too long. Max allowed length is %s characters'), $engine->cfg->nameMaxLength));
    }

    if (preg_match('/[^A-ZÅÄÖa-zåäö0-9_\-]/u', $_POST['username'])) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
        die(_('Allowed characters are: a-Ö 0-9 _ -'));
    }

    if ($user->info->last_name_change > time() - 604800) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 403 Permission Denied');
        die(_('You have changed your username recently, so you can\'t do it again yet.'));
    }

    $_POST['username'] = preg_replace('/\s\s+/', ' ', trim($_POST['username']));

    if ($user->isFreeName($_POST['username'])) {
        $user->updateAccount('username', $_POST['username']);

        $db->q("UPDATE user SET last_name_change = NOW() WHERE id = " . (int)$user->id . " LIMIT 1");
    } else {
        header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
        echo _('This name is already in use. Please choose another one.');
    }
}

if (!empty($_POST['email'])) {
    $user->changeEmail($_POST['email']);
    include '../../inc/email.class.php';
    $body = _("Hi %s,\n\nThis message confirms that this email address was added to your account and can now be used for password recovery.\n\nWe do not save your email address in a readable form. We can never see it or use it to contact you. We cannot even search user accounts by email addresses. It can only be used for password recovery purposes.\n\nBest regards,\nYlilauta.org");
    $body = sprintf($body, $_POST['username']);
    $email = new Email($_POST['email'], true);
    $email->from($engine->cfg->siteName, $engine->cfg->noreplyEmail);
    $email->subject(_('Email address added to account'));
    $email->replyTo($engine->cfg->replyToEmail);
    $email->addPart($email->htmlToPlainText($body), 'text/plain');
    $send = $email->send();
}

