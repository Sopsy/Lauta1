<?php
if (!defined('ALLOWLOAD')) {
    die();
}

echo '
<h1>Mustalistatut sanat</h1>
<p class="bottommargin">Jos käyttäjä lähettää jonkin luettelosta esiintyvistä sanoista, viestin lähettäminen estetään ja käyttäjälle annetaan automaattisesti ' . $engine->cfg->wordAutobanLength . ' sekunnin pituinen porttikielto.</p>';

if (!empty($_POST['savewords'])) {
    $words = explode("\n", str_replace("\r\n", "\n", trim($_POST['words'])));

    $i = 0;
    $qWords = '';
    foreach ($words AS $word) {
        if (mb_strlen($word) == 0) {
            continue;
        }
        $qWords .= ($i != 0 ? "'), ('" : '') . $db->escape($word);
        ++$i;
    }

    $qa = $db->q("DELETE FROM word_blacklist");
    if (!$qa) {
        die("<h2>Tietokantakysely epäonnistui.</h2>");
    }

    $qb = $db->q("INSERT IGNORE INTO word_blacklist (word) VALUES ('" . $qWords . "')");
    if ($qb) {
        echo "<h2>Sanaluettelo tallennettu.</h2>";
    } else {
        echo "<h2>Sanaluettelon tallennus epäonnistui!</h2>";
    }

    $engine->writeModlog(18, 'Blacklisted word count: ' . count($words));
}

echo '
<form class="modform pure-form" action="' . $_SERVER['REQUEST_URI'] . '" method="post">
<label for="words">Sanaluettelo</label>
<textarea name="words" id="words" class="pure-input-1">';

$q = $db->q("SELECT * FROM word_blacklist");

$a = 0;
while ($autoBan = $q->fetch_assoc()) {
    echo ($a != 0 ? "\r\n" : '') . $autoBan['word'];
    ++$a;
}

echo '</textarea>
<input type="submit" name="savewords" class="pure-button pure-button-primary" value="Tallenna" />
</form>';
