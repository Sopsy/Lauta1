<?php
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

include("../public/inc/engine.class.php");
new Engine(['db' => '', 'fileupload' => '']);

$q = $db->q("SELECT id, extension FROM file");

while ($file = $q->fetch_assoc()) {
    $fileName = str_pad(base_convert($file['id'], 10, 36), 5, '0', STR_PAD_LEFT);
    $folder = $fileName[0] . '/' . $fileName[1] . '/' . $fileName[2];

    $src = $engine->cfg->filesDir . '/' . $folder . '/' . $fileName . '.' . $file['extension'];

    if (!is_file($src) || filesize($src) == 0) {
        //$qb = $db->q("DELETE FROM post_file WHERE file_id = ". $file['id']);
        echo $file['id'] . ": source file does not exist...\n";
    }
}