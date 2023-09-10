<?php

// Initialize the board engine
$loadClasses = [
    'cache' => '',
    'db' => '',
    'html' => true,
    'posts' => '',
    'user' => false,
];
include("../../inc/engine.class.php");
new Engine($loadClasses);

if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) || !hash_equals($user->csrf_token, $_SERVER['HTTP_X_CSRF_TOKEN'])) {
    http_response_code(401);
    die(_('Your session has expired. Please refresh the page and try again.'));
}

$notifications = $user->getNotifications();

if (count($notifications) == 0) {
    die('<p>' . _('You don\'t have any notifications.') . '</p>');
}

echo '<div class="notification-list">';
foreach ($notifications as $notification) {
    if ($notification['type'] == 1) {
        // New reply in a post
        if ($notification['count'] == 1) {
            $text = sprintf(_('Your post has a new reply'));
        } else {
            $text = sprintf(_('Your post has %s new replies'), $notification['count']);
        }
    } elseif ($notification['type'] == 2) {
        // New reply in a thread
        $reflink = '<em>' . htmlspecialchars($notification['custom_info']) . '</em>';
        if ($notification['count'] == 1) {
            $text = sprintf(_('Your thread %s has a new reply'), $reflink);
        } else {
            $text = sprintf(_('Your thread %s has %s new replies'), $reflink, $notification['count']);
        }
    } elseif ($notification['type'] == 3) {
        // New reply in a followed thread
        $reflink = '<em>' . htmlspecialchars($notification['custom_info']) . '</em>';
        if ($notification['count'] == 1) {
            $text = sprintf(_('Thread %s has a new reply'), $reflink);
        } else {
            $text = sprintf(_('Thread %s has %s new replies'), $reflink, $notification['count']);
        }
    } elseif ($notification['type'] == 4) {
        // Post upvoted
        if ($notification['count'] == 1) {
            $text = sprintf(_('Your post has been upvoted!'));
        } else {
            $text = sprintf(_('Your post has been upvoted %s times!'), $notification['count']);
        }
    } elseif ($notification['type'] == 5) {
        // Gold account get
        if (empty($notification['post_id'])) {
            $text = _('Someone just gave you a Gold account!');
        } else {
            if ($notification['count'] == 1) {
                $text = _('Someone just gave you a Gold account for your post!');
            } else {
                $text = sprintf(_('You received %d Gold accounts for your post!'), $notification['count']);
            }
        }
    } elseif ($notification['type'] == 6) {
        // Announcements
        if (empty($notification['custom_info'])) {
            $notification['custom_info'] = _('Nothing to announce. Stupid admins forgot the message.');
        }
        $text = sprintf(_('Announcement: %s'), htmlspecialchars($notification['custom_info']));
    } elseif ($notification['type'] == 7) {
        // Tag unlocks
        if (!empty($notification['custom_info'])) {
            if (!array_key_exists($notification['custom_info'], $engine->cfg->postTags)) {
                $notification['custom_info'] = _('This was a tag that does not exist anymore...');
            } else {
                $tag = $engine->cfg->postTags[$notification['custom_info']];
                $notification['custom_info'] = str_replace('[NAME]', _($tag['name']), $tag['display']);
            }
        } else {
            $notification['custom_info'] = _('Weird, we don\'t even know which tag it was!');
        }
        $text = sprintf(_('You got a new tag: %s'), $notification['custom_info']);
    }

    echo '
    <div class="notification' . (!$notification['is_read'] ? ' not-read' : '') . '" data-id="' . $notification['id'] . '">
        <div class="notification-info">';
            if (!empty($notification['post_id'])) {
                echo '<a href="' . $engine->cfg->siteUrl . '/scripts/redirect.php?id=' . $notification['post_id'] . '">' . $text . '</a>';
            } elseif (!empty($notification['user_post_id'])) {
                echo '<a href="' . $engine->cfg->siteUrl . '/scripts/redirect.php?id=' . $notification['user_post_id'] . '">' . $text . '</a>';
            } else {
                echo $text;
            }
            echo '<div class="notification-meta">';
            echo '<time datetime="' . date(DateTime::ATOM, $notification['timestamp']) . '">'
                . $engine->formatTime($user->language, $user, $notification['timestamp']) .'</time>';
            if (!empty($notification['user_post_id'])) {
                echo ' <a href="/scripts/redirect.php?id=' . $notification['user_post_id'] . '" class="ref" data-id="' . $notification['user_post_id'] . '">&gt;&gt;' . $notification['user_post_id'] . '</a>';
            } elseif (!empty($notification['post_id'])) {
                echo ' <a href="/scripts/redirect.php?id=' . $notification['post_id'] . '" class="ref" data-id="' . $notification['post_id'] . '">&gt;&gt;' . $notification['post_id'] . '</a>';
            }
            echo '</div>';
        echo '</div>';

        if (!$notification['is_read']) {
            echo '<div class="notification-buttons">
                <button class="icon-checkmark-circle" data-e="notificationsMarkRead"></button>
            </div>';
        }
    echo '</div>';
}
echo '</div>
<div class="buttons">
    <button class="linkbutton" data-e="modalClose notificationsMarkAllRead">' . _('Mark all as read') . '</button>
</div>';
