<?php

$loadClasses = [
    'cache' => '',
    'db' => '',
    'html' => true,
    'user' => false,
];
include("../../inc/engine.class.php");
new Engine($loadClasses);


if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) || !hash_equals($user->csrf_token, $_SERVER['HTTP_X_CSRF_TOKEN'])) {
    $engine->redirectExit($engine->cfg->siteUrl . '/preferences');
}

if (empty($_POST['hideboards'])) {
    // Hide poster names
    if (!empty($_POST['hide_names'])) {
        $names = str_replace("\r", '', $_POST['hide_names']);
        $names = explode("\n", $names);
        $user->setHiddenNames($names);
    } else {
        $user->setHiddenNames();
    }

    if (!empty($_POST['language'])) {
        $user->setLanguage($_POST['language']);
    }


    foreach (['hide_images', 'auto_follow', 'auto_follow_reply', 'notification_from_thread_replies',
                 'notification_from_followed_replies', 'notification_from_post_replies',
                 'notification_from_post_upvotes', 'follow_show_floatbox', 'hide_ads'] AS $checkbox) {
        if (empty($_POST[$checkbox])) {
            $_POST[$checkbox] = 0;
        } else {
            $_POST[$checkbox] = 1;
        }
    }

    // Other settings
    foreach ($_POST as $key => $val) {
        if (!$user->preferenceExists($key)) {
            continue;
        }

        $update = $user->updatePreferences($key, $val);

        if (!$update) {
            $engine->dieWithError(_('Error saving preferences'));
        }
    }
    $goto = 'site';
} else {
    $hide_boards = [];
    if (!empty($_POST['hideboard'])) {
        foreach ($html->getBoardList(true) as $board_list) {
            if (in_array($board_list['boardid'], $_POST['hideboard'])) {
                $hide_boards[] = $board_list['boardid'];
            }
        }
    }
    $user->setHiddenBoards($hide_boards);
    $goto = 'boards';
}

$engine->redirectExit($engine->cfg->siteUrl . '/preferences?' . $goto);
