<?php
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

include(dirname(__DIR__) . "/public/inc/engine.class.php");
new Engine(['db' => '', 'fileupload' => '']);

// Hourly posts
$updater = new RRDUpdater($engine->cfg->rrdDir . '/counter_posts.rrd');
$q = $db->q("SELECT MAX(id) AS id, UNIX_TIMESTAMP(DATE_FORMAT(time, '%Y-%m-%d %H:%i:00')) AS timestamp
    FROM posts
    WHERE time > '2020-06-05 16:20:00'
    GROUP BY UNIX_TIMESTAMP(DATE_FORMAT(time, '%Y-%m-%d %H:%i:00'))
    ORDER BY UNIX_TIMESTAMP(DATE_FORMAT(time, '%Y-%m-%d %H:%i:00')) ASC");

while ($row = $q->fetch_assoc()) {
    echo "Adding {$row['timestamp']}: {$row['id']}\n";
    $updater->update(['posts' => $row['id']], $row['timestamp']);
}

// Realtime users
$updater = new RRDUpdater($engine->cfg->rrdDir . '/counter_users_realtime.rrd');
$q = $db->q("SELECT * FROM counter_users_minute WHERE time > '2020-06-05 21:40:00' ORDER BY time ASC");
while ($row = $q->fetch_assoc()) {
    echo "Adding {$row['time']}: {$row['count']}\n";
    $updater->update(['users' => $row['count']], strtotime($row['time']));
}

// Daily users
$updater = new RRDUpdater($engine->cfg->rrdDir . '/counter_users_daily.rrd');
$q = $db->q("SELECT * FROM counter_users_minute WHERE date > '2020-06-04' ORDER BY date ASC");
while ($row = $q->fetch_assoc()) {
    echo "Adding {$row['date']}: {$row['count']}\n";
    $updater->update(['users' => $row['count']], strtotime($row['date']));
}