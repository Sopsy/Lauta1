<?php
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

include("../public/inc/engine.class.php");
new Engine(['db' => '', 'fileupload' => '']);

/** @noinspection LowPerformingDirectoryOperationsInspection - We want sorted */
$fulls = glob($engine->cfg->filesDir . '/full/*/*.mp4');

$i = 0;
foreach ($fulls AS $file) {
    $thumb = str_replace('.mp4', '.jpg', $file);

    if (empty($argv[1]) || $argv[1] !== '--recreate') {
        if (is_file($thumb)) {
            continue;
        }
    } else {
        @unlink($thumb);
    }

    $thumbname = '/tmp/' . uniqid('', true) . '.jpg';
    shell_exec('nice --adjustment=19 ffmpeg -loglevel panic -i ' . escapeshellarg($file) . ' -vframes 1 -f image2 ' . escapeshellarg($thumbname));
    if (!is_file($thumbname)) {
        echo 'FFMPEG ERROR: ' . $file . "\n";
        continue;
    }

    $thumbnail = $fileupload->createImage($thumbname, $thumb, 3840, 3840);
    if ($thumbnail && is_file($thumb)) {
        $thumbnail = $fileupload->jpegtran($thumb, true);
    }
    unlink($thumbname);

    if ($thumbnail && is_file($thumb) && filesize($thumb) !== 0) {
        echo $thumb . " created\n";
    } else {
        echo 'ERROR: ' . $file . ' ' . $thumbname . ' ' . $thumb . "\n";
    }
}
