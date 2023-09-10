<?php

if( !isset( $_SERVER['REMOTE_ADDR'] ) )
	$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
if( !isset( $_SERVER['REQUEST_URI'] ) )
	$_SERVER['REQUEST_URI'] = '/cron_minutely';

include(__DIR__ . "/../public/inc/engine.class.php");
new Engine(['db' => '', 'fileupload' => '', 'posts' => '']);

$q = $db->q("SELECT MAX(id) AS id FROM post LIMIT 1");
$maxId = $q->fetch_assoc()['id'];
file_put_contents($engine->cfg->staticDir . '/id.txt', $maxId);

// Front page postcount
$db->q("SET SESSION long_query_time = 5");

// Delete orphaned file db-entries
echo "\nDeleting old files...\n";

// Delete deleted files
$q = $db->q("SELECT id, extension FROM file_deleted");
while ($row = $q->fetch_assoc()) {
    $fileName = str_pad(base_convert($row['id'], 10, 36), 5, '0', STR_PAD_LEFT);
    $folder = $fileName[0] . '/' . $fileName[1] . '/' . $fileName[2];

    echo "Deleting file {$fileName}\n";
    $files = glob("{$engine->cfg->filesDir}/{$folder}/{$fileName}.*");
    foreach ($files AS $file) {
        unlink($file);
    }

    if ($row['extension'] == 'm4a') {
        $thumb = false;
    } else {
        $thumb = true;
    }

    // Cache purge
    if ($row['extension'] == 'mp4') {
        $fileUrl = "{$engine->cfg->videosUrl}/{$fileName}.{$row['extension']}";
    } else {
        $fileUrl = "{$engine->cfg->filesUrl}/{$fileName}.{$row['extension']}";
    }

    if ($thumb) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "{$engine->cfg->thumbsUrl}/{$fileName}." . ($row['extension'] == 'mp4' ? 'jpg' : $row['extension']));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Bypass-Cache: 1']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $fileUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Bypass-Cache: 1']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);

    $db->q("DELETE FROM file_deleted WHERE id = " . (int)$row['id']);
}

// RRD update
$updater = new RRDUpdater($engine->cfg->rrdDir . '/counter_posts.rrd');
$q = $db->q('SELECT id, UNIX_TIMESTAMP() AS timestamp FROM post ORDER BY id DESC LIMIT 1');
$row = $q->fetch_assoc();
if (!empty($row)) {
    $updater->update(['posts' => $row['id']], $row['timestamp']);
    echo "counter_posts.rrd updated\n";
}

$updater = new RRDUpdater($engine->cfg->rrdDir . '/counter_users_realtime.rrd');
$q = $db->q('SELECT COUNT(*) AS count, UNIX_TIMESTAMP() AS timestamp FROM user WHERE last_active >= DATE_SUB(NOW(), INTERVAL 1 HOUR) LIMIT 1');
$row = $q->fetch_assoc();
if (!empty($row)) {
    $updater->update(['users' => $row['count']], $row['timestamp']);
    echo "counter_users_realtime.rrd updated\n";
}

// Update graph images
$graphOutput = $engine->cfg->staticDir . '/img/graphs';
if (!is_dir($graphOutput) && !mkdir($graphOutput, 0775, true) && !is_dir($graphOutput)) {
    die('Failed to create a directory for graph images');
}

$rrd = new RRDGraph($engine->cfg->rrdGraphOutputDir . '/posts_day.svg');
$rrd->setOptions(array_merge($engine->cfg->rrdGraphOptions, [
    "--start" => strtotime('1 day ago'),
    "DEF:posts={$engine->cfg->rrdDir}/counter_posts.rrd:posts:AVERAGE",
    "CDEF:graph=posts,300,TRENDNAN,3600,*",
    "LINE:graph#800",
]));
$rrd->save();

$rrd = new RRDGraph($engine->cfg->rrdGraphOutputDir . '/users_day.svg');
$rrd->setOptions(array_merge($engine->cfg->rrdGraphOptions, [
    "--start" => strtotime('1 day ago'),
    "DEF:users={$engine->cfg->rrdDir}/counter_users_realtime.rrd:users:AVERAGE",
    "CDEF:graph=users",
    "LINE:graph#800",
]));
$rrd->save();