<?php

// Initialize the board engine
$loadClasses = [
    'cache' => '',
    'db' => '',
    'html' => true,
    'user' => false,
];
include("inc/engine.class.php");
new Engine($loadClasses);

if (!empty($_POST['clear']) && hash_equals($user->csrf_token, $_POST['clear'])) {
    $user->updatePreferences('custom_css', '');
    header("Location: /");
} else {
    echo '<form action="clearcss.php" method="post"><input type="hidden" name="clear" value="' . $user->csrf_token . '" /><input type="submit" value="' . _('Clear Custom CSS') . '" /></form>';
}
