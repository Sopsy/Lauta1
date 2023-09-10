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
include("../../inc/engine.class.php");
new Engine($loadClasses);

if (empty($_POST) || !isset($_POST['msg']) || mb_strlen($_POST['msg']) == 0 || empty($_POST['postId']) || !is_numeric($_POST['postId'])) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    die(_('Bad request'));
}

if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) || !hash_equals($user->csrf_token, $_SERVER['HTTP_X_CSRF_TOKEN'])) {
    http_response_code(401);
    die(_('Your session has expired. Please refresh the page and try again.'));
}

// Mouseover message preview
$post = $posts->getPost($_POST['postId'], true);
if (!$post) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    die(_('Post does not exist'));
}

if ($post['user_id'] != $user->info->id AND !$user->isMod) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    die(_('Permission denied.'));
}

$message = $posts->formatMessage($_POST['msg']);

$postSubject = '';
if (isset($_POST['subject']) && mb_strlen($_POST['subject']) != 0) {
    $thread = $posts->getThread($post['thread_id']);
    $postSubject = trim($posts->removeForbiddenUnicode($_POST['subject']));
    $postSubject = mb_substr($postSubject, 0, $engine->cfg->subjectMaxLength);
    $postSubject = $db->escape($postSubject);
    if ($thread && ($thread['user_id'] == $user->id || $user->isMod)) {
        $q = $db->q("UPDATE thread SET subject = '" . $postSubject . "' WHERE id = " . (int)$thread['id'] . " LIMIT 1");
    }
}

$autobanWords = $db->q("SELECT * FROM word_blacklist");
$autobanWords = $db->fetchAll($autobanWords, 'word');
foreach ($autobanWords AS $autobanWord) {
    if ((stripos($message, $autobanWord) !== false || stripos($postSubject,
                $autobanWord) !== false) && !$user->isMod
    ) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
        die(sprintf(_('Your message contained a blacklisted word (%s)'), $autobanWord));
    }
}

$q = $db->q("SELECT `is_locked` FROM `board` WHERE `url` = '" . $post['url'] . "' LIMIT 1");
$boardinfo = $q->fetch_assoc();

if ($boardinfo['is_locked'] AND !$user->isAdmin) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    die(_('You cannot edit posts on a locked board.'));
}

if (trim($message) != trim($post['message']) || mb_strlen(trim($message)) != 0) {
    $db->q("UPDATE `post` SET `edited` = NOW(), `message` = '" . $db->escape($message) . "' WHERE `id` = '" . $post['id'] . "' LIMIT 1");
}

// Update replies
$posts->clearRepliesByReplyingPost($post['id']);

preg_match_all('/>>([0-9]+)/i', $message, $postReplies);
$postReplies = array_unique($postReplies[1]);
if(!empty($postReplies)) {
    $posts->saveReplies($post['id'], $postReplies);
}