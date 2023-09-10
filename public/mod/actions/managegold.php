<?php
if (!defined('ALLOWLOAD')) {
    die();
}

echo '
<h1 class="bottommargin">Kultatilihallinta</h1>
<br />
<form class="modform" action="' . $_SERVER['REQUEST_URI'] . '" method="post">
<fieldset>
<h2>Luo avain</h2>
<label for="amount">Määrä</label>
<input type="number" name="amount" id="amount" value="1" /><br />
<label for="amount">Käyttäjän ID, jos lisätään käyttäjälle</label>
<input type="text" name="userid" id="userid" /><br />
<label for="length">Kesto sekunteina</label>
<input type="number" name="length" id="length" value="604800" /><br />
<input type="submit" value="Luo" />
</fieldset>
</form>';

// If the user has given us the input
if (!empty($_POST['amount']) && isset($_POST['length'])) {

    $engine->writeModlog(22, 'Count: ' . $_POST['amount'] . ' - Length: ' . $_POST['length']);

    $amount = is_numeric($_POST['amount']) ? $_POST['amount'] : 1;
    $length = is_numeric($_POST['length']) ? $_POST['length'] : 86400;
    $owner_id = !empty($_POST['userid']) ? (int)$_POST['userid'] : 'NULL';
    echo $amount . ' privaattia avainta luotu (kesto ' . (int)$length . ' sek): <br />';
    for ($i = 1; $i <= $amount; $i++) {
        $key = bin2hex(random_bytes(10));
        $q = $db->q("INSERT INTO `gold_key` (`key`, `length`, owner_id) VALUES ('" . $db->escape($key) . "', " . (int)$length . ", " . $owner_id . ")");
        if ($q) {
            echo '<b>' . $key . '</b><br />';
        } else {
            echo 'Fail<br />';
        }
    }
    if (!empty($owner_id)) {
        $user->addNotification('gold_account_get', $owner_id);
    }
}
