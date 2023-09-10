<?php

$cachefile = '/tmp/bot_ip_whitelist.json';
if (is_file($cachefile) && filemtime($cachefile) < time() - 86400) {
    unlink($cachefile);
}

if (is_file($cachefile)) {
    return json_decode(file_get_contents($cachefile), true);
}

$data = shell_exec("whois -h whois.radb.net -- '-i origin AS32934' | grep ^route");
$data = explode("\n", $data);

$ips = [];
foreach ($data as $row) {
    $row = explode('     ', $row);
    $ip = trim(array_pop($row));
    if (empty($ip)) {
        continue;
    }

    $ips[] = $ip;
}

file_put_contents($cachefile, json_encode($ips));
return $ips;
