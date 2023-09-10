<?php
if (!defined('ALLOWLOAD')) {
    die();
}

echo '
<h1>' . _('Delete message') . '</h1>

<form action="" method="post" class="pure-form pure-form-stacked">
    <fieldset>
        <legend>' . _('Message') . '</legend>
        <input class="pure-input-1" type="number" min="0" placeholder="' . _('Message ID') . '" name="id" value="' . (!empty($_POST['id']) ? $_POST['id'] : (!empty($_GET['id']) ? $_GET['id'] : '')) . '" />

        <label for="onlyfile" class="pure-checkbox">
            <input type="checkbox" id="onlyfile" name="onlyfile" />
            ' . _('Delete file only') . '
        </label>
    </fieldset>
    <input class="pure-button pure-button-primary" type="submit" value="' . _('Delete message') . '" />

    <fieldset>
        <legend>' . _('Delete messages by the user') . '</legend>
        <label for="deleteall" class="pure-checkbox">
            <input type="checkbox" id="deleteall" name="deleteall" />
            ' . _('Delete messages by the user') . '
        </label>
        <input class="pure-input-1" type="number" placeholder="' . _('Interval in seconds (0 = all)') . '" id="deletesince" name="deletesince" min="0" />
        ' . ModFunctions::timeQuicklinks('deletesince') . '
    </fieldset>
    <input class="pure-button pure-button-primary" type="submit" value="' . _('Delete multiple messages') . '" />
</form>';

if (!empty($_POST['id'])) {
    $msgid = $_POST['id'];
    $post = $posts->getPost($msgid, true);
    if (!$post) {
        ModFunctions::printError(_('Post does not exist'));
    }

    if (isset($_POST['onlyfile']) AND $_POST['onlyfile'] == 'on') {
        $onlyFile = true;
    } else {
        $onlyFile = false;
    }

    if (isset($_POST['deleteall']) AND $_POST['deleteall'] == 'on' && !empty($_POST['deletesince'])) {
        $timespan = '';

        if (!empty($_POST['deletesince']) AND is_numeric($_POST['deletesince'])) {
            $timespan = " AND `time` > DATE_SUB(NOW(), INTERVAL " . (int)$_POST['deletesince'] . " SECOND)";
        }

        $q = $db->q("SELECT `id` FROM `post` WHERE (`ip` = '" . $db->escape($post['ip']) . "' OR `user_id` = '" . $post['user_id'] . "')" . $timespan);
        $deleteCount = $q->num_rows;
        $deleteIds = $db->fetchAll($q, 'id');
        $deleteIds = implode(',', $deleteIds);
        $msgid = $deleteIds;
    } else {
        $deleteCount = 1;
    }

    if ($onlyFile) {
        $engine->writeModlog(14, 'Deleted count: ' . $deleteCount, $post['id']);
        if ($posts->deleteFileFromPosts($msgid)) {
            if ($deleteCount == 1) {
                ModFunctions::printInfo(_('File deleted from message'));
            } else {
                ModFunctions::printInfo(sprintf(_('File deleted from %d messages'), $deleteCount));
            }
        } else {
            ModFunctions::printError(_('Deleting the file failed'));
        }
    } else {
        $engine->writeModlog(1, 'Deleted count: ' . $deleteCount, $post['id']);
        $reason = 3;
        if ($deleteCount > 1) {
            $reason = 4;
        }
        if ($posts->deletePosts($msgid)) {
            if ($deleteCount == 1) {
                ModFunctions::printInfo(_('Message deleted'));
            } else {
                ModFunctions::printInfo(sprintf(_('%d messages deleted'), $deleteCount));
            }
        } else {
            ModFunctions::printError(_('Deleting the message failed'));
        }
    }
}
