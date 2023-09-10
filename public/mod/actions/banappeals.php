<?php
if (!defined('ALLOWLOAD')) {
    die();
}

echo '<h1>' . _('Ban appeals') . '</h1>';

if (!empty($_GET['accept']) AND is_numeric($_GET['accept'])) {
    $id = $db->escape($_GET['accept']);
    $q = $db->q("UPDATE `user_ban` SET `appeal_checked` = 1, `is_expired` = 1 WHERE `id` = " . (int)$id . " LIMIT 1");
    $qb = $db->q("DELETE FROM `ban_ip` WHERE ban_id = " . (int)$id);
    if (!$q) {
        echo '<h2>' . _('Database error') . '</h2>';
    }
} elseif (!empty($_GET['deny']) AND is_numeric($_GET['deny'])) {
    $id = $db->escape($_GET['deny']);
    $q = $db->q("UPDATE `user_ban` SET `appeal_checked` = 1 WHERE `id` = " . (int)$id . " LIMIT 1");
    if (!$q) {
        echo '<h2>' . _('Database error') . '</h2>';
    }
}

$q = $db->q("SELECT * FROM `user_ban` WHERE `is_appealed` = 1 AND `appeal_checked` = 0 ORDER BY `id` DESC");
if ($q->num_rows >= 1) {
    echo '
	<table class="pure-table pure-table-striped">
        <thead>
		<tr>
			<th>' . _('Banned by') . '</th>
			<th>' . _('Added') . '</th>
			<th>' . _('Ends') . '</th>
			<th>' . _('Reason') . '</th>
			<th>' . _('Actions') . '</th>
		</tr>
        </thead>
        <tbody>';
    while ($ban = $q->fetch_assoc()) {
        $post = $posts->getPost($ban['post_id'], true);
        if (!$post) {
            $post = $db->q("SELECT a.id, a.message, b.name as boardname
                FROM `post_deleted` a
                LEFT JOIN thread t ON a.thread_id = t.id
                LEFT JOIN thread_deleted td ON a.thread_id = td.id
                LEFT JOIN `board` b ON b.`id` = COALESCE(t.board_id, td.board_id)
                WHERE a.`id` = " . (int)$ban['post_id'] . "
                LIMIT 1");
            $post = $post->fetch_assoc();
        }
        echo '
		<tr>
			<td>' . htmlspecialchars($user->modNameById($ban['banned_by'])) . '</td>
			<td>' . date('d.m.Y H:i:s', strtotime($ban['start_time'])) . '</td>
			<td>' . date('d.m.Y H:i:s', strtotime($ban['end_time'])) . '</td>
			<td>' . ($user->banReasons[$ban['reason']] ?? _('Unknown'))
                . (!empty($ban['reason_details']) ? ' (' . htmlspecialchars($ban['reason_details']) . ')' : '')
                . '</td>
			<td>
			    <a class="pure-button pure-button-primary" href="?action=banappeals&accept=' . $ban['id'] . '">' . _('Accept') . '</a>
                <a class="pure-button" href="?action=banappeals&deny=' . $ban['id'] . '">' . _('Reject') . '</a>
            </td>
		</tr>
		<tr>
			<td colspan="5">
			    <strong>' . _('Appeal') . '</strong>: ' . htmlspecialchars($ban['appeal_text']);
        if (!empty($post)) {
            echo '<br />
			    <strong>' . _('Post') . ' (' . $post['boardname'] . '):</strong>
			    <a data-id="' . $post['id'] . '" href="/scripts/redirect.php?id=' . $post['id'] . '" class="ref backlink">&gt;&gt;' . $post['id'] . '</a>';
        }
        echo '
			</td>
		</tr>';
    }
    echo '
        </tbody>
	</table>';

} else {
    echo '<h2>' . _('No unchecked ban appeals') . '</h2>';
}


