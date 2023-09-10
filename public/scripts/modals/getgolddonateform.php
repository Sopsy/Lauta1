<?php

$loadClasses = [
    'cache' => '',
    'db' => '',
    'user' => false,
];
include("../../inc/engine.class.php");
new Engine($loadClasses);

if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    die(_('Bad request'));
}
$_POST['id'] = (int)$_POST['id'];

if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) || !hash_equals($user->csrf_token, $_SERVER['HTTP_X_CSRF_TOKEN'])) {
    http_response_code(401);
    die(_('Your session has expired. Please refresh the page and try again.'));
}

$unused_keys = $user->getUnusedGoldKeys(true);

?>
<p><?= _('Choose the Gold account key you wish to donate to this user.') ?></p>
<p><?= sprintf(_('Selected post: %s'), '<span class="ref" data-id="' . $_POST['id'] . '">&gt;&gt;' . $_POST['id'] . '</span>') ?></p>


<div class="gold-keys-list">
<?php if(empty($unused_keys)) : ?>
    <p><?= _('You have no Gold account keys available to be donated.') ?></p>
    <a href="/gold" class="linkbutton"><?= _('Purchase Gold account keys') ?></a>
<?php else: ?>
    <?php foreach($unused_keys as $key) : ?>
        <div class="gold-key">
            <span><?= htmlspecialchars($key['key']) ?></span>
            <button class="linkbutton" data-key="<?= htmlspecialchars($key['key']) ?>" data-id="<?= $_POST['id'] ?>"><?= _('Donate') ?></button>
            <span><?= sprintf(_('Length: %s'), $user->goldLengthToHumanReadable($key['length'])) ?></span>
        </div>
    <?php endforeach ?>
<?php endif ?>
</div>

<h3><?= _('Terms and conditions') ?></h3>
<p><?=  _('The donation may not reach the person who sent the message if they are not using the same user account anymore.') ?></p>
<p><?= _('If the user account with which the message was sent with does not exist at all, the Gold account key is returned to your user account.') ?></p>
<p><?= _('Each individual Gold account key can only be donated once.') ?></p>
<p><?= _('Other terms apply, please read the terms and conditions from the regular Gold account purchase page.') ?></p>
<p><?= _('You agree to these terms by continuing.') ?></p>