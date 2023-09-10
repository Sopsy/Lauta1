<?php
// Initialize the board engine
$loadClasses = [
    'cache' => '',
    'db' => '',
    'posts' => '',
    'user' => '',
];
include("../../inc/engine.class.php");
new Engine($loadClasses);

if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    die(_('Invalid post'));
}

$post = $posts->getPost($_POST['id'], true);
if (!$post) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    die(_('Post does not exist'));
}

if ($posts->fileIsProcessing((int)$post['fileid'])) {
    die('<div class="file"><em>' . _('This file is being processed...') . '</em></div>');
}

$tag = 'video';

$attrs = ' playsinline autoplay controls';
if ($post['has_sound'] == 0 && $post['extension'] != 'm4a') {
    $attrs .= ' muted loop';
}

$fileName = str_pad(base_convert($post['fileid'], 10, 36), 5, '0', STR_PAD_LEFT);

$file = $engine->cfg->videosUrl . '/' . $fileName . '.' . $post['extension'];
$poster = '';
if ($post['extension'] == 'mp4') {
    $poster = ' poster="' . $engine->cfg->filesUrl . '/' . $fileName . '.jpg"';
}

echo '<video ' . $attrs . $poster . '>';
echo '<source src="' . $file . '">';
echo '</video>';