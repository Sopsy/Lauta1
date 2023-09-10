<?php
if (!defined('ALLOWLOAD')) {
    die();
}

echo '
<h1 class="bottommargin">UID käyttäjänimeksi</h1>
<form class="modform" action="' . $_SERVER['REQUEST_URI'] . '" method="post">
<fieldset>
<label for="id">UID</label>
<input type="text" id="id" name="id" value="' . (!empty($_POST['id']) ? $_POST['id'] : (!empty($_GET['id']) ? $_GET['id'] : '')) . '" /><br />
<input type="submit" value="Hae" />
</fieldset>
</form>';

// If the user has given us the input
if (!empty($_POST['id'])) {
    $q = $db->q("SELECT `username` FROM user WHERE `id` = '" . $db->escape($_POST['id']) . "' LIMIT 1");
    $row = $q->fetch_assoc();
    if (!$row) {
        echo '<p>Tunnusta ei löydy</p>';
    } else {
        echo '<p>Tunnus: ' . $row['username'] . '</p>';
    }
}

echo '
<h1>Käyttäjänimi UID:ksi</h1>
<form class="modform" action="' . $_SERVER['REQUEST_URI'] . '" method="post">
<fieldset>
<label for="uname">Käyttäjänimi</label>
<input type="text" id="uname" name="uname" value="' . (!empty($_POST['uname']) ? $_POST['uname'] : (!empty($_GET['uname']) ? $_GET['uname'] : '')) . '" /><br />
<input type="submit" value="Hae" />
</fieldset>
</form>';

// If the user has given us the input
if (!empty($_POST['uname'])) {
    $q = $db->q("SELECT `id` FROM user WHERE `username` LIKE '" . $db->escape($_POST['uname']) . "' LIMIT 1");
    $row = $q->fetch_assoc();
    if (!$row) {
        echo '<p>Tunnusta ei löydy</p>';
    } else {
        echo '<p>UID: ' . $row['id'] . '</p>';
    }
}
