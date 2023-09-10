<?php

$loadClasses = [
    'cache' => '',
    'db' => '',
    'html' => '',
    'user' => '',
];
include("inc/engine.class.php");
new Engine($loadClasses);

if (!$user->isAdmin) {
    $engine->redirectExit('/404');
}

$html->printHeader(_('Password reset'));

if (empty($_POST)) {
    echo '
<h1>' . _('Password reset') . '</h1>

<p>' . _('You can reset a forgotten password with this form.') . '</p>
<p>' . _('To make sure, the reset is not going for a wrong account, you are required to input the login name and UID of the account.') . '</p>
<form action="' . $engine->cfg->siteUrl . '/resetpassword.php" method="post" class="banappeal">
<fieldset>
	<label for="username">' . _('Login name') . '</label>
	<input type="text" name="username" id="username">
	<label for="uid">' . _('UID') . '</label>
	<input type="text" name="uid" id="uid">

	<input type="submit" value="' . _('Submit') . '" />
</fieldset>
</form>
';
} else {
    if (!isset($_POST['username'])) {
        $_POST['username'] = '';
    }
    if (!isset($_POST['uid'])) {
        $_POST['uid'] = '';
    }
    $username = $db->escape($_POST['username']);
    $uid = $db->escape($_POST['uid']);
    $q = $db->q("SELECT `id`, `username` FROM user WHERE `username` = '" . $username . "' AND `id` = '" . $uid . "' LIMIT 1");

    if ($q->num_rows != 0) {
        $uid = $q->fetch_assoc();
        $passwordPlain = $engine->randString(8);

        $user->changePassword($passwordPlain, $uid['id']);
        echo '<h1>' . _('Password changed') . '</h1><p>' . sprintf(_('New password for user %s is %s'),
                $uid['username'], $passwordPlain) . '</p>';
    } else {
        echo '<h1>' . _('Password not changed!') . '</h1><p>' . _('User not found or invalid UID') . '</p>';
    }
}

$html->printFooter();
