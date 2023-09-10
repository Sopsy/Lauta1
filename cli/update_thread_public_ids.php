<?php

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

include("../public/inc/engine.class.php");
new Engine(['db' => '', 'fileupload' => '']);

$q = $db->q("SELECT id, user_id FROM thread WHERE time < '2020-07-11 19:00:00' ORDER BY id ASC");

while ($thread = $q->fetch_assoc()) {
    echo "Updating {$thread['id']}...\n";

    $db->q('UPDATE post SET public_user_id = 0 WHERE thread_id = ' . (int)$thread['id']);

    $qa = $db->q('SELECT id, thread_id, ip, user_id, op_post FROM post WHERE thread_id = ' . (int)$thread['id'] . ' ORDER BY id ASC');
    while ($reply = $qa->fetch_assoc()) {
        if ($reply['op_post'] || $reply['user_id'] != null && $reply['user_id'] == $thread['user_id']) {
            // OP is always 0
            continue;
        }

        $getPublicId = $db->q('SELECT public_user_id FROM post
            WHERE
                id < ' . (int)$reply['id'] . '
                AND IF(user_id IS NULL, ip = UNHEX("' . bin2hex($reply['ip']) . '"), user_id = ' . (int)$reply['user_id'] . ')
                AND thread_id = ' . (int)$reply['thread_id'] . '
            LIMIT 1');

        if ($getPublicId->num_rows === 0) {
            $getPublicId = $db->q(
                "SELECT COALESCE(MAX(public_user_id)+1, 1) AS public_user_id
            FROM post WHERE thread_id = " . (int)$thread['id'] . " LIMIT 1"
            );
        }
        $publicUserId = $getPublicId->fetch_assoc()['public_user_id'];

        $db->q('UPDATE post SET public_user_id = ' . (int)$publicUserId . ' WHERE id = ' . (int)$reply['id'] . ' LIMIT 1');
    }
}
