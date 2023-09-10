<?php
if (!defined('ALLOWLOAD')) {
    die();
}

$qb = $db->q('
    SELECT id, username, account_created, INET6_NTOA(last_ip) AS last_ip, email, is_suspended
    FROM user
    WHERE is_suspended = 1
    ORDER BY account_created DESC
');
$entries = $db->fetchAll($qb);


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
window.onload = () => {
    let lock_buttons = document.querySelectorAll(".lock");
    
    for (let lock_button of lock_buttons) {
        lock_button.addEventListener("click", lock);
    }
};
</script>

<h1>' . _('Locked users') . '</h1>

<table class="pure-table pure-table-striped">
<thead><tr>
    <th>' . _('Id') . '</th>
    <th>' . _('Username') . '</th>
    <th>' . _('Account created') . '</th>
    <th>' . _('Last IP') . '</th>
    <th>' . _('Has email') . '</th>
    <th>' . _('Actions') . '</th>
</tr></thead><tbody>';

foreach ($entries AS $entry) {
    echo '
    <tr>
        <td>' . $entry['id'] . '</td>
        <td>' . htmlspecialchars($entry['username']) . '</td>
        <td>' . $entry['account_created'] . '</td>
        <td><a href="https://ylilauta.org/ip.php?ip=' . $entry['last_ip'] . '" target="_blank">' . $entry['last_ip'] . '</a></td>
        <td>' . (empty($entry['email']) ? _('No') : _('Yes')) . '</td>
        <td>
            <button class="lock" data-locked="' . ($entry['is_suspended'] ? 'true' : 'false') . '" data-id="' . $entry['id'] . '">
            ' . ($entry['is_suspended'] ? _('Unlock account') : _('Lock account')) . '
            </button>
        </td>
    </tr>';
}
echo '</tbody></table>';
