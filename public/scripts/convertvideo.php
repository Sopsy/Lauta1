<?php

$load = sys_getloadavg();
while ($load[0] > 10) {
    sleep(random_int(1,10));
    $load = sys_getloadavg();
}

if (PHP_SAPI !== 'cli') {
    die();
}
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

include(__DIR__ . "/../inc/engine.class.php");
new Engine(['db' => '']);

if (empty($argv[1]) || empty($argv[2]) || !is_numeric($argv[2])) {
    die();
}

$file = $argv[1];
$fileId = (int)$argv[2];

$tmpSrc = sys_get_temp_dir() . '/video-' . time() . random_int(000000, 999999) . '.mp4';
$tmpDest = sys_get_temp_dir() . '/video-' . time() . random_int(000000, 999999) . '.mp4';

rename($file, $tmpSrc);
if (!is_file($tmpSrc)) {
    file_put_contents('/tmp/convert.log', '[ERROR] ' . $fileId . ': move before conversion failed' . "\n", FILE_APPEND);
    die();
}

// Figure out data from the file
$streams = shell_exec('nice --adjustment=19 ffprobe -loglevel warning -show_streams -show_format -of json ' . escapeshellarg($tmpSrc));
$streams = json_decode($streams, true);
$format = $streams['format'];

$audioStream = false;
$videoStream = false;
$streams = $streams['streams'];
foreach ($streams as $stream) {
    if ($stream['codec_type'] === 'audio') {
        $audioStream = $stream;
    } elseif ($stream['codec_type'] === 'video') {
        $videoStream = $stream;
    }
}

if ($videoStream === false) {
    file_put_contents('/tmp/convert.log', '[ERROR] ' . $fileId . ' (' . $file . '): video conversion failed (No video streams found)' . "\n", FILE_APPEND);
    file_put_contents('/tmp/convert.log', json_encode($streams) . "\n", FILE_APPEND);
    die();
}

// ---- VIDEO CODEC
$videoCodec = 'libx264';
if ($videoStream['codec_name'] === 'h264' && $videoStream['profile'] === 'High' && $videoStream['codec_tag_string'] === 'avc1'
    && $videoStream['pix_fmt'] === 'yuv420p' && (int)$videoStream['level'] <= 51 && $videoStream['chroma_location'] === 'left'
    && (int)$videoStream['width'] === 1920 && (int)$videoStream['height'] <= 1080
    && (int)$videoStream['coded_width'] === 1920 && (int)$videoStream['coded_height'] <= 1080) {
    //$videoStream = 'copy';
}

if ($videoCodec === 'libx264') {

    $videoWidth = 1920;
    $videoHeight = 1080;
    $preset = 'faster';
    if (!empty($videoStream['width']) && !empty($videoStream['height'])) {
        if ($videoStream['width'] < 426 && $videoStream['height'] < 240) {
            $videoWidth = 256;
            $videoHeight = 144;
            $preset = 'slow';
        } elseif ($videoStream['width'] < 640 && $videoStream['height'] < 360) {
            $videoWidth = 426;
            $videoHeight = 240;
            $preset = 'slow';
        } elseif ($videoStream['width'] < 854 && $videoStream['height'] < 480) {
            $videoWidth = 640;
            $videoHeight = 360;
            $preset = 'medium';
        } elseif ($videoStream['width'] < 1280 && $videoStream['height'] < 720) {
            $videoWidth = 854;
            $videoHeight = 480;
            $preset = 'medium';
        } elseif ($videoStream['width'] < 1920 && $videoStream['height'] < 1080) {
            $videoWidth = 1280;
            $videoHeight = 720;
            $preset = 'fast';
        } else {
            $videoWidth = 1920;
            $videoHeight = 1080;
            $preset = 'faster';
        }
    }

    $videoFmt = '-c:v libx264 -pix_fmt yuv420p -crf 24 -preset:v ' . escapeshellarg($preset) . ' -profile:v high -level:v 5.1' .
        ' -filter_complex "scale=' . escapeshellarg($videoWidth) . ':' . escapeshellarg($videoHeight) . ''
        . ':force_original_aspect_ratio=decrease'
        . ',pad=ceil(iw/2)*2:ceil(ih/2)*2'
        . ',setsar=1"';
} elseif ($videoCodec === 'copy') {
    $videoFmt = '-c:v copy';
} else {
    $videoFmt = '-vn';
}

