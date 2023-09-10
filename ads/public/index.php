<?php
declare(strict_types=1);

$areaId = $_GET['area_id'] ?? 'unknown';
$placementIdM = $_GET['placement_id_m'] ?? 0;
$placementIdD = $_GET['placement_id_d'] ?? 0;

?>
<style>*{margin:0;padding:0}body{display:flex;flex-direction:column;justify-content:center;text-align:center}</style>
<script src="https://s1.adform.net/banners/scripts/adx.js" defer></script>
<script src="https://fcdn.lauta.media/Adform.js?<?= md5_file('Adform.js') ?>" defer></script>
<div data-adf data-placement-id-m="<?= $placementIdM ?>" data-placement-id-d="<?= $placementIdD ?>" data-area-id="<?= $areaId ?>"></div>