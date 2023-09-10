<?php

$files = glob(__DIR__ . '/../files/full/*/*.mp4');

$i = 0;
foreach ($files AS $file) {
    $size_before = filesize($file);

    echo $file . ': ' . round($size_before / 1024,2) . 'KB';

    $tmpfile = sys_get_temp_dir() . '/video-' . time() . random_int(000000, 999999) . '.mp4';

    $log = shell_exec('nice --adjustment=19 ffmpeg -hide_banner -loglevel warning -i ' . escapeshellarg($file) . ' -threads 0' .
        ' -sn -dn -map_metadata -1 -c:v libx264 -pix_fmt yuv420p -crf 28 -preset:v slow -profile:v high -level:v 5.1' .
        ' -filter_complex "scale=854:480:force_original_aspect_ratio=decrease,pad=854:480:(ow-iw)/2:(oh-ih)/2,setsar=1" -aspect 16:9 -movflags faststart' .
        ' -c:a aac -ac 2 -ar 44100 -b:a 96k -max_muxing_queue_size 1024 ' . escapeshellarg($tmpfile) . ' 2>&1');

    if (is_file($tmpfile) && filesize($tmpfile) !== 0) {
        $size_after = filesize($tmpfile);

        echo ' -> ' . round($size_after / 1024, 2) . 'KB (' .
            round(100 - ($size_after / $size_before) * 100, 2) . "% reduction) \n";

        unlink($file);
        rename($tmpfile, $file);
    } else {
        echo " - ERROR\n";
    }

    if (!empty(trim($log))) {
        echo $log . "\n";
    }
}