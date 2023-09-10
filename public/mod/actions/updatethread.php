<?php
if (!defined('ALLOWLOAD')) {
    die();
}

if (!empty($_POST['id'])) {
    $_GET['id'] = $_POST['id'];
}
if (!empty($_POST['toid'])) {
    $_GET['toid'] = $_POST['toid'];
}

echo '<h1>' . _('Manage thread options') . '</h1>';

$thread = false;
if (!empty($_POST['id'])) {
    $thread = $posts->getThread($_POST['id']);
} elseif (!empty($_GET['id'])) {
    $thread = $posts->getThread($_GET['id']);
}

if ($thread && !empty($_POST['do'])) {
    $board = $db->q("SELECT * FROM board WHERE id = " . (int)$thread['board_id'] . " LIMIT 1");
    $board = $board->fetch_assoc();

    $do = false;
    if ($_POST['do'] == 'lock' || $_POST['do'] == 'unlock') // Lock / Unlock
    {
        $do = $posts->updateThread('lock', ($_POST['do'] == 'lock' ? true : false), $thread['id']);
    } elseif ($_POST['do'] == 'stick' || $_POST['do'] == 'unstick') // Stick / Unstick
    {
        $do = $posts->updateThread('stick', ($_POST['do'] == 'stick' ? true : false), $thread['id']);
    } elseif ($_POST['do'] == 'move' && !empty($_POST['board'])) {
        // Move thread
        $destBoard = $db->q("SELECT id, name, url FROM board WHERE id = " . $db->escape((int)$_POST['board']) . " LIMIT 1");
        if ($destBoard->num_rows == 1) {
            $destBoard = $destBoard->fetch_assoc();
            $bump = '';
            if (!empty($_POST['update-timestamp'])) {
                $bump = ', `bump_time` = NOW()';
            }
            $do = $db->q("UPDATE thread SET board_id = " . (int)$destBoard['id'] . $bump . " WHERE id = " . (int)$thread['id']);
        }
    }
    if ($do) {
        echo '<h2>';
        if ($_POST['do'] == 'lock') {
            $engine->writeModlog(9, '', $posts->getOpPostId($thread['id']), $thread['id'], $board['id']);
            echo _('Thread locked');
        } elseif ($_POST['do'] == 'unlock') {
            $engine->writeModlog(10, '', $posts->getOpPostId($thread['id']), $thread['id'], $board['id']);
            echo _('Thread unlocked');
        } elseif ($_POST['do'] == 'stick') {
            $engine->writeModlog(7, '', $posts->getOpPostId($thread['id']), $thread['id'], $board['id']);
            echo _('Thread stickied');
        } elseif ($_POST['do'] == 'unstick') {
            $engine->writeModlog(8, '', $posts->getOpPostId($thread['id']), $thread['id'], $board['id']);
            echo _('Thread unstickied');
        } elseif ($_POST['do'] == 'move') {
            $engine->writeModlog(23, 'Moved to: ' . $destBoard['name'], $posts->getOpPostId($thread['id']), $thread['id'], $board['id']);
            echo _('Thread moved');
        }

        echo '</h2><a class="pure-button pure-button-primary" href="' . $engine->cfg->siteUrl . '/' . $board['url'] . '/' . $thread['id'] . '">' . _('Go to thread') . '</a>';
        echo ' <a class="pure-button" href="' . $engine->cfg->siteUrl . '/' . $board['url'] . '/">' . _('Go to board') . '</a>';

        $thread = $posts->getThread($_POST['id']);
    } else {
        echo '<h2>' . _('Action failed') . '</h2>';
    }
}

echo '
<form class="pure-form pure-form-stacked" action="" method="post">
<fieldset>
    <legend>' . _('Thread ID') . '</legend>
    <input class="pure-input-1" placeholder="' . _('Thread ID') . '" type="number" name="id" value="' . (!empty($_GET['id']) ? $_GET['id'] : '') . '" required />
</fieldset>
<fieldset>
    <legend>' . _('Lock or stick thread') . '</legend>';

if (!$thread['is_locked'] || !$thread) {
    echo '<button class="pure-button pure-button-primary" type="submit" name="do" value="lock">' . _('Lock') . '</button> ';
}
if ($thread['is_locked'] || !$thread) {
    echo '<button class="pure-button" type="submit" name="do" value="unlock">' . _('Unlock') . '</button> ';
}
if (!$thread['is_sticky'] || !$thread) {
    echo '<button class="pure-button pure-button-primary" type="submit" name="do" value="stick">' . _('Stick') . '</button> ';
}
if ($thread['is_sticky'] || !$thread) {
    echo '<button class="pure-button" type="submit" name="do" value="unstick">' . _('Unstick') . '</button> ';
}

echo '
</fieldset>
<fieldset>
    <legend>' . _('Move to another board') . '</legend>
    ', ModFunctions::boardSelect(), '
    <label for="update-timestamp">
        <input type="checkbox" name="update-timestamp" id="update-timestamp" />
        ' . _('Bump thread') . '
    </label>
    <button class="pure-button pure-button-primary" type="submit" name="do" value="move">' . _('Move') . '</button>
</fieldset>
</form>';

