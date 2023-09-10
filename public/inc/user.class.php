<?php

use IP2Location\Database;

class user
{
    public $id = false;
    public $session = false;
    public $session_id = false;
    public $info = false;
    public $csrf_token = false;
    public $account_created = false;
    public $last_active = false;
    public $activity_points = 0;
    public $language;
    public $is_anonymous = true;
    public $isAdmin = false;
    public $isSuperMod = false;
    public $isMod = false;
    public $isBanned = false;
    public $loggedIn = false;
    public $hasGoldAccount = false;
    public $hasPlatinumAccount = false;
    public $followed_threads;
    public $hidden_threads;
    public $unread_notifications = false;
    protected $statistics_keys;
    protected $statistics = false;
    protected $preferences = false;
    protected array $tags;
    protected $notifications = false;
    protected $delayed_stats_to_update = [];
    protected $mod_names = [];
    protected $use_captcha;
    protected $load_minimally = false;
    public $styleOverride = false;

    private string $timezone;
    public array $banReasons;
    private string $displayStyle;

    public function __construct($board = false)
    {
        global $engine, $db;

        if (is_array($board)) {
            $this->load_minimally = $board[1];
            $board = $board[0];
        }

        $this->currentBoard = $board;
        $this->isLimited = false;

        // Verify session and load user
        if (!empty($_COOKIE['user'])) {
            if (strlen($_COOKIE['user']) < 65) {
                $session = str_split($_COOKIE['user'], 32);
            } else {
                $session = str_split($_COOKIE['user'], 64);
            }

            if (count($session) != 2) {
                $this->updateCookie('user', '', true);
                $engine->redirectExit($_SERVER['REQUEST_URI']);
            }

            $user_id = $session[1];
            $this->session_id = $session[0];

            $this->info = $this->loadProfile($user_id);
            if ($this->info === false) {
                $this->updateCookie('user', '', true);
                $engine->redirectExit($_SERVER['REQUEST_URI']);
            }

            if (!empty($this->info->password)) {
                $this->is_anonymous = false;
            }

            $this->session = $this->loadSession($user_id, $this->session_id);
            if (!$this->session) {
                $this->updateCookie('user', '', true);
                $engine->redirectExit($_SERVER['REQUEST_URI']);
            }
        }

        if ($this->session_id === false) {
            // Session does not exist

            if (!$this->might_be_a_bot()) {
                $this->id = $this->createProfile();
                if ($this->id) {
                    $this->session_id = $this->createSession($this->id);
                    $this->session = $this->loadSession($this->id, $this->session_id);
                    $this->info = $this->loadProfile($this->id);
                    $this->updateCookie('user', $this->session_id . $this->id);
                } else {
                    $this->isLimited = true;
                }
            } else {
                $this->isLimited = true;
            }
        }

        if (!$this->isLimited) {
            $this->id = $this->info->id;
            $this->csrf_token = $this->session->csrf_token;
            $this->account_created = $this->info->account_created;
            $this->last_active = $this->info->last_active;
            $this->language = $this->info->language;

            // Admin or mod?
            if ($this->info->user_class == 1) {
                $this->isAdmin = true;
                $this->isSuperMod = true;
                $this->isMod = true;
            } elseif ($this->info->user_class == 2) {
                $this->isSuperMod = true;
                $this->isMod = true;
            } elseif ($this->info->user_class == 3) {
                $this->isMod = true;
            }

            if (!$this->load_minimally && $this->isMod) {
                // Count unread reports
                $q = $db->q("SELECT COUNT(*) AS `count` FROM `post_report` WHERE `cleared` = 0 LIMIT 1");
                $count = $q->fetch_assoc();
                $this->unread_reports = $count['count'];

                // Count unread ban appeals
                $q = $db->q("SELECT COUNT(*) AS `count` FROM `user_ban` WHERE `is_appealed` = 1 AND `appeal_checked` = 0 AND `is_expired` = 0 LIMIT 1");
                $count = $q->fetch_assoc();
                $this->unread_ban_appeals = $count['count'];
            }

            // Gold accounts
            if ($this->isMod) {
                $this->info->gold_account_expires = '9001-01-01 00:00:00';
            }
            if ($this->info->gold_account_expires === '9001-01-01 00:00:00') {
                $this->hasGoldAccount = true;
                $this->hasPlatinumAccount = true;
            } elseif (!empty($this->info->gold_account_expires)) {
                // Expired?
                if (strtotime($this->info->gold_account_expires) < time()) {
                    $this->removeGoldAccount();
                } else {
                    $this->hasGoldAccount = true;
                }
            }

            // For gold, more functions!
            if ($this->hasGoldAccount) {
                $engine->cfg->threadsPerPage = $this->getPreferences('threads_per_page');
                $engine->cfg->replyCount = $this->getPreferences('preview_posts_per_thread');
            }

            if (!$this->load_minimally) {
                $this->checkBans();
                $this->activity_points = $this->info->activity_points;
                $this->info->followedThreads = $this->getFollowedThreads();
                $this->unread_notifications = $this->getUnreadNotificationCount();
            }

            $this->validateLogin();
            $this->loadStats();
        } else {
            $this->info = new StdClass();
            if (empty($this->id)) {
                $this->id = false;
            }
            $this->language = $this->getPreferredLanguage();
            $this->info->username = '';
            $this->info->gold_account_expires = null;
            $this->info->followedThreads = [];
            $this->info->hiddenThreads = [];
        }

        // Set timezone and locale
        date_default_timezone_set('UTC');
        $engine->loadLocale($this->language, 'default');

        $this->banReasons = [
            0 => _('Other reason'),
            1 => _('Illegal or dangerous content'),
            9 => _('Illegal or dangerous content (extreme violence)'),
            10 => _('Illegal or dangerous content (sexual material of children)'),
            11 => _('Illegal or dangerous content (selling/buying drugs)'),
            2 => _('Spam'),
            12 => _('Spam (repetitive or duplicate content)'),
            13 => _('Spam (post solely intended to bump a thread)'),
            14 => _('Spam (anime)'),
            15 => _('Spam (telling others that you reported a post)'),
            3 => _('Malicious content'),
            16 => _('Malicious content (link shorteners)'),
            4 => _('Advertising'),
            5 => _('Harassment'),
            6 => _('Sexually explicit content'),
            7 => _('Inappropriate content'),
            8 => _('Your message contained a blacklisted word'),
        ];

        $this->checkNewTags();
    }

