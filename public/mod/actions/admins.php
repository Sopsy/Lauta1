<?php
if (!defined('ALLOWLOAD')) {
    die();
}

echo '
<h1 class="bottommargin">' . _('Moderator list') . '</h1>
<table class="modtable" border="1">
<tr>
    <th>' . _('User (ID)') . '</th>
    <th>' . _('Class') . '</th>
    <th>' . _('Log rows (last month)') . '</th>
    <th>' . _('(last 6 months)') . '</th>
    <th>' . _('Last active') . '</th>
</tr>';

$q = $db->q("SELECT a.*, UNIX_TIMESTAMP(a.last_active) AS last_active,
    (SELECT COUNT(*) FROM admin_log WHERE user_id = a.id AND time >= DATE_SUB(NOW(), INTERVAL 1 MONTH)) AS log_count,
    (SELECT COUNT(*) FROM admin_log WHERE user_id = a.id AND time >= DATE_SUB(NOW(), INTERVAL 6 MONTH)) AS log_count_long
    FROM user a
    WHERE user_class != 0
    GROUP BY a.id
    ORDER BY a.user_class ASC, a.id ASC");
$admins = $db->fetchAll($q);
foreach ($admins AS $admin) {
    if ($admin['user_class'] == 1) {
        $class = _('Admin');
    } elseif ($admin['user_class'] == 2) {
        $class = _('Super moderator');
    } else {
        $class = _('Moderator');
    }

    echo '
    <tr>
        <td><a href="' . $engine->cfg->siteUrl . '/mod/index.php?action=modlog&modid=' . $admin['id'] . '">' . $admin['username'] . ' (' . $admin['id'] . ')</a></td>
        <td>' . $class . '</td>
        <td>' . $admin['log_count'] . '</td>
        <td>' . $admin['log_count_long'] . '</td>
        <td>' . date("d.m.Y H:i:s", $admin['last_active']) . '</td>
        </td>
    </tr>';
}
echo '</table>';


