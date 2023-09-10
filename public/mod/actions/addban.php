<?php
if (!defined('ALLOWLOAD')) {
    die();
}

echo '<h1>' . _('Ban user') . '</h1>';

$msgid = (int)($_POST['msgid'] ?? ($_GET['msgid'] ?? 0));
if (!empty($msgid)) {
    $postInfo = $db->q("SELECT post.`user_id`, inet6_ntoa(ip) AS ip, `url`, board.`name` AS boardname, op_post, t.id AS thread_id
    FROM `post`
    LEFT JOIN thread t ON post.thread_id = t.id
    LEFT JOIN `board` ON t.board_id = board.`id`
    WHERE post.`id` = '" . $db->escape($msgid) . "' LIMIT 1");
    $postInfo = $postInfo->fetch_assoc();
    if (empty($postInfo)) {
        echo '<h2>' . sprintf(_('Post %d does not exist'), $msgid) .'</h2>';
        die();
    }
}

// If the user has given us the input
if (!empty($_POST)) {

    // Checkboxes
    if (!empty($_POST['deletepost']) AND $_POST['deletepost'] == "on") {
        $deletepost = true;
    } else {
        $deletepost = false;
    }
    if (!empty($_POST['deleteposts']) AND $_POST['deleteposts'] == "on") {
        $deleteposts = true;
    } else {
        $deleteposts = false;
    }

    if (!empty($_POST['deletethread']) AND $_POST['deletethread'] == "on") {
        $deletethread = true;
    } else {
        $deletethread = false;
    }
    if (!empty($_POST['deletethreads']) AND $_POST['deletethreads'] == "on") {
        $deletethreads = true;
    } else {
        $deletethreads = false;
    }

    // Check required fields
    if (empty($msgid)) {
        die('<h2>Viestin numero puuttuu tai sitä ei ole olemassa.</h2>');
    }
    if (empty($_POST['reason'])) {
        die('<h2>Bannin syy puuttuu.</h2>');
    }

    switch ($_POST['reason']) {
        case 'Laiton tai vaarallinen sisältö':
            $reason = 1;
            break;
        case 'Laiton tai vaarallinen sisältö (raaka väkivalta)':
            $reason = 9;
            break;
        case 'Laiton tai vaarallinen sisältö (lasten seksualisointi tai lapsiporno)':
            $reason = 10;
            break;
        case 'Laiton tai vaarallinen sisältö (huumeiden osto/myynti)':
            $reason = 11;
            break;
        case 'Roskapostitus':
            $reason = 2;
            break;
        case 'Roskapostitus (toistuva tai duplikaatti sisältö)':
            $reason = 12;
            break;
        case 'Roskapostitus (sisällötön langan nostaminen)':
            $reason = 13;
            break;
        case 'Roskapostitus (anime)':
            $reason = 14;
            break;
        case 'Roskapostitus (ilmiantamisesta ilmoittaminen muille)':
            $reason = 15;
            break;
        case 'Haitallinen sisältö':
            $reason = 3;
            break;
        case 'Haitallinen sisältö (linkinlyhentimet)':
            $reason = 16;
            break;
        case 'Mainostaminen':
            $reason = 4;
            break;
        case 'Häiriköinti':
            $reason = 5;
            break;
        case 'Seksuaalinen sisältö':
            $reason = 6;
            break;
        case 'Sopimaton sisältö':
            $reason = 7;
            break;
        default:
            $reason = 0;
            break;
    }

    $ip = $db->escape($postInfo['ip']);
    $uid = $db->escape($postInfo['user_id']);
    $length = (int)$_POST['banlength'];
    $banIp = isset($_POST['banip']);
    $banUser = isset($_POST['banuser']);

    if (!$banIp && !$banUser) {
        die('<h2>Either IP or User or both needs to be banned.</h2>');
    }

    if (!isset($_POST['banlength']) OR !is_numeric($_POST['banlength'])) {
        die('<h2>Bannin pituus puuttuu tai ei ole numeerinen</h2>');
    }
    if ($_POST['banlength'] < 1) {
        die('<h2>Bannin pituus on liian lyhyt</h2>');
    }

    $addban = $user->addBan(($banIp ? $ip : null), ($banUser ? $uid : null), $length, $reason, $msgid, false, $_POST['reasonadd'] ?? '');
    if (!$addban) {
        die('<h2>Bannin lisääminen epäonnistui</h2>');
    } else {
        echo '<h2>Banni lisätty!</h2>';
    }

    if (!$deletepost) {
        $db->q("DELETE FROM `post_report` WHERE `post_id` = " . (int)$msgid);
    }

    if ($deleteposts) {
        $q = $db->q("SELECT `id` FROM `post` WHERE `user_id` = " . (int)$uid . " AND `time` >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
        $ids = $db->fetchAll($q, 'id');
        $count = count($ids);
        if ($count != 0) {
            if (!$posts->deletePosts(implode(',', $ids))) {
                echo '<h2>Viestien poistaminen käyttäjältä epäonnistui</h2>';
            } else {
                echo '<h2>' . $count . ' viestiä käyttäjältä poistettu</h2>';
            }
        } else {
            echo '<h2>Ei viestejä käyttäjältä vuorokauden sisään</h2>';
        }
    }

    if ($deletethreads) {
        $q = $db->q("SELECT `id` FROM thread WHERE `user_id` = " . (int)$uid . " AND `time` >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
        $ids = $db->fetchAll($q, 'id');
        $count = count($ids);
        if ($count != 0) {
            if (!$posts->deleteThreads(implode(',', $ids), 4)) {
                echo '<h2>Lankojen poistaminen käyttäjältä epäonnistui</h2>';
            } else {
                echo '<h2>' . $count . ' lankaa käyttäjältä poistettu</h2>';
            }
        } else {
            echo '<h2>Ei lankoja käyttäjältä vuorokauden sisään</h2>';
        }
    }

    if ($deletepost) {
        if (!$posts->deletePosts($msgid)) {
            echo '<h2>Viestin poistaminen epäonnistui</h2>';
        }
    }

    if ($deletethread && $posts->getThread((int)$postInfo['thread_id'])) {
        if (!$posts->deleteThreads((int)$postInfo['thread_id'], 3)) {
            echo '<h2>Langan poistaminen epäonnistui</h2>';
        }
    }
}

echo '
<form action="' . $_SERVER['REQUEST_URI'] . '" method="post" class="pure-form pure-form-stacked">
<fieldset>
    <input class="pure-input-1" type="text" placeholder="' . _('Post ID') . '" name="msgid" value="' . (!empty($_GET['msgid']) ? $_GET['msgid'] : '') . '" />
    <legend>' . _('Message') . '</legend>
    <label for="deletepost" class="pure-checkbox">
        <input id="deletepost" name="deletepost" type="checkbox">
        ' . _('Delete message') . '
    </label>
    <label for="deleteposts">
        <input id="deleteposts" name="deleteposts" type="checkbox">
        ' . _('Delete all posts by the user in the last 24h') . '
    </label>';
if ($postInfo['op_post']) {
    echo '
    <label for="deletethread" class="pure-checkbox">
        <input id="deletethread" name="deletethread" type="checkbox">
        ' . _('Delete thread') . '
    </label>';
}
echo '
    <label for="deletethreads">
        <input id="deletethreads" name="deletethreads" type="checkbox">
        ' . _('Delete all threads by the user in the last 24h') . '
    </label>
    <label class="pure-checkbox">
        <input name="banuser" type="checkbox" checked>
        ' . _('Ban user account') . '
    </label>
    <label class="pure-checkbox">
        <input name="banip" type="checkbox" checked>
        ' . _('Ban IP-address') . '
    </label>
</fieldset>

<fieldset>
    <legend>' . _('Reason and length') . '</legend>
    <select class="pure-input-1" name="reason" required data-e="updateBanForm">
        <option disabled default selected>' . _('Choose reason') . '</option>';

foreach ($engine->cfg->ruleOptionsB AS $ruleKey => $ruleOption) {
    if (!is_array($ruleOption)) {
        echo '<option data-delete="false" data-delete24h="false" data-banlength="">' . htmlspecialchars($ruleOption) . '</option>';
    } else {
        if (!empty($ruleOption['boards']) && is_array($ruleOption['boards'])) {
            if (!empty($postInfo)) {
                if (in_array('!' . $postInfo['url'], $ruleOption['boards']) || (!in_array('*',
                            $ruleOption['boards']) && (!in_array($postInfo['url'],
                                $ruleOption['boards'])))
                ) {
                    continue;
                }
            }
        }
        echo '<optgroup label="' . htmlspecialchars($ruleKey) . '">';
        foreach ($ruleOption AS $optionKey => $optionValue) {
            if ($optionKey === 'boards') {
                continue;
            }
            echo '<option';
            if (is_array($optionValue)) {
                echo ' data-delete="';
                if (isset($optionValue['deletePost']) && $optionValue['deletePost']) {
                    echo 'true';
                } else {
                    echo 'false';
                }
                echo '" data-delete24h="';
                if (isset($optionValue['deletePosts24h']) && $optionValue['deletePosts24h']) {
                    echo 'true';
                } else {
                    echo 'false';
                }
                echo '" data-banlength="';
                if (!empty($optionValue['banLength'])) {
                    echo $optionValue['banLength'];
                }
                echo '"';
                $optionValue = $optionKey;
            }
            echo '>' . htmlspecialchars($optionValue) . '</option>';
        }
        echo '</optgroup>';
    }
}

echo '
    </select>
    <input class="pure-input-1" type="text" placeholder="' . _('Additional info (optional)') . '" name="reasonadd" value="' . (!empty($_POST['reasonadd']) ? $_POST['reasonadd'] : '') . '" />
    <input class="pure-input-1" type="text" required id="banlength" placeholder="' . _('Ban length in seconds') . '" name="banlength" value="' . (!empty($_POST['banlength']) ? $_POST['banlength'] : '') . '" />
    ' . ModFunctions::timeQuicklinks('banlength') . '
</fieldset>';

echo '
    <input type="submit" class="pure-button pure-button-primary" value="Lisää banni" />
</form>';

if (!empty($postInfo) AND $user->hasPermissions('viewpreviousbans')) {

    $uid = $db->escape($postInfo['user_id']);
    $q = $db->q("SELECT * FROM `user_ban` WHERE `user_id` = '" . $uid . "' ORDER BY start_time DESC");

    echo '
  <h2>Käyttäjän aiemmat bannit</h2>
  <table class="pure-table pure-table-striped">
    <thead>
        <tr>
          <th>Viesti-ID</th>
          <th>Alkoi</th>
          <th>Päättyy</th>
          <th>Syy</th>
          <th>Vanhentunut</th>
          <th>Bannaaja</th>
          <th>Toiminnot</th>
        </tr>
     </thead>
     <tbody>';

    while ($ban = $q->fetch_assoc()) {
        echo '
    <tr>
      <td>' . $ban['post_id'] . '</td>
      <td>' . date('d.m.Y H:i:s', strtotime($ban['start_time'])) . '</td>
      <td>' . date('d.m.Y H:i:s', strtotime($ban['end_time'])) . '</td>
      <td>' . ($user->banReasons[$ban['reason']] ?? _('Unknown'))
            . (!empty($ban['reason_details']) ? ' (' . htmlspecialchars($ban['reason_details']) . ')' : '')
            . '</td>
      <td>' . ($ban['is_expired'] ? 'Kyllä' : 'Ei') . '</td>
      <td>' . htmlspecialchars($user->modNameById($ban['banned_by'])) . '</td>
      <td><a class="pure-button" href="?action=managebans&delete=' . $ban['id'] . '&csrf_token=' . $user->csrf_token . '">Poista</a></td>
    </tr>';
    }
    echo '
    </tbody>
  </table>';
}