    private function checkNewTags(): void
    {
        if (!$this->hasTag('ylilauta_pro') && $this->account_created < (time() - 31536000)) {
            $this->unlockTag('ylilauta_pro');
        }

        if (!$this->hasTag('always_been_here') && $this->account_created < (time() - 31536000 * 3)) {
            $this->unlockTag('always_been_here');
        }

        if (!$this->hasTag('wizard') && $this->account_created < (time() - 31536000 * 6)) {
            $this->unlockTag('wizard');
        }

        if (!$this->hasTag('hyperactive') && $this->activity_points >= 100_000) {
            $this->unlockTag('hyperactive');
        }

        if (!$this->hasTag('top_commenter') && $this->getStats('total_upboats_received') >= 1000) {
            $this->unlockTag('top_commenter');
        }

        if (!$this->hasTag('upboat_pro') && $this->getStats('total_upboats_given') >= 1000) {
            $this->unlockTag('upboat_pro');
        }

        if (!$this->hasTag('bier') && $this->getStats('purchases_total_price') >= 1000) {
            $this->unlockTag('bier');
        }

        if (!$this->hasTag('epic_guy') && !empty($this->getStats('epic_threads'))) {
            $this->unlockTag('epic_guy');
        }

        if (!$this->hasTag('gold_commenter') && $this->getStats('gold_account_donations_received') >= 1) {
            $this->unlockTag('gold_commenter');
        }

        if (!$this->hasTag('goldboated') && $this->getStats('gold_accounts_donated') >= 1) {
            $this->unlockTag('goldboated');
        }

        // Gold tag is never permanent
        if ($this->hasGoldAccount) {
            $this->tags[] = 'gold';
        }
    }

    public function tags(): array
    {
        global $db;

        if (!isset($this->tags)) {
            $q = $db->q('SELECT tag_id FROM user_tag WHERE user_id = ' . (int)$this->id);

            $tags = $q->fetch_all(MYSQLI_NUM);
            $this->tags = array_map('current', $tags);
        }

        return $this->tags;
    }

    public function hasTag(string $tagId): bool
    {
        return in_array($tagId, $this->tags());
    }

    public function unlockTag(string $tagId, int $userId = null): bool
    {
        global $db;

        if ($userId === null) {
            $userId = $this->id;

            if ($this->hasTag($tagId)) {
                return true;
            }
        } else {
            $q = $db->q('SELECT tag_id FROM user_tag WHERE user_id = ' . (int)$this->id . ' AND tag_id = "' . $db->escape($tagId) . '" LIMIT 1');
            if ($q->num_rows == 1) {
                return true;
            }
        }

        $q = $db->q('INSERT IGNORE INTO user_tag (user_id, tag_id) VALUES (' . (int)$userId . ', "' . $db->escape($tagId) . '")');

        $this->tags[] = $tagId;
        $this->addNotification('tag_unlock', $userId, 'NULL', $tagId);

        return $q !== false;
    }

    public function updateCookie($name, $value, $remove = false)
    {
        global $engine;

        if (!$remove) {
            $cookietime = $engine->cfg->cookietime;
        } else {
            $cookietime = 1;
        }
        setcookie($name, $value, $cookietime, '/', null, !empty($_SERVER['HTTPS']), true);
    }

    private function loadProfile($id)
    {
        global $db, $cache;

        $q = $db->q("SELECT *, UNIX_TIMESTAMP(account_created) AS account_created,
            UNIX_TIMESTAMP(last_active) AS last_active, UNIX_TIMESTAMP(last_name_change) AS last_name_change
            FROM user WHERE id = " . (int)$id . " LIMIT 1");

        if ($q->num_rows != 0) {
            $profile = $q->fetch_object();

            if (!$this->load_minimally && !$cache->exists('update_delay' . $profile->id)) {
                $cache->set('update_delay' . $profile->id, true, 60);

                $keys[] = 'last_ip';
                $vals[] = inet_pton($this->getIp());
                $this->updateAccount($keys, $vals, $profile->id);
            }

            return $profile;
        } else {
            return false;
        }
    }

    public function setLanguage(string $language)
    {
        global $engine, $db;

        if (!array_key_exists($language, $engine->cfg->availableLanguages)) {
            return false;
        }

        $this->language = $language;
        $q = $db->q("UPDATE user SET language = '" . $db->escape($language) . "' WHERE id = " . (int)$this->id . " LIMIT 1");

        return $q !== false;
    }

    public function getIp()
    {
        return $_SERVER['REMOTE_ADDR'];
    }

    public function updateAccount($key, $val, $uid = 0)
    {
        global $db;

        $lastActive = '';
        if (empty($uid)) {
            $lastActive = ', last_active = NOW()';
        }
        if (empty($uid)) {
            $uid = $this->id;
        }
        if (empty($uid)) {
            return false;
        }

        if (!is_array($key) && is_array($val) || is_array($key) && !is_array($val)) {
            return false;
        }
        if (!is_array($key) && !is_array($val)) {
            $key = [$key];
            $val = [$val];
        }

        if (count($key) != count($val) || count($key) == 0) {
            return false;
        }

        $update = '';
        $i = 0;
        foreach ($key AS $curKey) {
            if ($i != 0) {
                $update .= ',';
            }

            if (is_int($val[$i])) {
                $curVal = (int)$val[$i];
            } elseif ($val[$i] === null) {
                $curVal = 'NULL';
            } else {
                $curVal = "'" . $db->escape($val[$i]) . "'";
            }

            $update .= "`" . $db->escape($curKey) . "` = " . $curVal;
            if (isset($this->info) AND is_object($this->info)) {
                $this->info->$curKey = $val[$i];
            }
            ++$i;
        }
        if (empty($update)) {
            return false;
        }

        return $db->q("UPDATE user SET " . $update . $lastActive . " WHERE `id` = '" . $uid . "' LIMIT 1") !== false;
    }

    public function loadSession($user_id, $session_id)
    {
        global $db, $cache;

        $q = $db->q("SELECT user_id, session_id, csrf_token FROM user_session WHERE user_id = " . (int)$user_id . " AND session_id = UNHEX('" . $db->escape($session_id) . "')");

        // Invalid session
        if ($q->num_rows != 1) {
            return false;
        }

        $row = $q->fetch_assoc();
        $session = new stdClass();
        $session->id = $row['session_id'];
        $session->csrf_token = bin2hex($row['csrf_token']);

        // Only update timestamps once per a minute
        if ($cache->exists('update_session_timestamp' . $user_id . $session_id)) {
            return $session;
        }

        $cache->set('update_session_timestamp' . $user_id . $session_id, true, 60);

        // Update last active -timestamp
        $db->q("UPDATE user_session SET last_active = NOW() WHERE user_id = " . (int)$row['user_id'] . " AND session_id = '" . $db->escape($row['session_id']) . "' LIMIT 1");

        return $session;
    }

    private function might_be_a_bot()
    {
        if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            // Great way of detecting crawlers!
            return true;
        }

        if (empty($_SERVER['HTTP_USER_AGENT'])) {
            return true;
        }

        if (preg_match('/Googlebot/i', $_SERVER['HTTP_USER_AGENT'])) {
            return true;
        }

        if (preg_match('/facebookexternalhit/i', $_SERVER['HTTP_USER_AGENT'])) {
            return true;
        }

        if (preg_match('/Baiduspider/i', $_SERVER['HTTP_USER_AGENT'])) {
            return true;
        }

        if (preg_match('/msnbot/i', $_SERVER['HTTP_USER_AGENT'])) {
            return true;
        }

        if (preg_match('/Yandex/i', $_SERVER['HTTP_USER_AGENT'])) {
            return true;
        }

        if (preg_match('/sukibot_heritrix/i', $_SERVER['HTTP_USER_AGENT'])) {
            return true;
        }

        return false;
    }

