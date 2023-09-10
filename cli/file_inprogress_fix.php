<?php
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

include(dirname(__DIR__) . "/public/inc/engine.class.php");
new Engine(['db' => '', 'fileupload' => '']);

$q = $db->q("SELECT b.id, b.extension
FROM file_processing a
LEFT JOIN file b ON b.id = a.file_id ORDER BY a.file_id DESC LIMIT 1000");

while ($file = $q->fetch_assoc()) {
    echo 'Processing... (' . $file['id'] . ')' . "\n";

    $fileName = str_pad(base_convert($file['id'], 10, 36), 5, '0', STR_PAD_LEFT);
    $folder = $fileName[0] . '/' . $fileName[1] . '/' . $fileName[2];

    $src = $engine->cfg->filesDir . '/' . $folder . '/' . $fileName . '.';

    if (!file_exists($src . $file['extension'])) {
        echo "File missing: {$src}\n";
        continue;
    }

    if ($file['extension'] === 'm4a') {
        $cmd = 'php ' . escapeshellarg($engine->cfg->siteDir . '/scripts/convertaudio.php') . ' ' . escapeshellarg($src . 'm4a') . ' ' . escapeshellarg($file['id']);
    } elseif ($file['extension'] === 'mp4') {
        $cmd = 'php ' . escapeshellarg($engine->cfg->siteDir . '/scripts/convertvideo.php') . ' ' . escapeshellarg($src . 'mp4') . ' ' . escapeshellarg($file['id']);
    }

    shell_exec(sprintf('%s > /dev/null 2>&1 &', $cmd));
}