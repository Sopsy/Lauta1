<?php

if (!isset($_SERVER['REMOTE_ADDR'])) {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
}
if (!isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = '/cron_hourly';
}

include(__DIR__ . "/../public/inc/engine.class.php");
new Engine(['db' => '', 'fileupload' => '', 'posts' => '']);

$boards = $db->q("SELECT * FROM board");
$db->q("SET SESSION long_query_time = 5");
$deletedThreads = 0;
$lockedThreads = 0;

while ($board = $boards->fetch_object()) {
    // Delete old threads
    if (!empty($board->inactive_hours_delete)) {
        $interval = (int)$board->inactive_hours_delete;
        if (empty($interval)) {
            continue;
        } // Failsafe

        $q = $db->q("SELECT id
            FROM thread a
            WHERE board_id = " . (int)$board->id . "
            AND is_sticky = 0
            AND bump_time < DATE_SUB(NOW(), INTERVAL " . $interval . " HOUR)
            ORDER BY id ASC
            LIMIT 10000");
        $ids = $db->fetchAll($q, 'id');
        $deletedThreads += count($ids);
        $ids = implode(',', $ids);
        if (!empty($ids)) {
            $posts->deleteThreads($ids, 1);
            echo $board->url . " delete:\n" . $ids . "\n\n";
        }
    }

    // Lock old threads
    if (!empty($board->inactive_hours_lock)) {
        $interval = (int)$board->inactive_hours_lock;
        if (empty($interval)) {
            continue;
        } // Failsafe

        $q = $db->q("SELECT id
            FROM thread a
            WHERE board_id = " . (int)$board->id . "
            AND is_sticky = 0
            AND is_locked = 0
            AND bump_time < DATE_SUB(NOW(), INTERVAL " . $interval . " HOUR)
            LIMIT 10000");
        $ids = $db->fetchAll($q, 'id');
        $lockedThreads += count($ids);
        $ids = implode(',', $ids);

        if (!empty($ids)) {
            $db->q('UPDATE thread SET is_locked = 1 WHERE id IN (' . $ids . ')');
            echo $board->url . " lock:\n" . $ids . "\n\n";
        }
    }
}

echo $deletedThreads . " threads deleted\n";
echo $lockedThreads . " threads locked\n";

// Update graph images
$rrd = new RRDGraph($engine->cfg->rrdGraphOutputDir . '/posts_month.svg');
$rrd->setOptions(array_merge($engine->cfg->rrdGraphOptions, [
    "--start" => strtotime('30 days ago'),
    "DEF:posts={$engine->cfg->rrdDir}/counter_posts.rrd:posts:AVERAGE",
    "CDEF:graph=posts,3600,*",
    "LINE:graph#800",
]));
$rrd->save();

$rrd = new RRDGraph($engine->cfg->rrdGraphOutputDir . '/users_month.svg');
$rrd->setOptions(array_merge($engine->cfg->rrdGraphOptions, [
    "--start" => strtotime('30 day ago'),
    "DEF:users={$engine->cfg->rrdDir}/counter_users_realtime.rrd:users:AVERAGE",
    "CDEF:graph=users",
    "LINE:graph#800",
]));
$rrd->save();