<?php

include("inc/engine.class.php");
new Engine(['cache' => '', 'db' => '']);

$threadsPerPage = 10000;
$boards = $db->q('SELECT url FROM board WHERE id NOT IN (74,98) ORDER BY name ASC');

if (empty($_GET['name'])) {
    // Sitemap index
    header('Content-Type: text/xml');
    echo '<?xml version="1.0" encoding="UTF-8"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

    $time = date('c');
    echo "<sitemap><loc>{$engine->cfg->siteUrl}/sitemap.php?name=default</loc><lastmod>{$time}</lastmod></sitemap>";
    while ($row = $boards->fetch_assoc()) {
        $threadCount = $db->q('SELECT COUNT(*) AS count
            FROM thread a
            LEFT JOIN board b ON b.id = a.board_id
            WHERE b.url = "' . $db->escape($row['url']) . '"
            LIMIT 1');
        $threadCount = (int)$threadCount->fetch_assoc()['count'];
        if ($threadCount === 0) {
            $threadCount = 1;
        }
        $pages = ceil($threadCount / $threadsPerPage);

        for ($page = 1; $page <= $pages; ++$page) {
            echo "<sitemap><loc>{$engine->cfg->siteUrl}/sitemap.php?name={$row['url']}&amp;page={$page}</loc><lastmod>{$time}</lastmod></sitemap>";
        }
    }

    echo '</sitemapindex>';
    echo '<!-- ' . round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 4)  . 's -->';
    die();
}

if ($_GET['name'] == 'default') {
    $time = date('c');
    header('Content-Type: text/xml');
    echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    echo "<url><loc>{$engine->cfg->siteUrl}/</loc><lastmod>{$time}</lastmod></url>";
    echo "<url><loc>{$engine->cfg->siteUrl}/allthreads</loc><lastmod>{$time}</lastmod></url>";
    echo "<url><loc>{$engine->cfg->siteUrl}/gold</loc></url>";
    echo "<url><loc>{$engine->cfg->siteUrl}/?saannot</loc></url>";
    echo "<url><loc>{$engine->cfg->siteUrl}/?tietoa</loc></url>";
    echo "<url><loc>{$engine->cfg->siteUrl}/?mainostus</loc></url>";
    echo "<url><loc>{$engine->cfg->siteUrl}/?english</loc></url>";
    while ($row = $boards->fetch_assoc()) {
        echo "<url><loc>{$engine->cfg->siteUrl}/{$row['url']}/</loc><lastmod>{$time}</lastmod></url>";
        for ($i = 2; $i <= 10; ++$i) {
            echo "<url><loc>{$engine->cfg->siteUrl}/{$row['url']}-{$i}/</loc><lastmod>{$time}</lastmod></url>";
        }
    }
    echo '</urlset>';
} else {
    $page = $_GET['page'] ?? 1;
    $page = (int)$page;
    if (empty($_GET['page']) || $page < 1) {
        http_response_code(404);
        die();
    }

    $_GET['name'] = strtolower(preg_replace('/[^a-z_\-0-9]/', '', $_GET['name']));
    $threads = $db->q('SELECT url, a.id, bump_time FROM thread a
        LEFT JOIN board b ON b.id = a.board_id
        WHERE b.url = "' . $db->escape($_GET['name']) . '"
        ORDER BY a.bump_time DESC
        LIMIT ' . ((int)$page - 1) * $threadsPerPage . ', ' . $threadsPerPage);

    if ($threads->num_rows === 0 && $page !== 1) {
        http_response_code(404);
        die();
    }

    header('Content-Type: text/xml');
    echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

    while ($row = $threads->fetch_assoc()) {
        $time = date('c', strtotime($row['bump_time']));
        echo "<url><loc>{$engine->cfg->siteUrl}/{$row['url']}/{$row['id']}</loc><lastmod>{$time}</lastmod></url>";
    }
    echo '</urlset>';
}
echo '<!-- ' . round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 4)  . 's -->';