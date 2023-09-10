<?php
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

include("../public/inc/engine.class.php");
new Engine(['db' => '', 'fileupload' => '']);

// Delete orphaned files
function deleteOldFiles($files) {
    global $db;

    $i = 1;
    $count = 0;
    foreach ($files AS $file) {
        $filename = pathinfo($file, PATHINFO_FILENAME);
        $q = $db->q("SELECT id FROM file WHERE id = " . (int)base_convert($filename, 36, 10) . " LIMIT 1");
        $res = $q->fetch_assoc();
        if (empty($res) && is_file($file)) {
            @unlink($file);
            echo "\n" . $file . " deleted\n";

            $extension = pathinfo($file, PATHINFO_EXTENSION);

            if ($extension === 'mp4') {
                $thumbFile = preg_replace('/\.mp4$/', '.jpg', $file);
                if (is_file($thumbFile)) {
                    @unlink($thumbFile);
                    echo $thumbFile . " deleted\n";
                }
            }
            ++$count;
        } else {
            if ($i % 1000 == 0) {
                echo '.';
            }
        }
        ++$i;
    }

    return $count;
}

$count = deleteOldFiles(glob($engine->cfg->filesDir . '/*/*/*/*'));