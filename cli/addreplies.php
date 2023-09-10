<?php

if (!isset($_SERVER['REMOTE_ADDR'])) {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
}
if (!isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = '/deletefiles';
}

include(__DIR__ . "/../public/inc/engine.class.php");
new Engine(['db' => '', 'fileupload' => '', 'posts' => '']);

$q = $db->q("SELECT id, message FROM post ORDER BY id ASC");

while ($row = $q->fetch_assoc()) {
    preg_match_all('/>>([0-9]+)/i', $row['message'], $replies);

    if(!empty($replies[1])) {

        $replies = $db->escape(implode(',', array_unique($replies[1])));
        $qb = $db->q("SELECT id FROM post WHERE id IN (" . $replies . ")");
        $replies = [];
        while ($rep = $qb->fetch_assoc()) {
            $replies[] = $rep['id'];
        }

        if(!empty($replies)) {
            $posts->saveReplies($row['id'], $replies);
            echo $row['id'] . "\n";
        }
    }
}