    private function createProfile()
    {
        global $db;

        $lastIp = $db->escape(inet_pton($this->getIp()));

        $q = $db->q("INSERT INTO user (`last_ip`, `language`)
            VALUES ('" . $lastIp . "', '" . $db->escape($this->getPreferredLanguage()) . "')");

        if ($q) {
            $this->id = $db->mysql0->insert_id;

            return $this->id;
        } else {
            return false;
        }

    }

    public function preferenceExists($key)
    {
        if ($this->preferences === false) {
            $this->loadPreferences();
        }
        return isset($this->preferences->$key);
    }

    public function updatePreferences($key, $val)
    {
        global $db;

        if (empty($this->id)) {
            return false;
        }

        if (!is_array($key) && is_array($val) || is_array($key) && !is_array($val)) {
            return false;
        }
        if (!is_array($key) && !is_array($val)) {
            $key = [$key];
            $val = [$val];
        }

        if (count($key) != count($val)) {
            return false;
        }

        if ($this->preferences === false) {
            $this->loadPreferences();
        }

        $values = [];
        $i = -1;
        foreach ($key AS $curKey) {
            ++$i;

            if (!isset($this->preferences->$curKey) && $this->preferences->$curKey !== null) {
                error_log('Invalid preferences key (set): ' . $curKey);
                continue;
            }

            $this->preferences->$curKey = $val[$i];
        }

        $q = $db->q("INSERT INTO user_preferences (
                user_id,
                custom_css,
                show_username,
                style,
                board_display_style,
                hide_sidebar,
                hide_ads,
                hide_images,
                reply_form_at_top,
                auto_follow,
                auto_follow_reply,
                follow_order_by_bumptime,
                follow_show_floatbox,
                notification_from_post_replies,
                notification_from_thread_replies,
                notification_from_followed_replies,
                notification_from_post_upvotes,
                threads_per_page,
                preview_posts_per_thread
            ) VALUES (
                " . (int)$this->id . ",
                '". $db->escape($this->preferences->custom_css) . "',
                " . (int)$this->preferences->show_username . ",
                '" . $db->escape($this->preferences->style) . "',
                '" . $db->escape($this->preferences->board_display_style) . "',
                " . (int)$this->preferences->hide_sidebar . ",
                " . (int)$this->preferences->hide_ads . ",
                " . (int)$this->preferences->hide_images . ",
                " . (int)$this->preferences->reply_form_at_top . ",
                " . (int)$this->preferences->auto_follow . ",
                " . (int)$this->preferences->auto_follow_reply . ",
                " . (int)$this->preferences->follow_order_by_bumptime . ",
                " . (int)$this->preferences->follow_show_floatbox . ",
                " . (int)$this->preferences->notification_from_post_replies . ",
                " . (int)$this->preferences->notification_from_thread_replies . ",
                " . (int)$this->preferences->notification_from_followed_replies . ",
                " . (int)$this->preferences->notification_from_post_upvotes . ",
                " . (int)$this->preferences->threads_per_page . ",
                " . (int)$this->preferences->preview_posts_per_thread . "
            )
            ON DUPLICATE KEY UPDATE
                custom_css = VALUES(custom_css),
                show_username = VALUES(show_username),
                style = VALUES(style),
                board_display_style = VALUES(board_display_style),
                hide_sidebar = VALUES(hide_sidebar),
                hide_ads = VALUES(hide_ads),
                hide_images = VALUES(hide_images),
                reply_form_at_top = VALUES(reply_form_at_top),
                auto_follow = VALUES(auto_follow),
                auto_follow_reply = VALUES(auto_follow_reply),
                follow_order_by_bumptime = VALUES(follow_order_by_bumptime),
                follow_show_floatbox = VALUES(follow_show_floatbox),
                notification_from_post_replies = VALUES(notification_from_post_replies),
                notification_from_thread_replies = VALUES(notification_from_thread_replies),
                notification_from_followed_replies = VALUES(notification_from_followed_replies),
                notification_from_post_upvotes = VALUES(notification_from_post_upvotes),
                threads_per_page = VALUES(threads_per_page),
                preview_posts_per_thread = VALUES(preview_posts_per_thread)");

        return $q !== false;
    }

    public function getTimezone(): string
    {
        global $engine;

        if (!empty($this->timezone)) {
            return $this->timezone;
        }

        $ip2location = new Database($engine->cfg->phpIp2LocationDBPath, Database::FILE_IO);
        $tz = $ip2location->lookup($_SERVER['REMOTE_ADDR'], Database::TIME_ZONE);

        if ($tz === '-' || $tz === '+00:00') {
            $this->timezone = 'GMT';

            return 'GMT';
        }

        $this->timezone = 'GMT' . $tz;
        return 'GMT' . $tz;
    }

    private function getPreferredLanguage()
    {
        global $engine;

        // http://www.thefutureoftheweb.com/blog/use-accept-language-header
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $locales = [];
            // break up string into pieces (languages and q factors)
            preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i',
                $_SERVER['HTTP_ACCEPT_LANGUAGE'], $locale_parse);

            if (count($locale_parse[1])) {
                // create a list like "en" => 0.8
                $locales = array_combine($locale_parse[1], $locale_parse[4]);

                // set default to 1 for any without q factor
                foreach ($locales as $locale => $val) {
                    if ($val === '') {
                        $locales[$locale] = 1;
                    }
                }

                // sort list based on value
                arsort($locales, SORT_NUMERIC);
            }

            foreach ($locales AS $locale => $priority) {
                if (strpos($locale, "-") !== false) {
                    $locale = explode("-", $locale);
                    $locale = $locale[0];
                }
                $locales[$locale] = $priority;
            }
            array_unique($locales);
            $locale_found = false;
            foreach ($locales AS $locale => $priority) {
                $arr = glob($engine->cfg->siteDir . "/inc/i18n/" . $locale . "*", GLOB_NOSORT);
                if (!empty($arr[0])) {
                    $arr = array_reverse(explode("/", $arr[0]));
                    $locale = $arr[0];
                    $val = $locale;
                    $locale_found = true;
                    break;
                }
            }

            if (!$locale_found) {
                $val = $engine->cfg->fallbackLanguage;
            }
        } else {
            $val = $engine->cfg->fallbackLanguage;
        }

