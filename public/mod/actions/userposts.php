<?php

if (!defined('ALLOWLOAD')) {
    die();
}

echo '
<style nonce="' . SCRIPT_NONCE . '">
.content {
    background-color: #fff;
    color: #000;
    padding: 10px;
    border: 1px solid #eee;
}
label {
    display: block;
    margin-bottom: 5px;
}
</style>
<h1 class="bottommargin">Etsi käyttäjän viestit</h1>
<form class="modform" action="' . $_SERVER['REQUEST_URI'] . '" method="post">
    <fieldset>
        <label>Etsimisperuste:</label>
        <label><input type="radio" name="searchtype" value="post"> Viestin ID</label>
        <label><input type="radio" name="searchtype" value="user"> Käyttäjän ID</label>
        <label>ID: <input type="text" name="id" value="' . (!empty($_POST['id']) ? $_POST['id'] : '') . '" /></label>
        <label>Viestit jälkeen: <input type="date" name="datelimit" value="' . (!empty($_POST['datelimit']) ? $_POST['datelimit'] : '') . '" /></label>
        <input type="submit" value="Etsi" />
    </fieldset>
</form>
<div class="content">';

if (empty($_POST['datelimit'])) {
    $_POST['datelimit'] = '1970-01-01';
}
$datelimit = strtotime($_POST['datelimit']);
$datelimit = $db->escape(date('Y-m-d', $datelimit));

// If the user has given us the input
if (!empty($_POST['searchtype']) && !empty($_POST['id'])) {
    if ($_POST['searchtype'] === 'post') {
        $id = $db->escape($_POST['id']);

        $q = $db->q(
            'SELECT * FROM (
            SELECT user_id AS id
            FROM post_deleted WHERE id = ' . (int)$id . '
            UNION
            SELECT user_id AS id
            FROM post WHERE id = ' . (int)$id . '
        ) a LIMIT 1'
        );

        if ($q->num_rows < 1) {
            echo 'Viestiä ei löytynyt';
        } else {
            $uid = $q->fetch_assoc()['id'];
            $q2 = $db->q(
                'SELECT a.*, UNIX_TIMESTAMP(time) AS time, UNIX_TIMESTAMP(time_deleted) AS time_deleted FROM (
                SELECT id, user_id, ip, message, time, remote_port, time_deleted
                FROM post_deleted WHERE `user_id` = ' . (int)$uid . ' AND time >= "' . $datelimit . '"
                UNION
                SELECT id, user_id, ip, message, time, remote_port, NULL AS time_deleted
                FROM post WHERE `user_id` = ' . (int)$uid . ' AND time >= "' . $datelimit . '"
            ) a
            ORDER BY time DESC'
            );

            echo $q2->num_rows . ' viestiä';
            if ($datelimit !== '1970-01-01') {
                echo ' ' . $datelimit . ' jälkeen';
            }
            echo '<hr>';

            while ($post = $q2->fetch_assoc()) : ?>
                <b>Viestin nro.</b> <?= $post['id'] ?><br/>
                <b>Käyttäjän ID:</b> <?= $post['user_id'] ?><br/>
                <b>Lähetetty:</b> <?= date('c', $post['time']) ?><br/>
                <?php if (!empty($post['time_deleted'])) : ?>
                    <b>Poistettu:</b> <?= date('c', $post['time_deleted']) ?><br/>
                <?php endif ?>
                <b>IP-osoite:</b>
                    [<?= inet_ntop($post['ip'])
                    ?>]<?= (!empty($post['remote_port']) ? ':' . $post['remote_port'] : '') ?><br/>
                <b>Viesti:</b> <?= nl2br(htmlspecialchars($post['message'])) ?>
                <hr>
            <?php endwhile;
        }
    } elseif ($_POST['searchtype'] === 'user') {
        $uid = $db->escape($_POST['id']);;
        $datelimit = $db->escape($_POST['datelimit'] ?? '1970-01-01');
        $q2 = $db->q(
            'SELECT a.*, UNIX_TIMESTAMP(time) AS time FROM (
            SELECT id, user_id, ip, message, time, remote_port
            FROM post_deleted WHERE `user_id` = ' . (int)$uid . ' AND time >= "' . $datelimit . '"
            UNION
            SELECT id, user_id, ip, message, time, remote_port
            FROM post WHERE `user_id` = ' . (int)$uid . ' AND time >= "' . $datelimit . '"
        ) a
        ORDER BY time DESC'
        );

        echo $q2->num_rows . ' viestiä';
        if ($datelimit !== '1970-01-01') {
            echo ' ' . $datelimit . ' jälkeen';
        }
        echo '<hr>';

        while ($post = $q2->fetch_assoc()) : ?>
            <b>Viestin nro.</b> <?= $post['id'] ?><br/>
            <b>Käyttäjän ID:</b> <?= $post['user_id'] ?><br/>
            <b>Lähetetty:</b> <?= date('c', $post['time']) ?><br/>
            <b>IP-osoite:</b>
                [<?= inet_ntop($post['ip'])
                ?>]<?= (!empty($post['remote_port']) ? ':' . $post['remote_port'] : '') ?><br/>
            <b>Alue:</b> <?= htmlspecialchars($post['boardname']) ?><br/>
            <?php if (!empty($post['subject'])) : ?>
                <b>Aihe:</b> <?= htmlspecialchars($post['subject']) ?><br/>
            <?php endif ?>
            <b>Viesti:</b> <?= nl2br(htmlspecialchars($post['message'])) ?>
            <hr>
        <?php endwhile;
    }
}
echo '</div>';


