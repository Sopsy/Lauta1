<?php
if (!defined('ALLOWLOAD')) {
    die();
}

if (!empty($_GET['delete']) AND is_numeric($_GET['delete'])) {
    echo '<h1>' . _('Delete ban') . '</h1>';
    if (empty($_GET['csrf_token']) OR !hash_equals($user->csrf_token, $_GET['csrf_token'])) {
        die('<h2>' . _('Invalid token') . '</h2>');
    }

    $delete = $db->escape($_GET['delete']);
    $q = $db->q("DELETE FROM `user_ban` WHERE `id` = '" . $delete . "' LIMIT 1");
    if ($q) {
        echo '<h2>' . _('Ban deleted') . '</h2><p><a class="pure-button" href="?action=managebans">' . _('Return') . '</a></p>';
    } else {
        echo '<h2>' . _('Database error') . '</h2>';
    }
} else {
    if (!isset($_GET['expired'])) {
        $hasExpired = 0;
    } else {
        $hasExpired = 1;
    }

    $q = $db->q("SELECT * FROM `user_ban` WHERE `is_expired` = " . $hasExpired . " ORDER BY start_time DESC");
    echo '<h1>' . _('Manage bans') . '</h1>';

    if ($hasExpired == 0) {
        echo '
	<h2>' . _('All active bans') . '</h2>
	<p><a class="pure-button" href="?action=managebans&expired">' . _('Show expired bans') . '</a></p>';
    } else {
        echo '
	<h2>' . _('Expired bans') . '</h2>
	<p><a class="pure-button" href="?action=managebans">' . _('Show active bans') . '</a></p>';
    }

    echo '
	<table class="pure-table pure-table-striped">
        <thead>
		<tr>
			<th>' . _('Message') . '</th>
			<th>' . _('Banned by') . '</th>
			<th>' . _('Reason') . '</th>
			<th>' . _('Added') . '</th>
			<th>' . _('Ends') . '</th>
			<th>' . _('Actions') . '</th>
		</tr>
        </thead>
        <tbody>';
    while ($ban = $q->fetch_assoc()) {
        echo '
		<tr>
			<td>' . (empty($ban['post_id']) ? '-' : '<a href="' . $engine->cfg->siteUrl . '/scripts/redirect.php?id=' . $ban['post_id'] . '" data-id="' . $ban['post_id'] . '" class="ref">&gt;&gt;' . $ban['post_id'] . '</a>')  . '</td>
			<td>' . htmlspecialchars($user->modNameById($ban['banned_by'])) . '</td>
            <td>' . ($user->banReasons[$ban['reason']] ?? _('Unknown'))
                . (!empty($ban['reason_details']) ? ' (' . htmlspecialchars($ban['reason_details']) . ')' : '')
                . '</td>
			<td><time datetime="' . date(DateTime::ATOM, strtotime($ban['start_time'])) . '">'
                . $engine->formatTime($user->language, $user, strtotime($ban['start_time'])) .'</time></td>
			<td><time datetime="' . date(DateTime::ATOM, strtotime($ban['end_time'])) . '">'
                . $engine->formatTime($user->language, $user, strtotime($ban['end_time'])) .'</time></td>
			<td><a class="pure-button" href="?action=managebans&delete=' . $ban['id'] . '&csrf_token=' . $user->csrf_token . '" data-e="confirm">' . _('Delete') . '</a></td>
		</tr>';
    }
    echo '
        </tbody>
	</table>';
}
