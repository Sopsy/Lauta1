<?php
die();
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

include(__DIR__ . "/../public/inc/engine.class.php");
new Engine(['db'=>'','user'=>'']);

$q = $db->q("SELECT id FROM user WHERE gold_account_expires >= '9001-01-01'");

while($user = $q->fetch_assoc()) {
    $text = '';
    $user->addNotification('announcement', $user['id'], 'NULL', $text);
}