// ---- AUDIO CODEC
if ($audioStream !== false) {
    $audioCodec = 'aac';
    if ($stream['codec_name'] === 'aac'
        && (
            ($stream['profile'] === 'LC' && (int)$stream['max_bit_rate'] <= 192000)
            || ($stream['profile'] === 'HE-AAC' && (int)$stream['max_bit_rate'] <= 96000)
        )
        && (int)$stream['channels'] <= 2
        && (int)$stream['sample_rate'] <= 96000) {
        $audioCodec = 'copy';
    }

    // Figure out bitrate from the original file, use it if lower than ours
    $bitrate = 192000;
    if (!empty($audioStream['bit_rate'])) {
        $streamBitrate = (int)$audioStream['bit_rate'];
        if ($streamBitrate !== 0) {
            $streamBitrate = round((int)$audioStream['bit_rate'] / 1000, 0) * 1000;
            $inFmt = $format['format_name'];
            if ($audioStream['codec_name'] === 'aac') {
                // AAC, just round down, leave some space so 127999 does not go to 96000.
                if ($streamBitrate < 127000) {
                    $bitrate = 96000;
                } elseif ($streamBitrate < 191000) {
                    $bitrate = 128000;
                } else {
                    $bitrate = 192000;
                }
            } else {
                // MP3, we can almost halve the bitrate and still keep a pretty good quality
                if ($streamBitrate < 191000) {
                    $bitrate = 96000;
                } elseif ($streamBitrate < 255000) {
                    $bitrate = 128000;
                } else {
                    $bitrate = 192000;
                }
            }
        }
    }

    // Figure out channel count
    $channels = 2;
    if ((int)$audioStream['channels'] === 1) {
        $channels = 1;
    }

    // Figure out sample rate
    $sampleRate = 48000;
    if (!empty($audioStream['sample_rate'])) {
        $streamSampleRate = (int)$audioStream['sample_rate'];
        if ($streamSampleRate !== 0) {
            if ($streamSampleRate < 9000) {
                $sampleRate = 8000;
            } elseif ($streamSampleRate < 12000) {
                $sampleRate = 11025;
            } elseif ($streamSampleRate < 23000) {
                $sampleRate = 22050;
            } elseif ($streamSampleRate < 45000) {
                $sampleRate = 44100;
            } else {
                $sampleRate = 48000;
            }
        }
    }

    if ($audioCodec === 'copy') {
        $audioFmt = '-c:a copy';
    } else {
        $audioFmt = '-c:a ' . escapeshellarg($audioCodec) . ' -ac ' . escapeshellarg($channels) . ' -ar ' . escapeshellarg($sampleRate) . ' -b:a ' . escapeshellarg($bitrate / 1000 . 'k');
    }
} else {
    $audioFmt = '-an';
}

// Convert
$log = shell_exec('nice --adjustment=19 ffmpeg -hide_banner -loglevel warning -i ' . escapeshellarg($tmpSrc) .
    ' -sn -dn -map_metadata -1 ' . $videoFmt . ' -movflags faststart ' . $audioFmt . ' -max_muxing_queue_size 9999 '
    . escapeshellarg($tmpDest) . ' 2>&1');

$log = explode("\n", $log);
foreach ($log as $line) {
    if (empty($line)) {
        continue;
    }
    file_put_contents('/tmp/convert.log', '[WARN] ' . $fileId . ': ' . $line . "\n", FILE_APPEND);
}

if (!is_file($tmpDest)) {
    file_put_contents('/tmp/convert.log', '[ERROR] ' . $fileId . ': video conversion failed' . "\n", FILE_APPEND);
    die();
}
unlink($tmpSrc);
rename($tmpDest, $file);

if (!is_file($file)) {
    file_put_contents('/tmp/convert.log', '[ERROR] ' . $fileId . ': move after conversion failed' . "\n", FILE_APPEND);
    die();
}

$md5 = md5_file($file);
$db->q("INSERT IGNORE INTO `file_md5` (`file_id`, `md5`) VALUES (" . (int)$fileId . ", UNHEX('" . $db->escape($md5) . "'))");
$db->q("DELETE FROM file_processing WHERE file_id = " . (int)$fileId . " LIMIT 1");
