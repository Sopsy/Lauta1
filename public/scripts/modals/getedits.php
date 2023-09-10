<?php

// Initialize the board engine
$loadClasses = [
    'cache' => '',
    'db' => '',
    'html' => true,
    'posts' => '',
    'user' => false,
    'fileupload' => '',
    'board' => [false, false, false],
];
include '../../inc/engine.class.php';
new Engine($loadClasses);

if (empty($_POST['msgid']) || !is_numeric($_POST['msgid'])) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    die(_('Bad request'));
}

if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) || !hash_equals($user->csrf_token, $_SERVER['HTTP_X_CSRF_TOKEN'])) {
    http_response_code(401);
    die(_('Your session has expired. Please refresh the page and try again.'));
}

echo '<div class="edits">';

$q = $db->q("SELECT *, INET6_NTOA(ip) AS ip, UNIX_TIMESTAMP(edit_time) AS edit_time FROM `post_edit` WHERE `id` = " . $db->escape((int)$_POST['msgid']));

$postEdits = $db->fetchAll($q);
if (count($postEdits) == 0) {
    echo '<p>' . _('This post has not been edited.') . '</p>';
} else {
    foreach ($postEdits AS $postEdit) {
        echo '<div class="edit"><h4>';
        printf(
            _('Old version at %s'),
           '<time datetime="' . date(DateTime::ATOM, $postEdit['edit_time']) . '">'
           . $engine->formatTime($user->language, $user, $postEdit['edit_time']) .'</time>'
        );
        if ($user->isAdmin) {
            printf(_(', edited by %s'), $postEdit['ip']);
        }
        echo '</h4>';

        echo '<blockquote class="message"><div class="postcontent">';
        $posts->printMessage($postEdit['message_before'], (int)$_POST['msgid'], (int)$_POST['msgid']);
        echo '</div></blockquote>';
        echo '</div>';
    }
}
echo '</div>';