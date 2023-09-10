<?php
$loadClasses = [
    'cache' => '',
    'db' => '',
    'user' => '',
    'html' => '',
];
include("inc/engine.class.php");
new Engine($loadClasses);
$html->printHeader();
$html->printSidebar();

?>
<div id="right" class="graphs center">
<?php
if (isset($_GET['online'])) {
    // Online counter
    ?>
    <h1>Active users</h1>
    <p><em>Times in UTC</em></p>
    <p>Real-time active users</p>
    <img src="<?= $engine->cfg->rrdGraphOutputUrl ?>/users_day.svg" />
    <p>Real-time active users</p>
    <img src="<?= $engine->cfg->rrdGraphOutputUrl ?>/users_month.svg" />
    <p>Daily active users</p>
    <img src="<?= $engine->cfg->rrdGraphOutputUrl ?>/users_year.svg" />
    <p>Monthly active users</p>
    <img src="<?= $engine->cfg->rrdGraphOutputUrl ?>/users_alltime.svg" />
    <?php

} elseif (isset($_GET['postcount'])) {
    // Post amount counter
    ?>
    <h1>Number of posts</h1>
    <p><em>Times in UTC</em></p>
    <p>Hourly posts</p>
    <img src="<?= $engine->cfg->rrdGraphOutputUrl ?>/posts_day.svg" />
    <p>Hourly posts</p>
    <img src="<?= $engine->cfg->rrdGraphOutputUrl ?>/posts_month.svg" />
    <p>Daily posts</p>
    <img src="<?= $engine->cfg->rrdGraphOutputUrl ?>/posts_year.svg" />
    <p>Monthly posts</p>
    <img src="<?= $engine->cfg->rrdGraphOutputUrl ?>/posts_alltime.svg" />

    <?php
} else {
    ?>
    <h1>Ylilauta statistics</h1>
    <p><a href="/graphs.php?online">Active users</a></p>
    <p><a href="/graphs.php?postcount">Number of posts</a></p>
    <?php
}
?>
</div>

<?php
