<?php

require('mod_functions.php');

$loadClasses = [
    'cache' => '',
    'db' => '',
    'html' => '',
    'posts' => '',
    'fileupload' => '',
    'user' => '',
];
include("../inc/engine.class.php");
new Engine($loadClasses);

if (empty($_GET['action'])) {
    $_GET['action'] = 'bulletinboard';
}

// If the user hasn't been authenticated
if (!$user->isMod) {
    $engine->return_not_found();
}

$html->printHeader(_('Management') . ' | ' . $engine->cfg->siteName);
echo '<link href="' . $engine->cfg->staticUrl . '/css/pure.css" type="text/css" rel="stylesheet">';
echo '<link href="' . $engine->cfg->staticUrl . '/css/management.css?' . time() . '" type="text/css" rel="stylesheet">';
$html->printModbar();

$validModPages = [
    'bulletinboard',
    'postcounts',
    'modlog',
    'admins',
    'manageboards',
    'autobans',
    'searchposts',
    'searchfile',
    'deletedposts',
    'deletepost',
    'updatethread',
    'reportedposts',
    'banappeals',
    'addban',
    'managebans',
    'managegold',
    'announcements',
    'uidname',
    'poststream',
    'multipleposts',
    'userposts',
    'spamguard',
    'lockedusers'
];

$action = $_GET['action'];

echo '
<div id="topbar">
    <button id="e-sidebar-toggle" data-e="sidebarToggle" class="icon-menu"></button>
    <button id="e-sidebar-hide" data-e="sidebarHide"></button>
    <div class="right">';
if ($action === 'poststream') {
    echo '<a><span id ="stream" class="icon-pause-circle" ></span></a>';
}
echo '<a href="/mod/index.php?action=reportedposts" title="' . _('Reports') . '"><span class="icon-flag2"></span></a>
        <a href="/" title="' . _('Preferences and profile') . '"><span class="icon-home3"></span></a>
    </div>
</div>
<div id="right" class="mod">';

if (!in_array($action, $validModPages) || !$user->hasPermissions($action)) {
    echo '<h1>' . _('Insufficient permissions') . '</h1></div>';
    die();
}
;
define('ALLOWLOAD', true);
require('actions/' . $action . '.php');
echo '</div>';
