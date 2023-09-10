<?php

// Initialize the board engine
$loadClasses = [
    'cache' => '',
    'db' => '',
    'html' => '',
    'posts' => '',
    'user' => 'banned',
    'fileupload' => '',
    'board' => [false, false, false],
];
include("inc/engine.class.php");
new Engine($loadClasses);

if (!isset($user)) {
    $engine->redirectExit($engine->cfg->siteUrl);
}
if (!$user->isBanned) {
    $engine->redirectExit($engine->cfg->siteUrl);
}

if (strtotime($user->banInfo['end_time']) <= time()) {
    $db->q("UPDATE `user_ban` SET `is_expired` = 1, `appeal_checked` = 1 WHERE `id` = '" . $user->banInfo['id'] . "' LIMIT 1");
    $user->banInfo['is_expired'] = 1;
}

if (!empty($_POST['appealtext']) AND !$user->banInfo['is_appealed']) {
    $appealtext = htmlspecialchars($_POST['appealtext']);
    if (mb_strlen($appealtext) >= $engine->cfg->appealTextMaxLength) {
        $appealtext = mb_substr($appealtext, 0, $engine->cfg->appealTextMaxLength);
    }
    $appealtext = $db->escape($appealtext);

    $db->q("UPDATE `user_ban` SET `is_appealed` = 1, `appeal_text` = '" . $appealtext . "' WHERE `id` = '" . $user->banInfo['id'] . "' LIMIT 1");
    $user->banInfo['is_appealed'] = 1;
    $user->banInfo['appeal_text'] = stripslashes($appealtext);
}

if (!empty($user->banInfo['post_id'])) {
    $post = $posts->getPost($user->banInfo['post_id'], true);
    if (!$post) {
        $post = $db->q("
            SELECT pd.*, UNIX_TIMESTAMP(pd.`time`) AS `time`, COALESCE(t.board_id, td.board_id) AS board_id
            FROM `post_deleted` pd
            LEFT JOIN thread t ON t.id = pd.thread_id
            LEFT JOIN thread_deleted td ON td.id = pd.thread_id
            WHERE pd.`id` = " . (int)$user->banInfo['post_id'] . " LIMIT 1");
        $post = $post->fetch_assoc();

        if ($post) {
            $post['board'] = $post['board_id'];
        }
    }

    if ($post) {
        $board->getBoardInfo($post['board'], false, false);
    }
}

$html->printHeader(_('You are banned.') . ' | ' . $engine->cfg->siteName);
$html->printSidebar();

$images = glob($engine->cfg->staticDir . '/img/banimages/*');
$image = $images[array_rand($images)];
$image = str_replace($engine->cfg->staticDir, '', $image);
$displayImage = $engine->cfg->staticUrl . $image;

echo '
<div id="right" class="ban-info">
    <h1 class="infobar">' . ($user->banInfo['is_expired'] == 0 ? _('You are banned.') : _('You were banned.')) . '</h1>
    <div class="grid">
    <div>
    <div class="banned-block">
        <h2>' . _('Reason:') . ' ' . ($user->banReasons[$user->banInfo['reason']] ?? _('Unknown'));
    if(!empty($user->banInfo['reason_details'])) {
        echo ' (' . htmlspecialchars($user->banInfo['reason_details']) . ')';
    }
    echo '</h2>';

if (isset($post)) {
    if (!empty($post['message'])) {
        echo '<p>' . _('Your post which resulted in this ban is the following');
        if (!empty($board->info['boardname'])) {
            echo ' (' . sprintf(_('Board: %s'), htmlspecialchars($board->info['boardname'] ?? _('Unknown'))) . ')';
        }
        echo ':</p>';
        echo '
        <div class="thread">
            <div class="post answer">
                <div class="postinfo">
                    <div class="left">
                        <span class="postnumber">' . _('No.') . ' ' . $post['id'] . '</span>
                        <time class="posttime" datetime="' . date(DateTime::ATOM, $post['time']) . '">'
                            . $engine->formatTime($user->language, $user, $post['time']) .'</time>
                    </div>
                </div>
                <div class="message">
                    <blockquote class="postcontent">', $posts->printMessage($post['message'], $post['id'], $post['id']) . '</blockquote>
                </div>
            </div>
        </div>';
    } else {
        echo '<p>' . _('Your post only contained a file, so it is not shown here.')
            . ' (' . sprintf(_('Board: %s'), htmlspecialchars($board->info['boardname'])) . ')</p>';
    }
}

$endTime = strtotime($user->banInfo['end_time']);
echo '
        <p><a href="' . $engine->cfg->siteUrl . '/?saannot">' . _('Please read the rules of Ylilauta.') . '</a></p>
        <p>' . ($user->banInfo['is_expired'] ?
            _('Your ban is now over.') :
            sprintf(
                _('Your ban ends %s.'),
                '<time datetime="' . date(DateTime::ATOM, $endTime) . '">'
                . $engine->formatTime($user->language, $user, $endTime) .'</time>'
            )) . '</p>';
echo '
    </div>
    <div class="banned-block">';

if (!$user->banInfo['is_expired'] && $user->banInfo['banned_by'] != 0) {
    echo '<h3>' . _('Ban appeal') . '</h3>';
    if (!$user->banInfo['is_appealed']) {
        echo '
        <p>' . _('We understand that people make mistakes. Us included. Please send us a message and we\'ll reconsider your ban.') . '</p>
        <form action="' . $engine->cfg->siteUrl . '/banned" method="post" class="banappeal">
        <textarea maxlength="' . $engine->cfg->appealTextMaxLength . '" name="appealtext" id="appealtext" placeholder="'
            . sprintf(_('Your message (max %s characters)'), $engine->cfg->appealTextMaxLength) . '"></textarea>
        <p>' . _('Note: We can not reply to your message. We can only approve or reject your appeal to lift this ban.') . '</p>
        <input type="submit" class="linkbutton" value="' . _('Submit') . '" />
        </form>';
    } elseif ($user->banInfo['is_appealed'] AND !$user->banInfo['appeal_checked']) {
        echo '
        <p>' . _('We have not yet reviewed the message you sent. Please check back later.') . '</p>
        <p>' . _('Your message is:') . '</p>
        <div class="box">' . $user->banInfo['appeal_text'] . '</div>';
    } elseif ($user->banInfo['is_appealed'] AND $user->banInfo['appeal_checked'] AND !$user->banInfo['is_expired']) {
        echo '
        <p>' . _('After considering the message you sent, we are sorry to say that your ban was not lifted.') . '</p>
        <p>' . _('Your message was:') . '</p>
        <div class="box">' . $user->banInfo['appeal_text'] . '</div>';
    }
}

echo '</div>
    </div>
    <img class="banimage" src="' . $displayImage . '" alt="' . _('Banned!') . '" />
    </div>
</div>';


