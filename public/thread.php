<?php

// Initialize the board engine
$loadClasses = [
    'cache' => '',
    'db' => '',
    'html' => '',
    'user' => $_GET['board'],
    'board' => [$_GET['board'], $_GET['thread'], false],
    'posts' => '',
];
include("inc/engine.class.php");
new Engine($loadClasses);

// Basic checks
if (!$engine->user_can_access_board() || !$engine->user_can_access_thread()) {
    if (empty($board->threads)) {
        $q = $db->q('SELECT delete_reason FROM thread_deleted WHERE id = ' . (int)$_GET['thread'] . ' LIMIT 1');
        if ($q->num_rows == 1) {
            $deleteReason = $q->fetch_assoc()['delete_reason'];
            $reasons = [
                0 => _('Unknown'),
                1 => _('Automatic due to inactivity'),
                2 => _('Deleted by the user who created the thread'),
                3 => _('Deleted by a moderator'),
                4 => _('Moderator deleted multiple posts from this user (e.g. because of spamming)'),
            ];
            $engine->return_not_found(410, sprintf(_('Reason for deletion: %s'), $reasons[$deleteReason]));
        }
    }
    $engine->return_not_found(410);
}

$thread = $board->threads[0];
if ($thread['board_id'] != $board->info['boardid']) {
    // Thread does not exist on this board
    $engine->redirectExit($engine->cfg->siteUrl . '/' . $board->getBoardUrl($thread['board_id']) . '/' . $thread['id']);
}

// Set last seen id for followed threads
if (!empty($thread['replies'])) {
    $last_reply_id = end($thread['replies'])['id'];
    reset($thread['replies']);
} else {
    $last_reply_id = 0;
}

$user->followed_update_last_seen_reply($thread['id'], $last_reply_id);

// Clear unread_count from followed threads
$user->followed_clear_unread_count($thread['id']);

// Update readcount
$posts->storeThreadView($user->id, (int)$thread['id']);

// Print the thread
$html->printHeader($thread['subject'] . ' - ' . $board->info['boardname'] . ' | ' . $engine->cfg->siteName, true, $thread['id']);

$html->printSidebar(true, true);
echo '<div id="right" class="thread-page">';

$html->printBoardHeader();

$html->printNavigationBar(true, false, false, [$board->info['url'] . '/', $thread['id']],
    [$board->info['boardname'], $thread['subject']]);

// ads
$html->print_ad($board->info['url'], 4);

include('announcement.php');

if ($user->getPreferences('reply_form_at_top')) {
    $html->printPostForm($thread['id']);
}

echo '<div class="threads style-replies">';
$html->printThread($thread, true);
echo '</div>';

if (!$user->getPreferences('reply_form_at_top')) {
    $html->printPostForm($thread['id']);
}

$html->print_ad($board->info['url'], 7);

$html->printNavigationBar(false, false, false, [$board->info['url'] . '/', $thread['id']],
    [$board->info['boardname'], $thread['subject']]);

?>
</div>
