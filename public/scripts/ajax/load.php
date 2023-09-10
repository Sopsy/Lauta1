<?php

// Initialize the board engine
$loadClasses = [
    'cache' => '',
    'db' => '',
    'html' => true,
    'posts' => '',
    'user' => false,
];
include("../../inc/engine.class.php");
new Engine($loadClasses);

$db->q("INSERT INTO ban_bot_ip (ip) VALUES (INET6_ATON('" . $_SERVER['REMOTE_ADDR'] . "')) ON DUPLICATE KEY UPDATE hits = hits + 1");
echo bin2hex(random_bytes(32));