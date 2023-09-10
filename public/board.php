<?php

// There should always be a page number
if (empty($_GET['page']) OR !is_numeric($_GET['page'])) {
    $_GET['page'] = 1;
}
if (empty($_GET['board'])) {
    $_GET['board'] = false;
}
if (empty($_GET['style'])) {
    $_GET['style'] = false;
}

// Initialize the board engine
$loadClasses = [
    'cache' => '',
    'db' => '',
    'html' => '',
    'posts' => '',
    'fileupload' => '',
    'user' => $_GET['board'],
    'board' => [$_GET['board'], $_GET['page'], true],
];
include("inc/engine.class.php");
new Engine($loadClasses);

// Basic checks
if (!$engine->user_can_access_board()) {
    $engine->return_not_found();
}

$html->printHeader($board->info['boardname'] . ' | ' . $engine->cfg->siteName);
$html->printSidebar();

echo '<div id="right" class="board">';
$html->printBoardHeader();

$html->printPostForm();
$html->printNavigationBar(true, $_GET['page'], $board->info['pageCount'], [$board->info['url'] . '/'],
    [$board->info['boardname']]);

$html->print_ad($board->info['url'], 4);

include('announcement.php');

$threadCount = count($board->threads);
if ($threadCount == 0) {
    echo '<div class="infobar">' . _('Sorry, there are no threads on this board.') . '</div>';
} else {
    echo '<div class="threads ' . $user->getDisplayStyle() . '">';

    $i = 0;
    foreach ($board->threads as $thread) {

        if ($i == floor(count($board->threads)/3)) {
            $html->print_ad($board->info['url'], 5);
        }
        if ($i == ceil(count($board->threads)/3*2)) {
            $html->print_ad($board->info['url'], 6);
        }

        $html->printThread($thread);
        ++$i;
    }
    echo '</div>';
}

$html->print_ad($board->info['url'], 7);

$html->printNavigationBar(false, $_GET['page'], $board->info['pageCount'], [$board->info['url'] . '/'],
    [$board->info['boardname']]);

echo '</div>';

