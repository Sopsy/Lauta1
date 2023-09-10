<?php
if (!defined('ALLOWLOAD')) {
    die();
}

$qb = $db->q("SELECT *, UNIX_TIMESTAMP(edited) AS edited FROM admin_announcement ORDER BY position ASC, edited DESC");
$posts = $db->fetchAll($qb);

echo '<h1>' . _('Bulletin board') . '</h1><div class="news">';

if (count($posts) == 0) {
    echo '<h2>' . _('No announcements') . '</h2>';
}

foreach ($posts AS $post) {
    echo '
	<h2>' . $post['subject'] . ' - <time datetime="' . date(DateTime::ATOM, $post['edited']) . '">'
        . $engine->formatTime($user->language, $user, $post['edited']) .'</time></h2>
	<div class="newstext">' . nl2br($post['text']) . '</div>';
}
echo '</div>';
