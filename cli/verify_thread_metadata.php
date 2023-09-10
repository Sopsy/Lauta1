<?php

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

include("../public/inc/engine.class.php");
new Engine(['db'=>'','fileupload'=>'']);

$q = $db->q("SELECT id, reply_count, distinct_reply_count, hide_count, follow_count FROM thread ORDER BY id DESC");

$i = 0;
while($old = $q->fetch_assoc()) {
	++$i;
	if($i % 1000 == 0)
		echo '.';

	$qa = $db->q('SELECT COUNT(DISTINCT public_user_id) AS distinct_replies,
        COUNT(DISTINCT id) AS replies,
        (SELECT COUNT(*) FROM user_thread_follow b WHERE b.thread_id = ' . (int)$old['id'] . ' LIMIT 1) AS follow_count,
        (SELECT COUNT(*) FROM user_thread_hide c WHERE c.thread_id = ' . (int)$old['id'] . ' LIMIT 1) AS hide_count
        FROM post a WHERE thread_id = ' . (int)$old['id'] . ' AND a.op_post = 0 LIMIT 1');
    $new = $qa->fetch_assoc();

	$update = false;
	if((int)$old['reply_count'] != (int)$new['replies']) {
		$update = true;
		echo $old['id'] .' Reply count mismatch ('. (int)$old['reply_count'] .' -> '. (int)$new['replies'] .")\n";
	}
    if((int)$old['distinct_reply_count'] != (int)$new['distinct_replies']) {
		$update = true;
        echo $old['id'] .' Distinct reply count mismatch ('. (int)$old['distinct_reply_count'] .' -> '. (int)$new['distinct_replies'] .")\n";
	}
    if((int)$old['follow_count'] != (int)$new['follow_count']) {
		$update = true;
        echo $old['id'] .' Follow count mismatch ('. (int)$old['follow_count'] .' -> '. (int)$new['follow_count'] .")\n";
	}
    if((int)$old['hide_count'] != (int)$new['hide_count']) {
		$update = true;
        echo $old['id'] .' Hide count mismatch ('. (int)$old['hide_count'] .' -> '. (int)$new['hide_count'] .")\n";
	}

	if(!$update) {
        continue;
    }

	$db->q("UPDATE thread SET
        reply_count = ". (int)$new['replies'] .",
        distinct_reply_count = ". (int)$new['distinct_replies'] .",
        follow_count = ". (int)$new['follow_count'] .",
        hide_count = ". (int)$new['hide_count'] ."
        WHERE id = " . (int)$old['id'] . " LIMIT 1");
}
