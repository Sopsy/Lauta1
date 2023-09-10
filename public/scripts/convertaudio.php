<?php

$load = sys_getloadavg();
while ($load[0] > 10) {
    sleep(random_int(1,10));
    $load = sys_getloadavg();
}

if (php_sapi_name() !== 'cli') {
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

$tmpSrc = sys_get_temp_dir() . '/audio-' . time() . random_int(000000, 999999) . '.mp4';
$tmpDest = sys_get_temp_dir() . '/audio-' . time() . random_int(000000, 999999) . '.mp4';

rename($file, $tmpSrc);
if (!is_file($tmpSrc)) {
    file_put_contents('/tmp/convert.log', '[ERROR] ' . $fileId . ': move before conversion failed' . "\n", FILE_APPEND);
    die();
}

// Figure out data from the file
$streams = shell_exec('nice --adjustment=19 ffprobe -loglevel warning -show_streams -show_format -of json ' . escapeshellarg($tmpSrc));
$streams = json_decode($streams, true);
$stream = $streams['streams'][0];
$format = $streams['format'];

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
if (!empty($stream['bit_rate'])) {
    $streamBitrate = (int)$stream['bit_rate'];
    if ($streamBitrate !== 0) {
        $streamBitrate = round((int)$stream['bit_rate']/1000, 0)*1000;
        $inFmt = $format['format_name'];
        if ($stream['codec_name'] == 'aac') {
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
if ((int)$stream['channels'] == 1) {
    $channels = 1;
}

// Figure out sample rate
$sampleRate = 44100;
if (!empty($stream['sample_rate'])) {
    $streamSampleRate = (int)$stream['sample_rate'];
    if ($streamSampleRate !== 0) {
        if ($streamSampleRate < 9000) {
            $sampleRate = 8000;
        } elseif ($streamSampleRate < 12000) {
            $sampleRate = 11025;
        } elseif ($streamSampleRate < 23000) {
            $sampleRate = 22050;
        } else {
            $sampleRate = 44100;
        }
    }
}

$fmt = ' -ac ' . escapeshellarg($channels) . ' -ar ' . escapeshellarg($sampleRate) . ' -b:a ' . escapeshellarg((int)$bitrate/1000 . 'k');
if ($audioCodec == 'copy') {
    $fmt = '';
}
file_put_contents('/tmp/convert.log', '[INFO] ' . $fileId . ': audio conversion starting (codec: ' . $audioCodec . ', channels: ' . $channels .
    ', samplerate: ' . $sampleRate . ', bitrate: ' . $bitrate . ")\n", FILE_APPEND);

// Convert
$log = shell_exec('nice --adjustment=19 ffmpeg -hide_banner -loglevel warning -f ' . escapeshellarg($inFmt) .
    ' -i ' . escapeshellarg($tmpSrc) . ' -threads 0' .
    ' -vn -sn -dn -map_metadata -1' . // No video, no subtitles, no data
    ' -c:a ' . escapeshellarg($audioCodec) . $fmt . ' -movflags faststart' .
    ' ' . escapeshellarg($tmpDest) . ' 2>&1');

$log = explode("\n", $log);
foreach ($log as $line) {
    if (empty($line)) {
        continue;
    }
    file_put_contents('/tmp/convert.log', '[WARN] ' . $fileId . ': ' . $line . "\n", FILE_APPEND);
}

if (!is_file($tmpDest)) {
    file_put_contents('/tmp/convert.log', '[ERROR] ' . $fileId . ': audio conversion failed' . "\n", FILE_APPEND);
    die();
}
unlink($tmpSrc);
rename($tmpDest, $file);

if (!is_file($file)) {
    file_put_contents('/tmp/convert.log', '[ERROR] ' . $fileId . ': copy after conversion failed' . "\n", FILE_APPEND);
    die();
}

$md5 = md5_file($file);
$db->q("INSERT IGNORE INTO `file_md5` (`file_id`, `md5`) VALUES (" . (int)$fileId . ", UNHEX('" . $db->escape($md5) . "'))");
$db->q("DELETE FROM file_processing WHERE file_id = " . (int)$fileId . " LIMIT 1");
