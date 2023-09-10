<?php

// Initialize the board engine
$loadClasses = [
    'cache' => '',
    'db' => '',
    'user' => '',
];
include("../inc/engine.class.php");
new Engine($loadClasses);

if (!$user->isMod) {
    die('Go away');
}

$q = $db->q("SELECT *, UNIX_TIMESTAMP(thread.time) AS time, thread.id AS id
    FROM thread
    LEFT JOIN post b ON b.thread_id = thread.id AND b.op_post = 1
    WHERE board_id = 1 AND thread.time >= DATE_SUB(NOW(), INTERVAL 12 HOUR) AND subject LIKE 'Ylilaudan joulukalenterin %. luukku' AND b.edited IS NULL
    ORDER BY thread.id ASC LIMIT 30");
$rows = [];
while ($row = $q->fetch_assoc()) {
    $rows[] = $row;
}

foreach ($rows as $row) {
    echo '<a href="/sekalainen/' . $row['id'] . '">' . $row['id'] . '</a> ' . date('d.m.Y H:i:s',
            $row['time']) . ' ' . $row['subject'] . '<br />';
}