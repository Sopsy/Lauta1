<?php
if (empty($responseCode)) {
    http_response_code(404);
}

if (empty($db)) {
    $loadClasses = [
        'cache' => '',
        'db' => '',
        'user' => '',
        'html' => '',
    ];
    include("inc/engine.class.php");
    new Engine($loadClasses);
} else {
    $engine = $this;
}

if (!empty($responseCode) && $responseCode == 410) {
    $html->printHeader(_('410 Gone') . ' | ' . $engine->cfg->siteName);
} else {
    $html->printHeader(_('404 Not Found') . ' | ' . $engine->cfg->siteName);
}
$html->printSidebar();

echo '<div id="right" class="notfound">';

$images = glob($engine->cfg->staticDir . '/img/404images/*');
$image = $images[array_rand($images)];
$image = str_replace($engine->cfg->staticDir, '', $image);
$displayImage = $engine->cfg->staticUrl . $image;

echo '<div>';

if (!empty($additionalInfo) && $responseCode == 410) {
    echo '<h3>' . _('410 Gone') . ' - ' . _('The thread has been deleted') . '</h3>
        <p>' . $additionalInfo . '</p>';
} else {
    echo '<h3>' . _('404 Not Found') . ' - ' . _('The page or file you were looking for does not exist') . '</h3>
        <p>' . _('This thread has been deleted or it never even existed.') . '</p>';
}

if (!empty($board)) {
    $url = empty($board->info['url']) ? '/' : '/' . $board->info['url'] . '/';
    echo '<a class="error-page-return-link" href="' . $url . '">' . _('Return to board') . '</a>';
}

echo '
    <img class="error-page-image" src="' . $displayImage . '" alt="404" />
</div>';

echo '</div>';
