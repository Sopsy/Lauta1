<?php
if (!defined('ALLOWLOAD')) {
    die();
}
echo '
	<h1>' . _('Reported messages') . '</h1>';

if (!empty($_GET['clearbyip'])) {
    if (empty($_GET['csrf_token']) OR !hash_equals($user->csrf_token, $_GET['csrf_token'])) {
        echo '<h3>' . _('Invalid token') . '</h3>';
    } elseif (!filter_var($_GET['clearbyip'], FILTER_VALIDATE_IP)) {
        echo '<h3>' . _('Invalid IP-address') . '</h3>';
    } else {
        $engine->writeModlog(26, 'From IP: ' . $_GET['clearbyip']);
        $q = $db->q("UPDATE post_report SET cleared = 1, cleared_by = " . $user->id . " WHERE cleared = 0 AND reported_by = INET6_ATON('" . $db->escape($_GET['clearbyip']) . "')");

        if ($q) {
            echo '<h3>' . _('All reports from ip cleared') . '</h3><p><a class="pure-button" href="?action=reportedposts">' . _('Return') . '</a></p>';
        } else {
            echo '<h3>' . _('Action failed') . '</h3>';
        }
    }
} elseif (!empty($_GET['clearbyuser'])) {
    if (empty($_GET['csrf_token']) OR !hash_equals($user->csrf_token, $_GET['csrf_token'])) {
        echo '<h3>' . _('Invalid token') . '</h3>';
    } else {
        $engine->writeModlog(26, 'From user: ' . $_GET['clearbyuser']);
        $q = $db->q("UPDATE post_report SET cleared = 1, cleared_by = " . $user->id . " WHERE cleared = 0 AND reported_by_user = " . (int)$_GET['clearbyuser']);

        if ($q) {
            echo '<h3>' . _('All reports from user cleared') . '</h3><p><a class="pure-button" href="?action=reportedposts">' . _('Return') . '</a></p>';
        } else {
            echo '<h3>' . _('Action failed') . '</h3>';
        }
    }
} else {

    if (!isset($_GET['cleared'])) {
        $cleared = 0;
        $order = 'report_count DESC, MAX(a.report_time) ASC';
    } else {
        $cleared = 1;
        $order = 'MAX(a.report_time) DESC';
    }

    $q = $db->q("SELECT post_id, op_post, t.id as thread_id, MIN(c.name) AS boardname, MAX(cleared_by) AS cleared_by, MIN(reason) AS reason, COUNT(*) AS report_count,
            MAX(a.post_id), UNIX_TIMESTAMP(MIN(a.report_time)) AS report_time, INET6_NTOA(MAX(a.reported_by)) AS reported_by,
            MAX(reported_by_user) as reported_by_user
        FROM `post_report` a
        LEFT JOIN `post` p1 ON a.`post_id` = p1.`id`
        LEFT JOIN thread t ON t.id = p1.thread_id
        LEFT JOIN `board` c ON t.board_id = c.`id`
        WHERE a.`cleared` = " . (int)$cleared . "
        GROUP BY a.post_id
        ORDER BY " . $order . "
        LIMIT 500");

    if ($cleared == 0) {
        echo '<h2>' . _('Uncleared reports') . '</h2>
        <p>
            <a class="pure-button" href="?action=reportedposts&cleared">' . _('Show cleared reports') . '</a>
        </p>';
    } else {
        echo '<h2>' . _('Cleared reports') . '</h2>
        <p><a class="pure-button" href="?action=reportedposts">' . _('Show uncleared reports') . '</a></p>';
    }

    if ($q->num_rows >= 1) {

        echo '
		<table class="pure-table pure-table-striped">
            <thead>
			<tr>
				<th>' . _('Message') . '</th>
				<th>' . _('Reason') . '</th>
				<th>' . _('By IP') . '</th>
				<th>' . _('By user') . '</th>
				<th>' . _('Reported') . '</th>';
        if ($cleared == 0) {
            echo '
                <th>' . _('Actions') . '</th>';
        } else {
            echo '
                <th>' . _('Checked by') . '</th>';
        }
        echo '
			</tr>
            </thead>
            <tbody>';
        while ($report = $q->fetch_assoc()) {
            $bgcolor = '';
            if ($report['report_time'] < time() - 7200 && !$cleared) {
                $opacity = str_replace(',', '.', round((time() - $report['report_time'] - 7200) / 172800, 4));
                if ($opacity > 1) {
                    $opacity = 1;
                }
                $bgcolor = ' style="background-color: rgba(255,100,100,' . $opacity . ')"';
            }
            echo '
			<tr' . $bgcolor . ' class="post thread" data-id="' . $report['post_id'] . '" data-thread-id="' . $report['thread_id'] . '">
                <td>
                    ' . $report['boardname'] . '<br />
                    <a href="' . $engine->cfg->siteUrl . '/scripts/redirect.php?id=' . $report['post_id'] . '" data-id="' . $report['post_id'] . '" class="ref">&gt;&gt;' . $report['post_id'] . '</a>
                </td>
				<td>
                    ' . $report['reason'] .
                    ($report['report_count'] == 1 ? '' : '<br /><b>Ilmiannettu ' . $report['report_count'] . ' kertaa</b>') . '
                </td>
                <td>
                    ' . $report['reported_by'] . '<br />
                    <a href="?action=reportedposts&clearbyip=' . $report['reported_by'] . '&csrf_token=' . $user->csrf_token . '" data-e="confirm">' . _('Clear all') . '</a>
                </td>
                <td>
                    ' . $report['reported_by_user'] . '<br />
                    <a href="?action=reportedposts&clearbyuser=' . $report['reported_by_user'] . '&csrf_token=' . $user->csrf_token . '" data-e="confirm">' . _('Clear all') . '</a>
                </td>
				<td><time datetime="' . date(DateTime::ATOM, $report['report_time']) . '">'
                    . $engine->formatTime($user->language, $user, $report['report_time']) .'</time></td>';
            if ($cleared == 0) {
                echo '
                <td class="nowrap">
                    <button class="icon-button icon-check" data-e="postCheckReport"></button>
                    <button class="icon-button icon-trash2" data-e="postDelete"></button>
                    <button class="icon-button icon-hammer2" data-e="postBanUser"></button>';
                if ($report['op_post']) {
                    echo '
                    <button class="icon-button icon-shred" data-e="threadDelete"></button>
                    <button class="icon-button icon-hammer-wrench" data-e="threadManage"></button>';
                }
                echo '
                </td>';
            } else {
                echo '
                <td>' . htmlspecialchars($user->modNameById($report['cleared_by'])) . '</td>';
            }
            echo '
			</tr>';
        }
        echo '
            </tbody>
		</table>';
    } else {
        echo '<h3>' . _('No reports') . '</h3>';
    }
}
