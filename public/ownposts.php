<?php
// Initialize the board engine
$loadClasses = [
    'cache' => '',
    'db' => '',
    'html' => '',
    'posts' => '',
    'fileupload' => '',
    'user' => '',
];
include("inc/engine.class.php");
new Engine($loadClasses);

$html->printHeader();
$html->printSidebar();

if ((int)$user->id === 0) {
    die();
}

$threads = $db->q('SELECT post.id, thread.id as thread_id, board.url, thread.subject, board.name
    FROM thread
    LEFT JOIN post ON post.thread_id = thread.id AND post.op_post = 1
    LEFT JOIN board ON board.id = thread.board_id
    WHERE thread.user_id = ' . (int)$user->id);
$replies = $db->q('SELECT id FROM post WHERE op_post = 0 AND user_id = ' . (int)$user->id);
$upvotes = $db->q('SELECT post_id FROM post_upvote WHERE user_id = ' . (int)$user->id);

echo '<div id="right" class="preferences">

<h2>' . _('Threads you have made') . '</h2>';
$i = 0;
while ($thread = $threads->fetch_assoc()) {
    echo '<a href="/' . $thread['url'] . '/' . $thread['thread_id'] . '"><b>' . $thread['name'] . ':</b> ' . $thread['subject'] . '</a><br/>';
    ++$i;
}
echo '<p>' . sprintf(_('Total: %d threads'), $i) . '</p>';

echo '<h2>' . _('Replies you have sent') . '</h2>';
$i = 0;
while ($reply = $replies->fetch_assoc()) {
    echo '<a class="ref" data-id="' . $reply['id'] . '" href="/scripts/redirect.php?id=' . $reply['id'] . '">&gt;&gt;' . $reply['id'] . '</a> ';
    ++$i;
}
echo '<p>' . sprintf(_('Total: %d replies'), $i) . '</p>';

echo '<h2>' . _('Posts you have upvoted') . '</h2>';
$i = 0;
while ($upvote = $upvotes->fetch_assoc()) {
    echo '<a class="ref" data-id="' . $upvote['post_id'] . '" href="/scripts/redirect.php?id=' . $upvote['post_id'] . '">&gt;&gt;' . $upvote['post_id'] . '</a> ';
    ++$i;
}
echo '<p>' . sprintf(_('Total: %d posts'), $i) . '</p>';

echo '
    <h2>' . _('Delete data') . '</h2>
    <form class="async-form" action="/scripts/ajax/deletedata.php" method="post" data-e="userDeleteData">
        <span class="block"' . (empty($user->info->password) ? ' hidden' : '') . '>
            <label class="fixedwidth" for="deletionpassword">' . _('Password') . '</label>
            <input type="password" id="deletionpassword" name="deletionpassword" autocomplete="new-password" />
        </span>
        <span class="block">
            <label><input type="checkbox" id="delete-threads" /> ' . _('Delete all threads I have made') . '</label>
        </span>
        <span class="block">
            <label><input type="checkbox" id="delete-replies" /> ' . _('Delete all thread replies I have sent (excluding your own threads)') . '</label>
        </span>
        <span class="block">
            <label><input type="checkbox" id="delete-upvotes" /> ' . _('Delete all upvotes I have given') . '</label>
        </span>
        <span class="block">
            <button class="linkbutton" id="delete-data">' . _('Delete') . '</button>
        </span>
    </form>
</div>';
