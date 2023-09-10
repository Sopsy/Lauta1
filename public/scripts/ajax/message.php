<?php

// Initialize the board engine
$loadClasses = [
    'cache' => '',
    'db' => '',
    'html' => true,
    'posts' => '',
    'user' => [false, true],
    'fileupload' => '',
    'board' => [false, false, false],
];
include("../../inc/engine.class.php");
new Engine($loadClasses);

if (!isset($_POST['postId']) || !is_numeric($_POST['postId'])) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    die(_('Bad request'));
}

if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) || !hash_equals($user->csrf_token, $_SERVER['HTTP_X_CSRF_TOKEN'])) {
    http_response_code(401);
    die(_('Your session has expired. Please refresh the page and try again.'));
}

// Mouseover message preview
$post = $posts->getPost((int)$_POST['postId'], true);
if (!$post) {
    if ($user->isMod) {
        $id = $db->escape($_POST['postId']);
        $q = $db->q("SELECT a.*, UNIX_TIMESTAMP(time_deleted) AS time_deleted, UNIX_TIMESTAMP(time) AS time
            FROM `post_deleted` a
            WHERE a.`id` = " . (int)$id . "
            LIMIT 1");

        if ($q->num_rows != 0) {
            $post = $q->fetch_assoc();
            echo '<div class="padded">
            <b>' . _('Sent') . ':</b> <time datetime="' . date(DateTime::ATOM, $post['time']) . '">'
                . $engine->formatTime($user->language, $user, $post['time']) .'</time><br />
            <b>' . _('Deleted') . ':</b> <time datetime="' . date(DateTime::ATOM, $post['time_deleted']) . '">'
                . $engine->formatTime($user->language, $user, $post['time_deleted']) .'</time><br />
            <b>' . _('Message') . '</b><br/><br/>';
            $posts->printMessage($post['message'], $post['id'], $post['id']);
            echo '</div>';
            die ();
        } else {
            die(_('Post does not exist'));
        }
    } else {
        die(_('Post does not exist'));
    }
}

$board->getBoardInfo($post['board'], false, false);

if ($board->info['url'] == 'bilderberg' AND !$user->hasGoldAccount) {
    die(_('This message is only visible to users with a Gold account.'));
}
if ($board->info['url'] == 'platina' AND !$user->hasPlatinumAccount) {
    die(_('Post does not exist'));
}
if ($post['gold_hide'] AND !$user->hasGoldAccount) {
    die(_('This message is only visible to users with a Gold account.'));
}

if (!empty($_POST['msgonly']) AND $_POST['msgonly'] == 'true') {
    if (empty($_POST['nohtml'])) {
        echo '<p>';
        $posts->printMessage($post['message'], $post['id'], $post['op_post_id']);
        echo '</p>';
    } else {
        header("Content-Type: text/plain");
        echo $post['message'];
    }
} else {
    $html->printPost($post, true, true, true);
}
