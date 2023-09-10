<?php
die();

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

include(dirname(__DIR__) . "/public/inc/engine.class.php");
new Engine(['db' => '', 'fileupload' => '']);

// Realtime active users
if (is_file('../data/counter_users_realtime.rrd')) {
    unlink('../data/counter_users_realtime.rrd');
}

$q = $db->q("SELECT UNIX_TIMESTAMP(MIN(time)) AS min_time FROM counter_users_hour");
$minTime = $q->fetch_assoc()['min_time'];

$rrd = new RRDCreator('../data/counter_users_realtime.rrd', $minTime-1, 60);
$rrd->addDataSource('users:GAUGE:110:0:U');
$rrd->addArchive('AVERAGE:0.5:1:1440');
$rrd->addArchive('AVERAGE:0.5:60:744');
$rrd->addArchive('AVERAGE:0.5:1440:3650');
$rrd->save();

$updater = new RRDUpdater('../data/counter_users_realtime.rrd');

$q = $db->q("SELECT *, UNIX_TIMESTAMP(time) AS time, UNIX_TIMESTAMP((SELECT MIN(time) FROM counter_users_minute)) AS min_time
    FROM counter_users_hour
    WHERE time < (SELECT MIN(time) FROM counter_users_minute)
    ORDER BY time ASC");
while ($row = $q->fetch_assoc()) {
    for ($min = 0; $min < 60; ++$min) {
        $time = $row['time'] + $min * 60;
        if ($time >= $row['min_time']) {
            break;
        }
        echo "Adding " . date("Y-m-d H:i:s", $time) . ": {$row['count']}\n";
        $updater->update(['users' => $row['count']], $time);
    }
}

$q = $db->q("SELECT * FROM counter_users_minute ORDER BY time ASC");
while ($row = $q->fetch_assoc()) {
    echo "Adding {$row['time']}: {$row['count']}\n";
    $updater->update(['users' => $row['count']], strtotime($row['time']));
}

die();

// Monthly active users
if (is_file('../data/counter_users_monthly.rrd')) {
    unlink('../data/counter_users_monthly.rrd');
}

$q = $db->q("SELECT UNIX_TIMESTAMP(MIN(month)) AS min_time FROM counter_users_month");
$minTime = $q->fetch_assoc()['min_time'];

$rrd = new RRDCreator('../data/counter_users_monthly.rrd', $minTime-1, 2592000);
$rrd->addDataSource('users:GAUGE:3240000:0:U');
$rrd->addArchive('AVERAGE:0.5:1:600');
$rrd->save();

$updater = new RRDUpdater('../data/counter_users_monthly.rrd');

$q = $db->q("SELECT *, UNIX_TIMESTAMP(month) AS timestamp FROM counter_users_month ORDER BY month ASC");
while ($row = $q->fetch_assoc()) {
    echo "Adding {$row['timestamp']}: {$row['count']}\n";
    $updater->update(['users' => $row['count']], $row['timestamp']);
}

// Daily active users
if (is_file('../data/counter_users_daily.rrd')) {
    unlink('../data/counter_users_daily.rrd');
}

$q = $db->q("SELECT UNIX_TIMESTAMP(MIN(date)) AS min_time FROM counter_users_day");
$minTime = $q->fetch_assoc()['min_time'];

$rrd = new RRDCreator('../data/counter_users_daily.rrd', $minTime-1, 86400);
$rrd->addDataSource('users:GAUGE:129600:0:U');
$rrd->addArchive('AVERAGE:0.5:1:3650');
$rrd->save();

$updater = new RRDUpdater('../data/counter_users_daily.rrd');

$q = $db->q("SELECT *, UNIX_TIMESTAMP(date) AS timestamp FROM counter_users_day ORDER BY date ASC");
while ($row = $q->fetch_assoc()) {
    echo "Adding {$row['timestamp']}: {$row['count']}\n";
    $updater->update(['users' => $row['count']], $row['timestamp']);
}

die();
// Daily active users
if (is_file('../data/counter_posts.rrd')) {
    unlink('../data/counter_posts.rrd');
}
//$q = $db->q("SELECT UNIX_TIMESTAMP(MIN(date)) AS min_time FROM counter_users_month");
//$minTime = $q->fetch_assoc()['min_time'];

$rrd = new RRDCreator('../data/counter_posts.rrd', 1298937599, 60);
$rrd->addDataSource('posts:COUNTER:110:0:U');
$rrd->addArchive('AVERAGE:0.5:1:1440');
$rrd->addArchive('AVERAGE:0.5:60:744');
$rrd->addArchive('AVERAGE:0.999:1440:3650');
$rrd->save();

$updater = new RRDUpdater('../data/counter_posts.rrd');

/*
$q = $db->q("SELECT MAX(id) AS id, UNIX_TIMESTAMP(DATE_FORMAT(time, '%Y-%m-%d %H:%i:00')) AS date
    FROM posts
    WHERE time > '2020-06-05 16:20:00'
    GROUP BY UNIX_TIMESTAMP(DATE_FORMAT(time, '%Y-%m-%d %H:%i:00'))
    ORDER BY UNIX_TIMESTAMP(DATE_FORMAT(time, '%Y-%m-%d %H:%i:00')) ASC");
*/
$q = $db->q("SELECT id, UNIX_TIMESTAMP(time) AS time FROM temp ORDER BY time ASC");
$i = 0;
while ($row = $q->fetch_assoc()) {
    ++$i;
    if ($i % 100 === 1) {
        echo "Adding {$row['time']}: {$row['id']}\n";
    }
    $updater->update(['posts' => $row['id']], $row['time']);
}