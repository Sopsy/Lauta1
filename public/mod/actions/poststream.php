<?php
if (empty($_POST)) {
    echo '<span id="trigger"></span><script type="module" src="' . $engine->cfg->staticUrl . '/js/PostStream.js"></script><div id="content"><div id="poststream"></div></div>';
    die();
}
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

if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) || !hash_equals($user->csrf_token, $_SERVER['HTTP_X_CSRF_TOKEN'])) {
    $engine->dieWithError(_('Bad request'));
}

if (!$user->isMod) {
    $engine->dieWithError(_('Unauthorized'));
}


$lastPost = $_POST['id'];
if ($lastPost == 0) {
    $qb = $db->q('SELECT `id` FROM `post` ORDER BY `id` DESC LIMIT 100');
    $postIds = $db->fetchAll($qb, 'id');
} else {
    $qb = $db->q('SELECT `id` FROM `post` WHERE `id` > ' . $db->escape($lastPost) . ' ORDER BY id ASC LIMIT 100');
    $postIds = array_reverse($db->fetchAll($qb, 'id'));
}
$postlist = [];
$user->styleOverride = true;
foreach ($postIds as $postId) {
    ob_start();
    $post = $posts->getPost($postId, true);
    if (!$post) {
        continue;
    }
    $isThread = $post['op_post'];
    echo '<div class="thread ';
    if ($isThread) {
        echo 'highlight';
    }
    $thread = $posts->getThread($post["thread_id"]);

    echo '">';
    $board->getBoardInfo($post['board'], false);
    echo '<span class="thread-boardinfo"><a href="/' . $board->info['url'] . '/">' . $board->info['boardname'] . '</a> > <a ';
    if (!$isThread) {
        echo 'class="ref"  data-id="' . $thread['id'] . '"';
    }
    echo 'href="/' . $board->info['url'] . '/' . $thread['id'] . '">' . htmlspecialchars($thread['subject']) . '</a>';
    echo '</span>';
    $html->printPost($post, true, false);
    echo '</div>';
    $postlist[] = ob_get_clean();
}
if (isset($postIds[0])) {
    $id = $postIds[0];
} else {
    $id = $lastPost;
}
echo json_encode(['html' => $postlist, 'id' => $id]);