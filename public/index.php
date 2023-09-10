<?php

include("inc/engine.class.php");
new Engine(['cache' => '', 'db' => '', 'posts' => '', 'user' => 'index', 'html' => '']);

$html->printHeader();

// Tabs
$qb = $db->q("SELECT * FROM info_category FORCE INDEX (`order`) ORDER BY `position` ASC");
$categories = $db->fetchAll($qb);

foreach ($categories AS $category) {
    if (isset($_GET[$category['url']])) {
        $currentCat = $category;
        break;
    }
}
// Get category content
if (!isset($currentCat['id'])) {
    $currentCatId = 0;
} // 0 = Front page
else {
    $currentCatId = $currentCat['id'];
}

$html->printSidebar();

echo '
	<div id="right" class="front">
		<div id="title">';
if ($currentCatId == 0) {
    echo '
                <img src="' . $engine->cfg->staticUrl . $engine->cfg->siteLogoUrl . '" width="175" alt="">
                <h1>' . $engine->cfg->siteName . '</h1>
                <h2>' . $engine->cfg->siteMotto . ' - ' . sprintf(_('%s sent messages already!'),
            number_format($engine->getNewestPostId(), 0, ',', ' ')) . '</h2>';
} else {
    echo '<h1>' . $currentCat['description'] . '</h1>';
}
echo '</div>';
echo $html->print_ad('frontpage', 2);

$qb = $db->q("SELECT * FROM `info_content` WHERE `category_id` " . ($currentCatId == 0 ? 'IS NULL' : ' = ' . (int)$currentCatId) . " ORDER BY `position` ASC, `time_added` DESC");
$posts = $db->fetchAll($qb);

echo '
<div class="news">';

foreach ($posts AS $post) {
    echo '
	<h3' . (!$currentCatId ? ' class="center"' : '') . '>' . $post['subject'] . '</h3>
	<div class="newstext' . (!$currentCatId ? ' center' : '') . '">' . (!$post['is_html'] ? nl2br($post['text']) : $post['text']) . '</div>';
}

echo '
</div>';

if (!isset($currentCat)) {
    $mv = $db->get_mv(['onlinecount', 'onlinecount_total', 'postcount_hour']);
    $onlineCount = json_decode($mv->onlinecount, true);

    // Board list
    echo '
    <div class="box">
        <h3>
            <span>' . _('Boards') . '</span>
            <span class="statslink"><a href="/graphs.php?online">' . sprintf(_('%s users online'),
            $mv->onlinecount_total) . '</a></span>
            <span class="statslink" ><a href="/graphs.php?postcount">' . sprintf(_('%s messages during the last hour'),
            $mv->postcount_hour) . '</a></span>
        </h3>';

    $boardList = $html->getBoardList(true);

    echo '
		<nav id="front-boards">';

    foreach ($boardList AS $board) {
        if (empty($onlineCount[$board['boardid']])) {
            $boardOnlineCount = 0;
        } else {
            $boardOnlineCount = $onlineCount[$board['boardid']];
        }

        $boardOnlineCount .= ' ' . ($boardOnlineCount != 1 ? _('readers') : _('reader'));

        $rowClass = '';
        if ($board['is_hidden']) {
            $rowClass .= ' hidden';
        }

        echo '
            <a class="' . $rowClass . '" href="' . $engine->cfg->siteUrl . '/' . $board['url'] . '/">
                <span class="boardname">' . $board['boardname'] . '</span>
                <span class="description">' . $board['description'] . '</span>';

        if ($user->hasGoldAccount) {
            echo '<span class="onlinecount">' . $boardOnlineCount . '</span>';
        }

        echo '</a>';

    }
    echo '
        </nav>
    </div>';

    // Popular threads hour
    $q = $db->q("SELECT view_value FROM materialized_view WHERE view_key = 'popular_hour' LIMIT 1");
    try {
        $popular_hour = json_decode($q->fetch_assoc()['view_value'] ?? '', true);
    } catch (\Throwable $e) {
        $popular_hour = [];
    }

    // Popular threads day
    $q = $db->q("SELECT view_value FROM materialized_view WHERE view_key = 'popular_day' LIMIT 1");
    try {
        $popular_day = json_decode($q->fetch_assoc()['view_value'] ?? '', true);
    } catch (\Throwable $e) {
        $popular_day = [];
    }

    // Print boxes
    function print_fp_box_rows($data, $type = false)
    {
        global $engine;

        if (empty($data)){
            return;
        }

        foreach ($data AS $row) {
            echo '<p class="fp-box-p">' .
                '<a href="' . $engine->cfg->siteUrl . '/' . $row['url'] . '/"><span class="boardname">' . $row['boardname'] . '</span></a>: ' .
                '<a href="' . $engine->cfg->siteUrl . '/' . $row['url'] . '/' . $row['id'] . '"> ' . htmlspecialchars(empty(trim($row['subject'])) ? '-' : $row['subject']) . '</a>';
            echo '</p>';
        }
    }

    echo '
<div class="boxes fp-boxes">
    <div class="fp-box-div">
        <h3>' . _('Popular threads right now') . '</h3>',
    print_fp_box_rows($popular_hour, 1), '
    </div>
    <div class="fp-box-div">
        <h3>' . _('Popular threads today') . '</h3>',
    print_fp_box_rows($popular_day, 1), '
    </div>
</div>';
}


echo '</div>';