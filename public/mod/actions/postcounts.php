<?php
if (!defined('ALLOWLOAD')) {
    die();
}

die ('Broken. Wontfix. Sorry.');

echo '
<h1>' . _('Message amounts') . '</h1>
<table class="pure-table pure-table-striped">
    <thead>
	<tr>
		<th>' . _('Board') . '</th>
		<th>' . _('Messages per month') . '</th>
		<th>' . _('Messages per day') . '</th>
		<th>' . _('Messages per hour') . '</th>
	</tr>
    </thead>
    <tbody>';

$qam = $db->q("SELECT COUNT(`id`) AS `count`, b.board_id FROM `post` a LEFT JOIN thread b ON b.id = a.thread_id WHERE a.time > DATE_SUB(NOW(), INTERVAL 1 MONTH) GROUP BY b.board_id");
$qbm = $db->q("SELECT COUNT(`id`) AS `count`, `board_id` FROM `post_deleted` WHERE `time` > DATE_SUB(NOW(), INTERVAL 1 MONTH) GROUP BY b.board_id");
$qad = $db->q("SELECT COUNT(`id`) AS `count`, `board` FROM `post` WHERE `time` > DATE_SUB(NOW(), INTERVAL 1 DAY) GROUP BY b.board_id");
$qbd = $db->q("SELECT COUNT(`id`) AS `count`, `board_id` FROM `post_deleted` WHERE `time` > DATE_SUB(NOW(), INTERVAL 1 DAY) GROUP BY b.board_id");
$qah = $db->q("SELECT COUNT(`id`) AS `count`, `board` FROM `post` WHERE `time` > DATE_SUB(NOW(), INTERVAL 1 HOUR) GROUP BY `board`");
$qbh = $db->q("SELECT COUNT(`id`) AS `count`, `board_id` FROM `post_deleted` WHERE `time` > DATE_SUB(NOW(), INTERVAL 1 HOUR) GROUP BY b.board_id");

$am = $db->fetchAll($qam, 'count', 'board');
$bm = $db->fetchAll($qbm, 'count', 'board_id');
$ad = $db->fetchAll($qad, 'count', 'board');
$bd = $db->fetchAll($qbd, 'count', 'board_id');
$ah = $db->fetchAll($qah, 'count', 'board');
$bh = $db->fetchAll($qbh, 'count', 'board_id');

$boards = $db->q("SELECT `name` AS boardname, `id` AS boardid FROM `board` ORDER BY `name`");

$totalDay = 0;
$totalHour = 0;
$totalMonth = 0;
while ($board = $boards->fetch_assoc()) {
    $boardMonth = ((array_key_exists($board['boardid'],
            $am) ? $am[$board['boardid']] : 0) + (array_key_exists($board['boardid'],
            $bm) ? $bm[$board['boardid']] : 0));
    $boardDay = ((array_key_exists($board['boardid'],
            $ad) ? $ad[$board['boardid']] : 0) + (array_key_exists($board['boardid'],
            $bd) ? $bd[$board['boardid']] : 0));
    $boardHour = ((array_key_exists($board['boardid'],
            $ah) ? $ah[$board['boardid']] : 0) + (array_key_exists($board['boardid'],
            $bh) ? $bh[$board['boardid']] : 0));
    $totalMonth += $boardMonth;
    $totalDay += $boardDay;
    $totalHour += $boardHour;

    echo '
	<tr>
		<td>' . $board['boardname'] . '</td>
		<td>' . $boardMonth . '</td>
		<td>' . $boardDay . '</td>
		<td>' . $boardHour . '</td>
	</tr>';

}

echo '
	<tr class="bold">
		<td>' . _('Total') . '</td>
		<td>' . $totalMonth . '</td>
		<td>' . $totalDay . '</td>
		<td>' . $totalHour . '</td>
    </tr>
    </tbody>
</table>';
