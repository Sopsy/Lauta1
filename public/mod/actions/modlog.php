<?php
if (!defined('ALLOWLOAD')) {
    die();
}

if (isset($_GET['modid']) && is_numeric($_GET['modid'])) {
    $modId = " WHERE admin_log.user_id = " . (int)$_GET['modid'];
} else {
    $modId = '';
}

$qb = $db->q("SELECT admin_log.*, UNIX_TIMESTAMP(admin_log.time) AS time, INET6_NTOA(admin_log.ip) AS ip, board.name AS boardname
    FROM admin_log
    LEFT JOIN board ON board.id = admin_log.board_id
    " . $modId . "
    ORDER BY admin_log.time DESC
    LIMIT 1000");
$entries = $db->fetchAll($qb);

echo '<h1>' . _('Moderation log') . '</h1>';

if (!empty($modId)) {
    echo '
	<p>' . sprintf(_('Viewing the moderation log of %s'), htmlspecialchars($user->modNameById($entries[0]['user_id']))) . '</p>
	<p><a class="pure-button" href="' . $engine->cfg->siteUrl . '/mod/index.php?action=modlog">' . _('Show all') . '</a></p>';
}

echo '
<table class="pure-table pure-table-striped">
<thead><tr>
    <th>' . _('Moderator') . '</th>
    <th>' . _('Action') . '</th>
    <th>' . _('Board') . '</th>
    <th>' . _('Thread') . '</th>
    <th>' . _('Message') . '</th>
    <th>' . _('Information') . '</th>
    <th>' . _('Timestamp') . '</th>
    <th>' . _('IP-address') . '</th>
</tr></thead><tbody>';

$modlogActions = [
    0 => "N/A",
    1 => _('Deleted a message'),
    2 => _('Banned an user'),
    3 => _('Sent a message as an admin'),
    6 => _('Invalid login'),
    7 => _('Stickied a thread'),
    8 => _('Unstickied a thread'),
    9 => _('Locked a thread'),
    10 => _('Unlocked a thread'),
    11 => _('Deleted a thread'),
    12 => _('Deleted a post'),
    14 => _('Deleted a file from a message'),
    18 => _('Changed blacklisted words'),
    22 => _('Created a Gold account key'),
    23 => _('Moved a thread'),
    25 => _('Checked a report'),
    26 => _('Marked all reports as checked'),
    27 => _('Logged in'),
    28 => _('Locked an user'),
    29 => _('Unlocked an user'),
    30 => _('Enabled antispam'),
    31 => _('Disabled antispam'),
];

foreach ($entries AS $entry) {
    if (empty($entry['username'])) {
        $entry['username'] = "(tuntematon)";
    }
    echo '
    <tr>
        <td><a href="' . $engine->cfg->siteUrl . '/mod/index.php?action=modlog&modid=' . $entry['user_id'] . '">' . htmlspecialchars($user->modNameById($entry['user_id'])) . '</a></td>
        <td>' . htmlspecialchars($modlogActions[$entry['action_id']]) . '</td>
        <td>' . htmlspecialchars($entry['boardname']) . '</td>
        <td>' . $entry['thread_id'] . '</td>
        <td>' . (empty($entry['post_id']) ? '-' : '<a href="' . $engine->cfg->siteUrl . '/scripts/redirect.php?id=' . $entry['post_id'] . '" data-id="' . $entry['post_id'] . '" class="ref">&gt;&gt;' . $entry['post_id'] . '</a>')  . '</td>
        <td>' . htmlspecialchars(str_replace('\n', '', $entry['custom_info'])) . '</td>
        <td><time datetime="' . date(DateTime::ATOM, $entry['time']) . '">'
        . $engine->formatTime($user->language, $user, $entry['time']) .'</time></td>
        <td>' . $entry['ip'] . '</td>
    </tr>';
}
echo '</tbody></table>';
