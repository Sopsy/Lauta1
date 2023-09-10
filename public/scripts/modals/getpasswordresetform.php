<?php

// Initialize the board engine
$loadClasses = [
    'cache' => '',
    'db' => '',
    'html' => true,
    'posts' => '',
    'user' => false
];
include '../../inc/engine.class.php';
new Engine($loadClasses);
?>

<form class="async-form" name="sendrecoverycode" action="/scripts/ajax/sendrecoverycode.php" data-e="submitPasswordResetRequest" method="post">
    <label>
        <span><?= _('Username') ?></span>
        <input type="text" name="username" maxlength="1000" required />
    </label>
    <label>
        <span><?= _('Email') ?></span>
        <input type="email" name="email" maxlength="1000" required />
    </label>
    <button class="linkbutton"><?= _('Send recovery code') ?></button>
</form>
<form class="async-form" name="resetpassword" action="/scripts/ajax/changepassword.php" data-e="submitForm" method="post" hidden>
    <input type="hidden" name="username"/>

    <p><?= _('If the email address was correct, we just sent you a recovery key. Please check your email for it.') ?></p>
    <p><?= _('Did not get it? Close this window and try again.') ?></p>

    <label>
        <span><?= _('Recovery key') ?></span>
        <input type="text" name="recoverykey" required />
    </label>
    <label>
        <span><?= _('New password') ?></span>
        <input type="password" name="password" autocomplete="new-password" required />
    </label>
    <label>
        <span><?= _('Confirm password') ?></span>
        <input type="password" name="passwordconfirm" autocomplete="new-password" required />
    </label>
    <button class="linkbutton"><?= _('Change password') ?></button>
</form>
<?php
if($user->useCaptcha()) {
    echo '<p class="protectedbyrecaptcha">' . $engine->getCaptchaText() . '</p>';
}
?>