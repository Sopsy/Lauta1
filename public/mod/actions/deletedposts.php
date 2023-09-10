<?php
if (!defined('ALLOWLOAD')) {
    die();
}

echo '
<h1 class="bottommargin">Etsi poistettu viesti</h1>
<form class="modform" action="' . $_SERVER['REQUEST_URI'] . '" method="post">
<fieldset>
<label for="id">Viestin ID</label>
<input type="text" id="id" name="id" value="' . (!empty($_POST['id']) ? $_POST['id'] : (!empty($_GET['id']) ? $_GET['id'] : '')) . '" /><br />
<input type="submit" value="Etsi" />
</fieldset>
</form>';

// If the user has given us the input
if (!empty($_POST['id'])) {
    $id = $db->escape($_POST['id']);
    $q = $db->q("SELECT a.*, b.name AS boardname, b.url, UNIX_TIMESTAMP(time_deleted) AS time_deleted, UNIX_TIMESTAMP(time) AS time
        FROM `post_deleted` a
        LEFT JOIN `board` b ON a.`board_id` = b.`id`
        WHERE a.`id` = " . (int)$id . " LIMIT 1");

    if ($q->num_rows != 0) {
        $post = $q->fetch_assoc();
        echo '
		<b>ID:</b> ' . $post['id'] . '<br />
		<b>Käyttäjän ID:</b> ' . $post['user_id'] . '<br />
		<b>Lähetetty:</b> ' . date('d.m.Y H:i:s', $post['time']) . '<br />
		<b>Poistettu:</b> ' . date('d.m.Y H:i:s', $post['time_deleted']) . '<br />
		<b>IP:</b> ' . inet_ntop($post['ip']) . (!empty($post['remote_port']) ? ':' . $post['remote_port'] : '') . '<br />
		<b>Alue:</b> ' . htmlspecialchars($post['boardname']) . '<br />
		<b>Aihe:</b> ' . htmlspecialchars($post['subject']) . '<br />
		<b>Viesti:</b> ' . htmlspecialchars($post['message']) . '<br />';
    } else {
        echo '<h2>Viestiä ei ole olemassa</h2>';
    }
}

