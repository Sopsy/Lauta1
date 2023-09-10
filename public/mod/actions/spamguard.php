<?php
if (!defined('ALLOWLOAD')) {
    die();
}

if (empty($_GET['id'])) {
    $qb = $db->q('
    SELECT
        id, username, account_created, INET6_NTOA(last_ip) AS last_ip, email, is_suspended,
        (SELECT COUNT(*) FROM user WHERE last_ip = a.last_ip AND `username` IS NOT NULL) AS ip_count
    FROM user a
    WHERE password IS NOT NULL AND
        (account_created > DATE_SUB(NOW(),INTERVAL 1 WEEK))
    ORDER BY account_created DESC');
} else {
    $qb = $db->q('
    SELECT
        id, username, account_created, INET6_NTOA(last_ip) AS last_ip, email, is_suspended, 0 AS ip_count
    FROM user
    WHERE password IS NOT NULL AND last_ip = (SELECT last_ip FROM user WHERE id = '. (int)$db->escape($_GET['id']) .')
    ORDER BY account_created DESC');
}
$entries = $db->fetchAll($qb);
$q = $db->q('SELECT * FROM antispam LIMIT 1');
$antispam = $q->fetch_assoc();
$timeleft = (43200 - (time() - (int)(new DateTimeImmutable($antispam['enabled_time']))->getTimestamp()));

if ($timeleft <= 0) {
    $db->q('UPDATE antispam SET enabled = 0');
    $q = $db->q('SELECT * FROM antispam LIMIT 1');
    $antispam = $q->fetch_assoc();
    $timeleft = (43200 - (time() - (int)(new DateTimeImmutable($antispam['enabled_time']))->getTimestamp()));
}

echo '
<script type="module" nonce="' . SCRIPT_NONCE . '">
import {Ajax} from "' . $engine->cfg->staticUrl . '/js/Module/Library/Ajax.js";
import {Toast} from "' . $engine->cfg->staticUrl . '/js/Module/Library/Toast.js";
function lock(e) {
    if (e.target.dataset.locked === "true") {
        Ajax.post("/scripts/ajax/spamguard.php", {unlock: e.target.dataset.id}).onLoad((xhr)=>{Toast.success(xhr.responseText)});
        e.target.innerHTML= "' . _('Lock account') . '";
        e.target.dataset.locked = "false";
    } else {
        Ajax.post("/scripts/ajax/spamguard.php", {lock: e.target.dataset.id}).onLoad((xhr)=>{Toast.success(xhr.responseText)});
        e.target.innerHTML= "' . _('Unlock account') . '";
        e.target.dataset.locked = "true";
    }
}

function antispam(e) {
    if (e.target.dataset.enabled === "true") {
        Ajax.post("/scripts/ajax/spamguard.php", {antispam: 1}).onLoad((xhr)=>{Toast.success(xhr.responseText)});
        e.target.innerHTML= "' . _('Disable') . '";
        e.target.dataset.enabled = "false";
        document.getElementById("spamtimer").textContent = "43200";
    } else {
        Ajax.post("/scripts/ajax/spamguard.php", {antispam: 0}).onLoad((xhr)=>{Toast.success(xhr.responseText)});
        e.target.innerHTML= "' . _('Enable') . '";
        document.getElementById("spamtimer").textContent = "0";
        e.target.dataset.enabled = "true";
    }
}

function clock() {
    let time = document.getElementById("spamtimer");
    let val = parseInt(time.innerText);
    val = val - 1;
    time.innerText = Math.max(val, 0).toString();
}

window.onload = () => {
    let lock_buttons = document.querySelectorAll(".lock");
    
    for (let lock_button of lock_buttons) {
        lock_button.addEventListener("click", lock);
    }
    
    let spam_button = document.getElementById("spam");
    spam_button.addEventListener("click", antispam);
    setInterval(clock, 1000);
};
</script>
<h1>' . _('New users') . '</h1>';

echo _('Anti spam:')
    . ' <span id="spamtimer" style="margin: 0">' . ($antispam['enabled'] ? $timeleft : 0) . '</span><button id="spam" data-enabled="' . ($antispam['enabled'] ? 'false' : 'true') . '">'
    . ($antispam['enabled'] ? _('Disable') : _('Enable'))
    . '</button>
<table class="pure-table pure-table-striped">
<thead><tr>
    <th>' . _('Id') . '</th>
    <th>' . _('Username') . '</th>
    <th>' . _('Account created') . '</th>
    <th>' . _('Has email') . '</th>
    <th>' . _('Last IP') . '</th>
    <th>' . _('Accounts from IP') . '</th>
    <th>' . _('Actions') . '</th>
</tr></thead><tbody>';

foreach ($entries AS $entry) {
    echo '
    <tr>
        <td>' . $entry['id'] . '</td>
        <td>' . htmlspecialchars($entry['username']) . '</td>
        <td>' . $entry['account_created'] . '</td>
        <td>' . (empty($entry['email']) ? _('No') : _('Yes')) . '</td>
        <td><a href="/ip.php?ip=' . $entry['last_ip'] . '" target="_blank">' . $entry['last_ip'] . '</a></td>
        <td><a href="/mod/index.php?action=spamguard&id=' . $entry['id'] . '">' . $entry['ip_count'] . '</a></td>
        <td>
            <button class="lock" data-locked="' . ($entry['is_suspended'] ? 'true' : 'false') . '" data-id="' . $entry['id'] . '">
            ' . ($entry['is_suspended'] ? _('Unlock account') : _('Lock account')) . '
            </button>
        </td>
    </tr>';
}
echo '</tbody></table>';
