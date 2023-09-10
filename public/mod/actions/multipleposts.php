<?php


if (!defined('ALLOWLOAD')) {
    die();
}

echo '
<style nonce="' . SCRIPT_NONCE . '">
.content{
    background-color: #fff;
    color: #000;
    padding: 10px;
    border: 1px solid #eee;
    white-space: pre;
}
.info{
    display: grid;
    grid-template-columns: max-content 1fr;
}
.info span{
    grid-column: 1 / -1;
}
</style>
<h1 class="bottommargin">Etsi useita viestejä</h1>
<form class="modform" action="' . $_SERVER['REQUEST_URI'] . '" method="post">
<fieldset>
<label>Viestien ID:t vapaamuotoisessa formaatissa</label><br>
<textarea id="idtext" name="idtext">' . (!empty($_POST['idtext']) ? $_POST['idtext'] : (!empty($_GET['idtext']) ? $_GET['idtext'] : '')) . '</textarea><br />
<input type="submit" value="Etsi" />
</fieldset>
</form>
<div class="content">';


// If the user has given us the input
if (!empty($_POST['idtext'])) {
    preg_match_all('#\d{6,10}#', $_POST['idtext'], $matches);
    foreach($matches[0] as $match) {
        $id = $db->escape($match);
        $q = $db->q("SELECT *, UNIX_TIMESTAMP(time) AS time
            FROM (
                SELECT id, user_id, ip, message, time, remote_port
                FROM post_deleted
                WHERE `id` = " . (int)$id . "
                UNION
                SELECT p.id, p.user_id, p.ip, p.message, p.time, p.remote_port
                FROM post p
                WHERE p.id = " . (int)$id . "
            ) a
            LIMIT 1");
        echo '<div class="info"><b>Viestin nro. </b>' . $match;
        if ($q->num_rows != 0) {
            $post = $q->fetch_assoc();
            echo '<br><b>Käyttäjän ID: </b>' . $post['user_id'] . '<br><b>Lähetetty: </b>' . date('c', $post['time']) . '<br><b>IP-osoite: </b>' . inet_ntop($post['ip']) . (!empty($post['remote_port']) ? ':' . $post['remote_port'] : '') . '<br><b>Alue: </b>' . htmlspecialchars($post['boardname']);
            if (!empty($post['subject'])){
                echo '<br><b>Aihe: </b>' . htmlspecialchars($post['subject']);
            }
            echo '<br><b>Viesti: </b>' . htmlspecialchars($post['message']) . '';
        } else {
            echo '<span>Viestiä ei ole olemassa tai se on poistettu yli kuukausi sitten, joten tietoja ei ole enää saatavilla.</span>';
        }
        echo '</div><hr>';
    }
}
echo '</div>';


