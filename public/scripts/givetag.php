<?php

// Initialize the board engine
$loadClasses = [
    'cache' => '',
    'db' => '',
    'user' => '',
    'html' => '',
];
include("../inc/engine.class.php");
new Engine($loadClasses);

if (!$user->isAdmin) {
    die('Go away');
}

$html->printHeader('Gibe tag');

$tags = [
    'unique_snowflake',
    'advent_burger',
    'anonymous_burger',
    'seal_of_ylilauta',
];

if (empty($_POST)) {
    echo '
<form action="' . $engine->cfg->siteUrl . '/scripts/givetag.php" method="post" class="banappeal">
<fieldset>
	<h2>' . _('UID') . '</h2>
	<input type="text" name="uid" id="uid">
	
	<h2>' . _('Tag') . '</h2>';

    foreach ($tags as $tag) {
        echo '<label><input type="checkbox" name="tags[]" value="' . $tag . '"> ' . $engine->cfg->postTags[$tag]['name'] . '</label><br>';
    }

    echo '
    <br>
	<input type="submit" value="' . _('Submit') . '">
</fieldset>
</form>
';
} else {
    if (empty($_POST['uid']) || (int)$_POST['uid'] == 0) {
        die('Invalid UID');
    }
    if (empty($_POST['tags']) || !is_array($_POST['tags'])) {
        die('Invalid tags');
    }

    $uid = $db->escape(trim($_POST['uid']));
    $q = $db->q("SELECT `id` FROM user WHERE `id` = '" . $uid . "' LIMIT 1");
    if ($q->num_rows == 0) {
        die('Invalid user');
    }

    echo '<h1>Tags:</h1>';
    foreach ($_POST['tags'] as $tag) {
        if (!in_array($tag, $tags)) {
            echo 'Skipped invalid 1: ' . $tag . '<br>';
            continue;
        }
        $tagKey = $db->escape($tag);
        if (empty($tagKey)) {
            echo 'Skipped invalid 2: ' . $tag . '<br>';
            continue;
        }

        $q = $user->unlockTag($tagKey, $uid);

        if ($q) {
            echo 'Added: ' . $engine->cfg->postTags[$tag]['name'] . '<br>';
        } else {
            echo 'Not added: ' . $engine->cfg->postTags[$tag]['name'] . '<br>';
        }
    }
}
