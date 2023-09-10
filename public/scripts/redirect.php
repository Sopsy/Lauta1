<?php
header('X-Robots-Tag: noindex');

if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: /');
    die();
}

// Initialize the board engine
include("../inc/engine.class.php");
new Engine(['cache' => '', 'db' => '', 'user' => '', 'html' => '']);

$q = $db->q("SELECT c.url, a.id, a.thread_id
    FROM post a
    LEFT JOIN thread b ON b.id = a.thread_id
    LEFT JOIN board c ON c.id = b.board_id
    WHERE a.id = '" . $db->escape((int)$_GET['id']) . "' LIMIT 1");
if ($q->num_rows == 0) {
    $engine->redirectExit($engine->cfg->siteUrl . '/');
}

$post = $q->fetch_assoc();
$engine->redirectExit($engine->cfg->siteUrl . '/' . $post['url'] . '/' . $post['thread_id'] . '#no' . $post['id']);