<?php

$files = glob('../files/full/*/*.png');

$i = 0;
foreach ($files AS $file) {

    shell_exec('pngcrush -reduce -fix -rem alla -l 9 -q ' . escapeshellarg($file) . ' ' . escapeshellarg($file . '.tmp.png'));
    if (is_file($file . '.tmp.png') && filesize($file . '.tmp.png') !== 0) {
        unlink($file);
        rename($file . '.tmp.png', $file);
    } else {
        echo "\nERROR: " . $file . "\n";
    }

    ++$i;
    if ($i % 100 === 0) {
        echo '.';
    }
    if ($i % 1000 === 0) {
        echo "\n" . $file . "\n";
    }
}
