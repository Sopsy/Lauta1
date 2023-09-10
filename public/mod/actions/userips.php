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
<h1 class="bottommargin">Etsi käyttäjän IP osoitteet</h1>
<form class="modform" action="' . $_SERVER['REQUEST_URI'] . '" method="post">
<fieldset>
<label>Käyttäjän ID:</label><br>
<input type="text" id="uid" name="uid" value="' . (!empty($_POST['uid']) ? $_POST['uid'] : (!empty($_GET['uid']) ? $_GET['uid'] : '')) . '" /><br /><br />
<input type="submit" value="Etsi" />
</fieldset>
</form>
<div class="content">';


// If the user has given us the input
if (!empty($_POST['uid'])) {
    $uid = $db->escape($_POST['uid']);
    $q2 = $db->q("SELECT CONCAT(INET6_NTOA(ip), ':', remote_port) as ipa, MAX(UNIX_TIMESTAMP(time)) AS time FROM (SELECT ip, remote_port, time FROM post_deleted WHERE `user_id` = " . (int)$uid . " UNION SELECT ip, remote_port, time FROM posts WHERE `userId` = " . (int)$uid . ') a GROUP BY ipa');
    while ($post = $q2->fetch_assoc()) {
        echo '<b>Aika: </b>' . date('c', $post['time']) . '<br><b>IP-osoite: </b>' . $post['ipa'];
        echo '<hr>';
    }
}
echo '</div>';


