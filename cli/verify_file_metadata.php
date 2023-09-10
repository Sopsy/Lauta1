<?php

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

include("../public/inc/engine.class.php");
new Engine(['db' => '', 'fileupload' => '']);

$q = $db->q("SELECT id, extension, duration, has_sound FROM file ORDER BY id DESC");

$i = 0;
while ($file = $q->fetch_assoc()) {
    ++$i;
    if ($i % 1000 == 0) {
        echo 'Processing... (' . $file['id'] . ')' . "\n";
    }

    $fileName = str_pad(base_convert($file['id'], 10, 36), 5, '0', STR_PAD_LEFT);
    $folder = $fileName[0] . '/' . $fileName[1] . '/' . $fileName[2];

    $src = $engine->cfg->filesDir . '/' . $folder . '/' . $fileName . '.' . $file['extension'];

    if (!file_exists($src)) {
        echo "File missing: {$src}\n";
        continue;
    }

    if (empty($file['duration'])) {
        $file['duration'] = 'NULL';
    }
    if (empty($file['has_sound'])) {
        $file['has_sound'] = 'NULL';
    }

    $duration = 'NULL';
    $has_sound = 'NULL';

    if (in_array($file['extension'], ['jpg', 'jpeg', 'png'])) {
        $imagesize = getimagesize($src);
    }

    if ($file['extension'] == 'm4a') {
        // Get duration with ffprobe
        $streams = shell_exec('nice --adjustment=19 ffprobe -hide_banner -loglevel warning -of json -show_streams ' . escapeshellarg($src));
        $streams = json_decode($streams, true)['streams'];

        $duration = (int)round($streams[0]['duration']);
    }
    if ($file['extension'] == 'mp4') {
        $probe = shell_exec('nice --adjustment=19 ffprobe -hide_banner -loglevel warning -show_streams -of json ' . escapeshellarg($src) . ' -v quiet');
        $videoInfo = json_decode($probe, true);

        if (empty($videoInfo['streams'])) {
            echo 'ERROR (empty streams): ' . $file['id'] . "\n";
            continue;
        }

        $videoInfo = $videoInfo['streams'];

        $streamNum = false;
        $has_sound = 0;
        foreach ($videoInfo AS $key => $stream) {
            if ($stream['codec_type'] == 'video' && empty($streamNum)) {
                $streamNum = $key;
            } elseif ($stream['codec_type'] == 'audio') {
                $has_sound = 1;
            }
        }

        if (empty($videoInfo[$streamNum]['duration']) || $videoInfo[$streamNum]['duration'] == 'N/A') {
            $videoInfo[$streamNum]['duration'] = (int)shell_exec('nice --adjustment=19 ffmpeg -i ' . escapeshellarg($src) . ' 2>&1 | grep "Duration"| cut -d " " -f 4 | sed s/,// | sed "s@\..*@@g" | awk \'{ split($1, A, ":"); split(A[3], B, "."); print 3600*A[1] + 60*A[2] + B[1] }\'');
        }

        $duration = (int)$videoInfo[$streamNum]['duration'];
    }

    $update = false;
    if ($duration != $file['duration']) {
        $update = true;
        echo $file['id'] . ' Duration mismatch (' . $duration . ' <- ' . $file['duration'] . ")\n";
    }
    if ($has_sound != $file['has_sound']) {
        $update = true;
        echo $file['id'] . ' HasSound mismatch (' . $has_sound . ' <- ' . $file['has_sound'] . ")\n";
    }

    if (!$update) {
        continue;
    }

    $db->q("UPDATE files
            SET duration = " . $duration . ", has_sound = " . $has_sound . "
			WHERE id = " . (int)$file['id'] . " LIMIT 1");
}
