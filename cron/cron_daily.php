<?php

if (!isset($_SERVER['REMOTE_ADDR'])) {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
}
if (!isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = '/cron_daily';
}

include(__DIR__ . "/../public/inc/engine.class.php");
new Engine(['db' => '', 'fileupload' => '']);

// Update RRD
$updater = new RRDUpdater($engine->cfg->rrdDir . '/counter_users_daily.rrd');
$q = $db->q('SELECT UNIX_TIMESTAMP() AS timestamp, COUNT(*) AS count FROM user WHERE last_active >= DATE_SUB(NOW(), INTERVAL 1 DAY) LIMIT 1');
$row = $q->fetch_assoc();
$updater->update(['users' => $row['count']], $row['timestamp']);
echo "counter_users_daily.rrd updated\n";

$rrd = new RRDGraph($engine->cfg->rrdGraphOutputDir . '/posts_year.svg');
$rrd->setOptions(array_merge($engine->cfg->rrdGraphOptions, [
    "--start" => strtotime('1 year ago'),
    "DEF:posts={$engine->cfg->rrdDir}/counter_posts.rrd:posts:AVERAGE",
    "CDEF:graph=posts,86400,*",
    "LINE:graph#800",
]));
$rrd->save();

$rrd = new RRDGraph($engine->cfg->rrdGraphOutputDir . '/users_year.svg');
$rrd->setOptions(array_merge($engine->cfg->rrdGraphOptions, [
    "--start" => strtotime('1 year ago'),
    "DEF:users={$engine->cfg->rrdDir}/counter_users_daily.rrd:users:AVERAGE",
    "CDEF:graph=users",
    "LINE:graph#800",
]));
$rrd->save();