        return $val;
    }

    public function getPreferences($keyStr)
    {
        if ($this->preferences === false) {
            $this->loadPreferences();
        }

        if (!$this->hasGoldAccount) {
            switch ($keyStr) {
                case 'hide_ads':
                    return false;
                case 'threads_per_page':
                    return 15;
                case 'preview_posts_per_thread':
                    return 3;
            }
        }

        if (!isset($this->preferences->$keyStr) && $this->preferences->$keyStr !== null) {
            error_log('Invalid preferences key (get): ' . $keyStr);
            return false;
        }

        return $this->preferences->$keyStr;
    }

    private function loadPreferences()
    {
        global $db;

        if ($this->preferences !== false) {
            return true;
        }

        $q = $db->q("SELECT custom_css, show_username, style, board_display_style, hide_sidebar, hide_ads, hide_images,
            reply_form_at_top, auto_follow, auto_follow_reply, follow_order_by_bumptime, follow_show_floatbox,
            notification_from_post_replies, notification_from_thread_replies, notification_from_followed_replies,
            notification_from_post_upvotes, threads_per_page, preview_posts_per_thread
            FROM user_preferences WHERE user_id = " . (int)$this->id);

        $this->preferences = new StdClass;
        if ($q->num_rows == 0) {
            $this->preferences->custom_css = '';
            $this->preferences->show_username = false;
            $this->preferences->style = 'ylilauta';
            $this->preferences->board_display_style = 'default';
            $this->preferences->hide_sidebar = false;
            $this->preferences->hide_ads = false;
            $this->preferences->hide_images = false;
            $this->preferences->reply_form_at_top = false;
            $this->preferences->auto_follow = false;
            $this->preferences->auto_follow_reply = false;
            $this->preferences->follow_order_by_bumptime = false;
            $this->preferences->follow_show_floatbox = false;
            $this->preferences->notification_from_post_replies = true;
            $this->preferences->notification_from_thread_replies = true;
            $this->preferences->notification_from_followed_replies = true;
            $this->preferences->notification_from_post_upvotes = true;
            $this->preferences->threads_per_page = 15;
            $this->preferences->preview_posts_per_thread = 3;

            return true;
        }

        $preferences = $q->fetch_all(MYSQLI_ASSOC)[0];
        foreach ($preferences AS $key => $value) {
            $this->preferences->$key = $value;
        }

        return true;
    }

    public function checkBans(): void
    {
        global $engine, $db;

        $q = $db->q('SELECT * FROM user_ban WHERE user_id = ' . (int)$this->id . ' AND is_expired = 0 LIMIT 1');
        if ($q->num_rows === 0) {
            $this->isBanned = false;
        } else {
            $this->isBanned = true;
            $this->banInfo = $q->fetch_assoc();
            if ($_SERVER['REQUEST_URI'] !== '/banned' && !$this->banInfo['is_expired'] && strtotime($this->banInfo['end_time']) <= time()) {
                $engine->redirectExit($engine->cfg->siteUrl . '/banned');
            }
        }
    }

    public function getFollowedThreads()
    {
        global $db;

        // Cookieless users
        if ($this->isLimited) {
            return [];
        }

        if (isset($this->info->followedThreads)) {
            return $this->info->followedThreads;
        }

        $orderBy = '';
        if (!$this->getPreferences('follow_order_by_bumptime')) {
            $orderBy = 'a.`unread_count` DESC, ';
        }

        $q = $db->q("SELECT a.`thread_id`, a.`last_seen_reply`, a.`unread_count`, b.`subject`, c.`url`
                FROM `user_thread_follow` a
                LEFT JOIN thread b ON b.`id` = a.`thread_id`
                LEFT JOIN `board` c ON b.`board_id` = c.`id`
                WHERE a.`user_id` = '" . (int)$this->id . "'
                ORDER BY " . $orderBy . "b.`bump_time` DESC");

        $threads = [];
        while ($thread = $q->fetch_assoc()) {
            $threads[$thread['thread_id']] = $thread;
        }

        $this->info->followedThreads = $threads;

        return $threads;
    }

    public function getStats($key)
    {
        if ($this->statistics === false) {
            $this->loadStats();
        }

        if (empty($this->statistics->$key)) {
            return 0;
        }

        return $this->statistics->$key;
    }

    private function loadStats()
    {
        global $db;

        if ($this->statistics !== false) {
            return true;
        }

        $q = $db->q("SELECT * FROM user_statistics WHERE user_id = " . (int)$this->id);

        $this->statistics = new StdClass;
        if ($q->num_rows == 0) {
            $this->statistics = new stdClass();
            $this->statistics->epic_threads = 0;
            $this->statistics->threads_followed = 0;
            $this->statistics->threads_hidden = 0;
            $this->statistics->total_pageloads = 0;
            $this->statistics->total_posts = 0;
            $this->statistics->total_threads = 0;
            $this->statistics->total_post_characters = 0;
            $this->statistics->total_uploaded_files = 0;
            $this->statistics->total_uploaded_filesize = 0;
            $this->statistics->total_upboats_given = 0;
            $this->statistics->total_upboats_received = 0;
            $this->statistics->gold_accounts_donated = 0;
            $this->statistics->gold_account_donations_received = 0;
            $this->statistics->purchases_total_price = 0;
            return true;
        }

        $this->statistics = $q->fetch_object();

        return true;
    }

    public function getUnreadNotificationCount()
    {
        global $db;

        if ($this->unread_notifications !== false) {
            return $this->unread_notifications;
        }

        $excluded = $this->getExcludedNotificationTypes();
        if (!empty($excluded)) {
            $excluded = ' AND type NOT IN (' . implode(',', $excluded) . ')';
        }

        $q = $db->q("SELECT COUNT(*) AS count FROM user_notification WHERE user_id = " . (int)$this->id . $excluded . " AND is_read = 0 LIMIT 1");
        $this->unread_notifications = $q->fetch_assoc()['count'];

        return $this->unread_notifications;
    }

    protected function getExcludedNotificationTypes()
    {
        global $engine;

        $excluded = [];

        if (!$this->getPreferences('notification_from_post_replies')) {
            $excluded[] = 1;
        }
        if (!$this->getPreferences('notification_from_thread_replies')) {
            $excluded[] = 2;
        }
        if (!$this->getPreferences('notification_from_followed_replies')) {
            $excluded[] = 3;
        }
        if (!$this->getPreferences('notification_from_post_upvotes')) {
            $excluded[] = 4;
        }

        if (empty($excluded)) {
            return false;
        }

        return $excluded;
    }

    private function validateLogin()
    {
        global $db;

        // Cookieless users
        if ($this->isLimited) {
            return;
        }

        $this->loggedIn = false;
        if (!empty($this->info->password)) {
            $this->loggedIn = true;

            return true;
        } else {
            return false;
        }
    }

    public function __destruct()
    {
        global $db;

        if (empty($this->delayed_stats_to_update)) {
            return;
        }

        $db->q("INSERT INTO user_statistics (
                    user_id,
                    epic_threads,
                    threads_followed,
                    threads_hidden,
                    total_pageloads,
                    total_posts,
                    total_threads,
                    total_post_characters,
                    total_uploaded_files,
                    total_uploaded_filesize,
                    total_upboats_given,
                    total_upboats_received,
                    gold_accounts_donated,
                    gold_account_donations_received,
                    purchases_total_price
                ) VALUES (
                    " . (int)$this->id . ",
                    " . (int)$this->statistics->epic_threads . ",
                    " . (int)$this->statistics->threads_followed . ",
                    " . (int)$this->statistics->threads_hidden . ",
                    " . (int)$this->statistics->total_pageloads . ",
                    " . (int)$this->statistics->total_posts . ",
                    " . (int)$this->statistics->total_threads . ",
                    " . (int)$this->statistics->total_post_characters . ",
                    " . (int)$this->statistics->total_uploaded_files . ",
                    " . (int)$this->statistics->total_uploaded_filesize . ",
                    " . (int)$this->statistics->total_upboats_given . ",
                    " . (int)$this->statistics->total_upboats_received . ",
                    " . (int)$this->statistics->gold_accounts_donated . ",
                    " . (int)$this->statistics->gold_account_donations_received . ",
                    " . (int)$this->statistics->purchases_total_price . "
                )
                ON DUPLICATE KEY UPDATE
                    epic_threads = VALUES(epic_threads),
                    threads_followed = VALUES(threads_followed),
                    threads_hidden = VALUES(threads_hidden),
                    total_pageloads = VALUES(total_pageloads),
                    total_posts = VALUES(total_posts),
                    total_threads = VALUES(total_threads),
                    total_post_characters = VALUES(total_post_characters),
                    total_uploaded_files = VALUES(total_uploaded_files),
                    total_uploaded_filesize = VALUES(total_uploaded_filesize),
                    total_upboats_given = VALUES(total_upboats_given),
                    total_upboats_received = VALUES(total_upboats_received),
                    gold_accounts_donated = VALUES(gold_accounts_donated),
                    gold_account_donations_received = VALUES(gold_account_donations_received),
                    purchases_total_price = VALUES(purchases_total_price)");
    }

    public function isWhitelisted()
    {
        if ($this->hasGoldAccount || $this->isMod) {
            return true;
        }

        return false;
    }

    public function useCaptcha()
    {
        global $engine, $db;

        if (!$engine->cfg->useCaptcha) {
            return false;
        }

        if (isset($this->use_captcha)) {
            return $this->use_captcha;
        }

        // Invalid account
        if (empty($this->id)) {
            $this->use_captcha = true;

            return true;
        }

        // Gold account
        if ($this->hasGoldAccount) {
            $this->use_captcha = false;

            return false;
        }

        // Less than required amount of posts
        if ($this->getStats('total_posts') < $engine->cfg->noCaptchaRequiredPosts) {
            $this->use_captcha = true;

            return true;
        }

        // New account
        if ($this->account_created >= (time() - $engine->cfg->newAccountCaptchaTime)) {
            $this->use_captcha = true;

            return true;
        }

        $this->use_captcha = false;

        return false;
    }

    public function addBan($ip, ?int $uid, int $length, int $reason, ?int $messageId = null, bool $autoban = false, string $reasonDetails = null)
    {
        global $engine, $db, $posts;

        $uid = $db->escape($uid);
        $ip = $db->escape($ip);

        $messageId = $db->escape($messageId);
        if (empty($messageId)) {
            $messageId = 0;
        }

        $reasonDetails = trim($reasonDetails);
        if (empty($reasonDetails)) {
            $reasonDetails = null;
        }

        if ($autoban) {
            $bannedBy = 'NULL';
        } else {
            $bannedBy = (int)$this->id;
            $engine->writeModlog(2, 'IP: ' . $ip . ', UID: ' . $uid, $messageId, $posts->getThreadIdByPostId($messageId), $posts->getBoardIdByPostId($messageId));
        }

        if (!empty($uid)) {
            $q = $db->q("INSERT INTO `user_ban` (`user_id`, `end_time`, `reason`, reason_details, `post_id`, `banned_by`)
			VALUES ('" . $uid . "', DATE_ADD(NOW(), INTERVAL " . (int)$length . " SECOND), " . (int)$reason . ",
			" . ($reasonDetails !== null ? "'" . $db->escape($reasonDetails) . "'" : 'NULL') .", '" . $messageId . "', " . $bannedBy . ")
		");
            $banId = (int)$db->mysql0->insert_id;
        } else {
            $q = true;
            $banId = 'NULL';
        }

        if (!empty($ip)) {

            // Limit IP bans to one day
            if ($length > 86400) {
                $length = 86400;
            }
            $qb = $db->q("INSERT INTO ban_ip (ip, ban_id, expires, added_by)
                VALUES (INET6_ATON('" . $ip . "'), " . $banId . ", DATE_ADD(NOW(), INTERVAL " . $length . " SECOND), " . $bannedBy . ")
                ON DUPLICATE KEY UPDATE
                ban_id = " . $banId . ", time = NOW(), expires = DATE_ADD(NOW(), INTERVAL " . $length . " SECOND), added_by = " . $bannedBy);
        } else {
            $qb = true;
        }

        return $q && $qb;
    }

    public function modNameById($id)
    {
        if (empty($this->mod_names)) {
            $this->loadModNames();
        }

        if (!empty($this->mod_names[$id])) {
            return $this->mod_names[$id];
        }

        return '(' . _('Unknown') . ')';
    }

    protected function loadModNames()
    {
        global $db;

        $q = $db->q("SELECT id, username FROM user WHERE user_class != 0");
        while ($row = $q->fetch_object()) {
            $this->mod_names[$row->id] = $row->username;
        }
    }

    public function removeBan($id)
    {
        global $db;

        $id = $db->escape($id);
        $q = $db->q("UPDATE `user_ban` SET `is_expired` = 1 WHERE `id` = '" . $id . "' LIMIT 1");

        if ($q) {
            return true;
        } else {
            return false;
        }
    }

    public function isValidIp($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP);
    }

    public function followThread($thread)
    {
        global $db;

        // Cookieless users
        if ($this->isLimited) {
            return false;
        }

        foreach ($this->info->followedThreads as $followed) {
            if ($followed['thread_id'] == $thread) {
                return false;
            }
        }

        $thread = $db->escape($thread);

        $q = $db->q("INSERT INTO `user_thread_follow` (`thread_id`, `user_id`) VALUES (" . (int)$thread . ", " . (int)$this->id . ")");

        // Delete threads that go over the limit of 1000 followed threads
        $db->q("DELETE a FROM user_thread_follow a JOIN (SELECT thread_id, user_id FROM user_thread_follow WHERE user_id = " . (int)$this->id . " ORDER BY added DESC LIMIT 1000 OFFSET 1000) b USING(user_id, thread_id)");

        if ($q) {
            return true;
        } else {
            return false;
        }
    }

    public function unfollowThread($thread)
    {
        global $db;

        // Cookieless users
        if ($this->isLimited) {
            return;
        }

        $thread = $db->escape($thread);

        if ($thread != 'all') {
            $q = $db->q("DELETE FROM `user_thread_follow` WHERE `thread_id` = " . (int)$thread . " AND `user_id` = " . (int)$this->id);
        } else {
            $q = $db->q("DELETE FROM `user_thread_follow` WHERE `user_id` = " . (int)$this->id);
        }

        if ($q) {
            return true;
        } else {
            return false;
        }
    }

    public function followed_clear_unread_count($thread)
    {
        global $db;

        if (empty($this->info->followedThreads[$thread])) {
            return false;
        }

        $db->q("UPDATE `user_thread_follow` SET `unread_count` = 0
            WHERE `user_id` = " . (int)$this->id . " AND `thread_id` = " . $db->escape((int)$thread) . " LIMIT 1");

        return true;
    }

    public function followed_update_last_seen_reply($thread, $last_seen_reply)
    {
        global $db;

        if (empty($this->info->followedThreads[$thread])) {
            return false;
        }
        if ($this->info->followedThreads[$thread]['last_seen_reply'] == $last_seen_reply) {
            return true;
        }

        $db->q("UPDATE `user_thread_follow` SET `last_seen_reply` = " . $db->escape((int)$last_seen_reply) . "
            WHERE `user_id` = " . (int)$this->id . " AND `thread_id` = " . $db->escape((int)$thread) . " LIMIT 1");

        return true;
    }

    public function createSession($user_id)
    {
        global $db;
        $session_id = bin2hex(random_bytes(32));
        $csrf_token = bin2hex(random_bytes(32));

        $db->q("INSERT INTO user_session (user_id, session_id, csrf_token, login_ip) VALUES (
                " . $db->escape((int)$user_id) . ", UNHEX('" . $db->escape($session_id) . "'),
                UNHEX('" . $db->escape($csrf_token) . "'), INET6_ATON('" . $db->escape($_SERVER['REMOTE_ADDR']) . "')
            )");

        return $session_id;
    }

    public function getSessions($user_id)
    {
        global $db;

        $q = $db->q("SELECT *, LOWER(HEX(session_id)) AS session_id, UNIX_TIMESTAMP(login_time) AS login_time,
            UNIX_TIMESTAMP(last_active) AS last_active, INET6_NTOA(login_ip) AS login_ip
            FROM user_session WHERE user_id = " . $db->escape((int)$user_id) . " ORDER BY last_active DESC");

        return $db->fetchAll($q);
    }

    public function destroySession($user_id, $session_id)
    {
        global $db;

        return $db->q("DELETE FROM user_session WHERE user_id = " . (int)$user_id . " AND session_id = UNHEX('" . $db->escape($session_id) . "')") !== false;
    }

    public function destroyAllSessions($user_id)
    {
        global $db;

        return $db->q("DELETE FROM user_session WHERE user_id = " . (int)$user_id . " AND session_id != UNHEX('" . $db->escape($this->session_id) . "')") !== false;
    }

    public function hasPermissions($action)
    {
        global $engine, $user;

        if ($engine->cfg->adminPermissions[$action] >= $user->info->user_class AND $engine->cfg->adminPermissions[$action] != 0) {
            return true;
        } else {
            return false;
        }
    }

    public function getUnusedGoldKeys($only_donatable = false)
    {
        global $db;

        $is_donatable = '';
        if ($only_donatable) {
            $is_donatable = $only_donatable ? ' AND is_donated = 0' : '';
        }
        $keyq = $db->q("SELECT *, DATE_ADD(`generated`, INTERVAL 3 MONTH) AS expires
            FROM gold_key
            WHERE owner_id = " . (int)$this->id . " AND is_used = 0" . $is_donatable . "
            ORDER BY `generated` ASC");

        return $db->fetchAll($keyq);
    }

    public function goldLengthToHumanReadable($length)
    {
        if ($length == 86400) {
            return '1 ' . _('day');
        }
        if ($length == 604800) {
            return '1 ' . _('week');
        }
        if ($length == 2592000) {
            return '1 ' . _('month');
        }
        if ($length == 7776000) {
            return '3 ' . _('months');
        }
        if ($length == 15552000) {
            return '6 ' . _('months');
        }
        if ($length == 31536000) {
            return '1 ' . _('year');
        }
        if ($length == 63072000) {
            return '2 ' . _('years');
        }

        return $length . ' ' . _('seconds');
    }

    public function donateKeyToUser($key, $new_owner_id, $message_id = false)
    {
        global $db;

        $key = $db->escape($key);
        $new_owner_id = (int)$new_owner_id;
        $message_id = $message_id ? (int)$message_id : 'NULL';

        if ($new_owner_id == $this->id) {
            return false;
        }

        $verify = $db->q("SELECT `key` FROM gold_key WHERE `key` = '" . $key . "' AND owner_id = " . (int)$this->id . " AND is_used = 0");
        if ($verify->num_rows == 0) {
            return false;
        }

        $newKey = bin2hex(random_bytes(10));

        $db->q("INSERT INTO gold_key_donate (gold_key, old_owner_id, new_owner_id, post_id) VALUES ('" . $key . "', " . (int)$this->id . ", " . $new_owner_id . ", " . $message_id . ")");
        $q = $db->q("UPDATE gold_key SET `key` = '" . $db->escape($newKey) . "', is_donated = 1, owner_id = " . $new_owner_id . " WHERE `key` = '" . $key . "' AND owner_id = " . (int)$this->id . " AND is_used = 0");

        $this->addNotification('gold_account_get', $new_owner_id, $message_id);
        $this->incrementStats('gold_accounts_donated');
        $this->incrementStats('gold_account_donations_received', 1, $new_owner_id);

        return $q !== false;
    }

    public function keyDonateLimitReached($key)
    {
        global $db;

        $key = $db->escape($key);

        $q = $db->q("SELECT is_donated FROM gold_key WHERE `key` = '" . $key . "' LIMIT 1");
        $count = (int)$q->fetch_assoc()['is_donated'];

        if ($count >= 1) {
            return true;
        }

        return false;
    }

    public function activateGoldKey($key)
    {
        global $db, $engine;

        // Cookieless users
        if ($this->isLimited) {
            return false;
        }

        $q = $db->q("SELECT `key`, `is_used`, `length` FROM `gold_key` WHERE `key` = '" . $db->escape($key) . "' LIMIT 1");
        if ($q->num_rows == 0) {
            return false;
        }

        $key = $q->fetch_assoc();

        if ($key['is_used']) {
            return _('This key has already been used.');
        }

        if ($this->hasPlatinumAccount) {
            return _('You already have a platinum account, you cannot activate Gold account keys.');
        }

        if ($this->hasGoldAccount AND !empty($this->info->gold_account_expires) AND strtotime($this->info->gold_account_expires) >= time()) {
            // Extend current gold account
            $keyLength = 'DATE_ADD(gold_account_expires, INTERVAL ' . (int)$key['length'] . ' SECOND)';
        } else {
            // Just activate
            $keyLength = 'DATE_ADD(NOW(), INTERVAL ' . (int)$key['length'] . ' SECOND)';
        }

        $db->q('UPDATE user SET gold_account_expires = ' . $keyLength . ' WHERE id = ' . (int)$this->id . ' LIMIT 1');
        $db->q("UPDATE `gold_key` SET `is_used` = 1, `used_by` = " . (int)$this->id . ", `used_time` = NOW() WHERE `key` = '" . $db->escape($key['key']) . "' LIMIT 1");

        return true;
    }

    public function removeGoldAccount()
    {
        global $db;

        // Cookieless users
        if ($this->isLimited) {
            return false;
        }

        $db->q('UPDATE user SET gold_account_expires = NULL WHERE id = ' . (int)$this->id . ' LIMIT 1');
        return true;
    }

    public function isFreeName($name)
    {
        global $db;

        if ($name == $this->info->username) {
            return true;
        }

        $q = $db->q("SELECT `id` FROM user WHERE `username` LIKE '" . $db->escape($name) . "' AND `id` != '" . $this->id . "' LIMIT 1");
        if ($q->num_rows == 0) {
            return true;
        } else {
            return false;
        }
    }

    public function changePassword($password, $uid = 0)
    {
        global $engine;

        if (empty($uid)) {
            $uid = $this->id;
        }
        if (empty($uid)) {
            return false;
        }

        $password = password_hash($password, $engine->cfg->passwordHashType, $engine->cfg->passwordHashOptions);

        return $this->updateAccount('password', $password, $uid);
    }

    public function changeEmail($email = null, $uid = 0)
    {
        global $engine;

        if (empty($uid)) {
            $uid = $this->id;
        }
        if (empty($uid)) {
            return false;
        }

        $email = password_hash($email, $engine->cfg->emailHashType, $engine->cfg->emailHashOptions);

        return $this->updateAccount('email', $email, $uid);
    }

    public function removeEmail($uid = 0)
    {
        if (empty($uid)) {
            $uid = $this->id;
        }

        if (empty($uid)) {
            return false;
        }

        return $this->updateAccount('email', null, $uid);
    }

    public function deleteProfile()
    {
        global $db;

        $q = $db->q("SELECT `id`, INET6_NTOA(`last_ip`) AS last_ip FROM user WHERE `id` = '" . (int)$this->id . "' LIMIT 1");
        if ($q->num_rows == 0) {
            return false;
        }

        $userInfo = $q->fetch_assoc();

        if ($userInfo['last_ip'] != $_SERVER['REMOTE_ADDR']) {
            return false;
        }

        $q = $db->q("DELETE FROM user WHERE `id` = '" . $userInfo['id'] . "' LIMIT 1");
        if ($q) {
            return true;
        } else {
            return false;
        }
    }

    public function incrementStats($statsName, int $incrBy = 1, ?int $userId = null)
    {
        global $db;

        $delayed = false;
        if (empty($userId)) {
            $userId = $this->id;
            $delayed = true;
            $this->statistics->$statsName += $incrBy;

            $statistics = $this->statistics;
        } else {
            $q = $db->q("SELECT * FROM user_statistics WHERE user_id = " . (int)$userId);

            if ($q->num_rows == 0) {
                $statistics = new stdClass();
                $statistics->epic_threads = 0;
                $statistics->threads_followed = 0;
                $statistics->threads_hidden = 0;
                $statistics->total_pageloads = 0;
                $statistics->total_posts = 0;
                $statistics->total_threads = 0;
                $statistics->total_post_characters = 0;
                $statistics->total_uploaded_files = 0;
                $statistics->total_uploaded_filesize = 0;
                $statistics->total_upboats_given = 0;
                $statistics->total_upboats_received = 0;
                $statistics->gold_accounts_donated = 0;
                $statistics->gold_account_donations_received = 0;
                $statistics->purchases_total_price = 0;
            } else {
                $statistics = $q->fetch_object();
            }

            $statistics->$statsName += $incrBy;
        }
        
        if (empty($userId)) {
            return false;
        }

        $userId = (int)$userId;
        $incrBy = (int)$incrBy;

        // Add activity points
        switch ($statsName) {
            case 'total_posts':
            case 'total_uploaded_files':
                $this->addActivityPoints($incrBy * 5, $userId);
                break;
            case 'total_threads':
                $this->addActivityPoints($incrBy * 10, $userId);
                break;
            case 'total_upboats_received':
                $this->addActivityPoints($incrBy, $userId);
                break;
            case 'total_upboats_given':
                $this->addActivityPoints(-$incrBy, $userId);
                break;
            case 'epic_threads':
                $this->addActivityPoints($incrBy * 10_000, $userId);
                break;
            default:
                break;
        }

        if (!$delayed) {
            // Only received upboats and gold donations are updated for other users
            $q = $db->q("INSERT IGNORE INTO user_statistics (
                    user_id,
                    total_upboats_received,
                    gold_account_donations_received
                ) VALUES (
                    " . (int)$userId . ",
                    " . (int)$statistics->total_upboats_received . ",
                    " . (int)$statistics->gold_account_donations_received . "
                )
                ON DUPLICATE KEY UPDATE
                    total_upboats_received = VALUES(total_upboats_received),
                    gold_account_donations_received = VALUES(gold_account_donations_received)");
        } else {
            $this->delayed_stats_to_update[] = $statistics->$statsName;
            $q = true;
        }

        return $q !== false;
    }

    public function addActivityPoints(int $points, int $userId = null)
    {
        global $db;

        if ($userId === null) {
            $userId = $this->id;
        }

        $q = $db->q("UPDATE user SET activity_points = activity_points+" . (int)$points . " WHERE id = " . (int)$userId ." LIMIT 1");

        return $q !== false;
    }

    public function getHiddenThreads()
    {
        global $db;

        // Cookieless users
        if ($this->isLimited) {
            return [];
        }

        if (isset($this->info->hiddenThreads)) {
            return $this->info->hiddenThreads;
        }

        $q = $db->q("SELECT `thread_id` FROM `user_thread_hide` WHERE `user_id` = " . (int)$this->id . " ORDER BY added DESC");
        $threads = $q->fetch_all(MYSQLI_NUM);
        $threads = array_map('current', $threads);

        $this->info->hiddenThreads = $threads;

        return $threads;
    }

    public function getHiddenNames()
    {
        global $db;

        // Cookieless users
        if ($this->isLimited) {
            return [];
        }

        if (isset($this->info->hiddenNames)) {
            return $this->info->hiddenNames;
        }

        $q = $db->q("SELECT name FROM user_name_hide WHERE user_id = " . (int)$this->id);
        $names = $q->fetch_all(MYSQLI_NUM);
        $names = array_map('current', $names);

        $this->info->hiddenNames = $names;

        return $names;
    }

    public function setHiddenNames($names = false)
    {
        global $db;

        // Cookieless users
        if ($this->isLimited) {
            return false;
        }

        $q = $db->q("DELETE FROM user_name_hide WHERE user_id = " . (int)$this->id);

        if (empty($names) || !$names) {
            return $q !== false;
        }

        $names = array_filter(array_map('trim', $names));
        $names = array_unique($names);

        $q = 'INSERT IGNORE INTO user_name_hide (user_id, name) VALUES ';

        foreach ($names AS $name) {
            $name = preg_replace('/[^A-ZÅÄÖa-zåäö0-9_\-]/u', '', $name);
            $q .= "(" . (int)$this->id . ", '" . $db->escape($name) . "'),";
        }
        $q = substr($q, 0, -1);

        $success = $db->q($q) !== false;

        if (!$success) {
            error_log("'" . $_POST['hide_names'] ."'");
        }

        return $success;
    }

    public function getHiddenBoards()
    {
        global $db;

        // Cookieless users
        if ($this->isLimited) {
            return [];
        }

        if (isset($this->info->hidden_boards)) {
            return $this->info->hidden_boards;
        }

        $q = $db->q("SELECT board_id FROM user_board_hide WHERE user_id = " . (int)$this->id);
        $boards = $q->fetch_all(MYSQLI_NUM);
        $this->info->hidden_boards = array_map('current', $boards);

        return $this->info->hidden_boards;
    }

    public function setHiddenBoards(array $board_list)
    {
        global $db;

        $db->q("DELETE FROM user_board_hide WHERE user_id = " . (int)$this->id);

        if (empty($board_list)) {
            return true;
        }

        $boards = '(';
        $i = 0;
        foreach ($board_list as $board) {
            if ($i != 0) {
                $boards .= '),(';
            }

            $boards .= $this->id . ',' . $board;
            ++$i;
        }
        $boards .= ')';
        $q = $db->q("INSERT INTO user_board_hide (user_id, board_id) VALUES " . $boards);

        return $q !== false;
    }

    public function getBoardHideStats()
    {
        global $db, $html;

        $total_users = $db->q('SELECT COUNT(DISTINCT user_id) AS count FROM user_board_hide');
        $total_users = $total_users->fetch_assoc()['count'];

        $hide_by_board = $db->q('SELECT board_id, COUNT(*) AS count FROM user_board_hide GROUP BY board_id');
        $hide_by_board = $db->fetchAll($hide_by_board, 'count', 'board_id');

        $hide_percentages = [];
        foreach ($html->getBoardList(true) AS $board) {
            if (!empty($total_users) && !empty($hide_by_board[$board['boardid']])) {
                $hide_percentages[$board['boardid']] = round($hide_by_board[$board['boardid']] / $total_users * 100);
            } else {
                $hide_percentages[$board['boardid']] = 0;
            }
        }

        return $hide_percentages;
    }

    public function getNotifications()
    {
        global $db;

        if ($this->notifications !== false) {
            return $this->notifications;
        }

        $excluded = $this->getExcludedNotificationTypes();
        if (!empty($excluded)) {
            $excluded = ' AND type NOT IN (' . implode(',', $excluded) . ')';
        }

        $q = $db->q("SELECT *, UNIX_TIMESTAMP(timestamp) AS timestamp FROM user_notification
                WHERE user_id = " . (int)$this->id . $excluded . "
                ORDER BY is_read ASC, timestamp DESC LIMIT 100");

        $this->notifications = $q->fetch_all(MYSQLI_ASSOC);

        return $this->notifications;
    }

    public function markNotificationAsRead(int $notification_id)
    {
        global $db;

        $q = $db->q(
            "UPDATE user_notification SET is_read = 1 WHERE user_id = " . (int)$this->id . " AND id = " . (int)$notification_id . " LIMIT 1"
        );

        return $q !== false;
    }

    public function markNotificationsAsReadByPostId($post_id)
    {
        global $db;

        $q = $db->q("UPDATE user_notification SET is_read = 1 WHERE user_id = " . (int)$this->id . " AND post_id = " . (int)$post_id);

        return $q !== false;
    }

    public function markNotificationsAsReadByThreadId($thread_id)
    {
        global $db;

        $q = $db->q("UPDATE user_notification SET is_read = 1 WHERE user_id = " . (int)$this->id . " AND post_id IN (SELECT id FROM post WHERE thread_id = " . (int)$thread_id . ") AND is_read = 0");

        return $q !== false;
    }

    public function markAllNotificationsAsRead()
    {
        global $db;

        $q = $db->q("UPDATE user_notification SET is_read = 1 WHERE user_id = " . (int)$this->id);

        return $q !== false;
    }

    public function addNotification($type, $user_id = false, $post_id = 'NULL', $custom_data = 'NULL', $thread_id = 'NULL', $user_post_id = 'NULL')
    {
        global $db;

        if (empty($user_id)) {
            return false;
        }
        if ($post_id != 'NULL') {
            $post_id = (int)$post_id;
        }
        if ($thread_id != 'NULL') {
            $thread_id = (int)$thread_id;
        }
        if ($user_post_id != 'NULL') {
            $user_post_id = (int)$user_post_id;
        }
        if ($custom_data != 'NULL') {
            $custom_data = "'" . $db->escape($custom_data) . "'";
        }
        $oldNotification = false;

        if ($type == 'post_reply') {
            $type = 1;
        } elseif ($type == 'thread_reply') {
            $type = 2;
        } elseif ($type == 'followed_reply') {
            $type = 3;
        } elseif ($type == 'post_upboated') {
            $type = 4;
        } elseif ($type == 'gold_account_get') {
            $type = 5;
        } elseif ($type == 'announcement') {
            $type = 6;
        } elseif ($type == 'tag_unlock') {
            $type = 7;
        } else {
            return false;
        }

        if ($type == 1) {
            if ($user_post_id == 'NULL') {
                return false;
            }
            $q = $db->q("SELECT id FROM user_notification WHERE user_id = " . (int)$user_id . " AND type = " . (int)$type . " AND user_post_id = " . $user_post_id . " AND is_read = 0 LIMIT 1");
            if ($q->num_rows == 1) {
                $oldNotification = $q->fetch_assoc();
            }
        }
        if (in_array($type, [2, 3])) {
            if ($thread_id == 'NULL') {
                return false;
            }
            $q = $db->q("SELECT id FROM user_notification WHERE user_id = " . (int)$user_id . " AND type = " . (int)$type . " AND thread_id = " . $thread_id . " AND is_read = 0 LIMIT 1");
            if ($q->num_rows == 1) {
                $oldNotification = $q->fetch_assoc();
            }
        }
        if (in_array($type, [4, 5])) {
            if ($post_id == 'NULL') {
                return false;
            }
            $q = $db->q("SELECT id FROM user_notification WHERE user_id = " . (int)$user_id . " AND type = " . (int)$type . " AND post_id = " . $post_id . " AND is_read = 0 LIMIT 1");
            if ($q->num_rows == 1) {
                $oldNotification = $q->fetch_assoc();
            }
        }

        if (!$oldNotification) {
            $q = $db->q("INSERT IGNORE INTO user_notification (user_id, type, thread_id, post_id, user_post_id, custom_info) VALUES
                (" . (int)$user_id . ", " . (int)$type . ", " . $thread_id . ", " . $post_id . ", " . $user_post_id . ", " . $custom_data . ")");
        } else {
            $q = $db->q("UPDATE user_notification SET count = count+1 WHERE id = " . (int)$oldNotification['id'] . " LIMIT 1");
        }

        return $q !== false;
    }

    public function getDisplayStyle()
    {
        if (isset($this->displayStyle)) {
            return $this->displayStyle;
        }

        $display_style = 'style-replies';
        if (empty($this->styleOverride)) {
            if ($this->getPreferences('board_display_style') == 'grid') {
                $display_style = 'style-box';
            } elseif ($this->getPreferences('board_display_style') == 'compact') {
                $display_style = 'style-compact';
            }

            return $display_style;
        }

        if (in_array($this->styleOverride, ['box', 'compact'])) {
            return 'style-' . $this->styleOverride;
        }

        $this->displayStyle = $display_style;

        return $display_style;
    }

    public function emailIssetString()
    {
        if (isset($this->info->email))
        {
            return _("Exists");
        }

        return _("Not set");
    }
}
