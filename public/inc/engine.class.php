<?php

require __DIR__ . '/../../vendor/autoload.php';

define('SCRIPT_NONCE', $_SERVER['REQUEST_ID'] ?? bin2hex(random_bytes(8)));

class engine
{
    public $cfg;
    private int $antispam;
    private $filesizeUnits = ['B', 'KB', 'MB', 'GB', 'TB', 'EB', 'PB'];
    private IntlDateFormatter $timeFormatter;

    public function __construct($loadClass = false)
    {
        global $engine;

        $this->loadConfig();
        $this->checkAccess();
        $engine = $this;

        if ($this->cfg->debug) {
            $this->queryCount = 0;
            $this->queryTime = 0;
            $this->executedQueries = [];
        }

        // UTF8 should always be just fine.
        mb_internal_encoding("UTF-8");

        if ($loadClass) {
            if (!is_array($loadClass)) {
                $loadClass = [$loadClass];
            }
            foreach ($loadClass as $className => $confVars) {
                global $$className;
                $$className = $this->loadClass($className, $confVars);
            }
        }

        // Block automated requests
        $q = $db->q('SELECT hits, last_seen FROM ban_bot_ip WHERE ip = INET6_ATON("' . $_SERVER['REMOTE_ADDR'] .'") LIMIT 1');
        $row = $q->fetch_object();
        if ($row && $row->hits >= 1 && strtotime($row->last_seen) < time() - 91741) {
            die();
        }
    }

    public function getAccessCookieKey(): string
    {
        $keys = [
            'HTTP_ACCEPT_LANGUAGE',
            'HTTP_DNT',
            'HTTP_HOST',
        ];

        $cookieKey = '';
        foreach ($keys as $key) {
            if (empty($_SERVER[$key])) {
                continue;
            }
            $cookieKey .= $_SERVER[$key];
        }

        return md5(date('Ymd') . $cookieKey . ')(D)Dg0adgojhDA)(G/Yoakjldgn9d01a(/G&YA)D=G/(&Y2/("&%#"%Tµ&QGÄÖåadfG)äA*"¤¨¨X');
    }

    public function checkAccess()
    {
        // Allow googlebot
        if (($_SERVER['IS_ALLOWED_BOT'] ?? '0') === '1') {
            return;
        }

        // Allow CLI
        if (PHP_SAPI === 'cli') {
            return;
        }

        // Skip for POST (not really wise, but lazy)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return;
        }

        $cookieKey = $this->getAccessCookieKey();

        // Missing or invalid key
        if (empty($_COOKIE['key']) || !hash_equals($cookieKey, $_COOKIE['key'])) {
            $ip2proxy = new \IP2Proxy\Database();
            $ip2proxy->open($this->cfg->phpIp2ProxyDBPath);
            $proxyType = $ip2proxy->isProxy($_SERVER['REMOTE_ADDR']);
            $isProxy = $proxyType === 1 || $proxyType === 2;

            if ($this->ipIsWhitelisted()) {
                $isProxy = false;
            }

            if ($isProxy) {
                ?>
                <!DOCTYPE html>
                <html lang="en">
                <head>
                    <title>Ylilauta</title>
                    <meta name="robots" content="noindex">
                    <link rel="icon" href="<?= $this->cfg->staticUrl ?>/favicon.png" type="image/png">
                    <link rel="stylesheet" href="<?= $this->cfg->staticUrl ?>/css/1.0/icons.css">
                    <link rel="stylesheet" href="<?= $this->cfg->staticUrl ?>/css/1.0/ylilauta.css">
                    <script src="<?= $this->cfg->staticUrl ?>/js/1.2/Locale/en_US.UTF-8.default.js" defer></script>
                    <script type="module" src="<?= $this->cfg->staticUrl ?>/js/1.4/Bootstrap.js" defer></script>
                    <script nonce="<?= SCRIPT_NONCE ?>" src="https://www.google.com/recaptcha/api.js?render=explicit" async defer></script>
                </head>
                <body class="no-sidebar">
                <div class="front">
                <div id="title">
                    <img src="https://static.ylilauta.org/img/logo/norppa_ylilauta.svg" alt="Ylilauta" width="175">
                    <h1>Beep boop?</h1>
                </div>
                <p>Your internet connection is one that bots commonly use. Please click the button below to prove that you are not one.</p>
                <p>Nettiyhteytesi on sellainen, jota botit monesti käyttävät. Klikkaa allaolevaa nappia todistaaksesi ettet ole yksi niistä.</p>
                <form class="accesscheck" name="accesscheck" action="/scripts/ajax/checkaccess.php" method="post" data-e="checkAccess">
                    <input type="submit" class="linkbutton" value="Continue to Ylilauta / Jatka Ylilaudalle" />
                </form>
                <p class="protectedbyrecaptcha"><?= $this->getCaptchaText() ?></p>
                <p>Having issues? You can contact us at info@ylilauta.org.</p>
                </div>
                <script nonce="<?= SCRIPT_NONCE ?>">
                    window.user = {csrfToken: null};
                    window.captchaPublicKey = '<?= htmlspecialchars($this->cfg->reCaptchaPublicKey, ENT_QUOTES | ENT_HTML5) ?>';
                </script>
                </body>
                </html>
                <?php
            } else {
                ?>
                <script nonce="<?= SCRIPT_NONCE ?>">
                    if (navigator.cookieEnabled) {
                        document.cookie = "key=<?= $cookieKey ?>;path=/;max-age=43200;secure;samesite=lax";
                        window.location.reload();
                    } else {
                        alert("This website requires you to allow cookies. Please enable cookies in your browser!");
                    }
                </script>
                <noscript>This website requires JavaScript. Please enable JavaScript!</noscript>
                <?php
            }
            die();
        }
    }

    public function ipIsBanned(string $ip): bool
    {
        global $db;

        $ip = $db->escape($ip);
        $q = $db->q("SELECT * FROM ban_ip WHERE ip = INET6_ATON('" . $ip . "') LIMIT 1");

        return $q->num_rows !== 0;
    }

    private function logBlockedIp($ip, $user, $reason)
    {
        error_log("Blocked IP {$ip} ({$reason}), user {$user}" . (!empty($_POST['msg'])?", message: {$_POST['msg']}":''));
    }

    public function ipIsAllowed($ip)
    {
        global $user;

        $ip2proxy = new \IP2Proxy\Database();
        $ip2proxy->open($this->cfg->phpIp2ProxyDBPath);
        $proxyType = $ip2proxy->isProxy($ip);
        $isProxy = $proxyType === 1 || $proxyType === 2;

        if (($_SERVER['IP_BLACKLISTED'] ?? '0') === '1') {
            if ($this->ipIsWhitelisted()) {
                return true;
            }

            if (!$isProxy) {
                $asn = trim(shell_exec('timeout --signal=KILL 3 whois -h whois.radb.net '
                    . escapeshellarg($ip) . ' | grep "origin:" | awk \'{print $2}\' | head -n 1'));
            }
            $this->logBlockedIp($ip, $user->id, 'Nginx, IP2Proxy: ' . ($isProxy ? 'HIT (' . $proxyType . ')' : 'MISS (' . $asn . ')'));
            return false;
        }

        if ($isProxy) {
            if ($this->ipIsWhitelisted()) {
                return true;
            }

            $this->logBlockedIp($ip, $user->id, 'IP2Proxy (' . $proxyType . ')');
            return false;
        }

        return true;
    }

    public function ipIsWhitelisted(): bool
    {
        return ($_SERVER['IP_WHITELISTED'] ?? '0') === '1';
    }

    private function loadConfig()
    {
        $cfg = new stdClass();
        require 'config.php';
        $this->cfg = $cfg;
    }

    private function loadClass($className, $confVars)
    {
        // To prevent loading just about anything, we just manually define all
        // allowed class names
        $allowedClasses = ['db', 'html', 'board', 'user', 'posts', 'files', 'fileupload', 'cache', 'email'];
        if (!in_array($className, $allowedClasses)) {
            return false;
        }

        // Include the class file and load the class
        require($className . '.class.php');

        return new $className($confVars);
    }

    public function __destruct()
    {
        if (!empty($this->cfg) && $this->cfg->debug) {
            echo "\r\n<!--\r\nDEBUG Active\r\n\r\nExecuted Database Queries:\r\n";
            print_r($this->executedQueries);
            echo "\r\n" . 'Total time spent on queries: ' . $this->queryTime / 1000 . " s\r\n" . '-->';
        }
    }

    public function redirectExit($goto = '/')
    {
        header("Location: " . $goto);
        die();
    }

    public function old_dieWithError($str)
    {
        global $html;

        if ($html) {
            $html->printHeader();
            $html->printSidebar();
            echo '
            <div id="right" class="error center">
				<h1>' . _('An error occurred') . '</h1>
				<h2>' . $str . '</h2>
			    <p><a href="javascript:history.go(-1);">' . _('Go back') . '</a></p>
			</div>
			';
        } else {
            echo '
			<p><strong>' . _('Error:') . '</strong> ' . $str . '</p>
			<p><a href="javascript:history.go(-1);">' . _('Go back') . '</a></p>';
        }
        die();
    }

    function dieWithError($str)
    {
        header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
        echo $str;
        die();
    }

    public function convertFilesize($bytes, $precision = 2)
    {
        $factor = (int)((strlen($bytes) - 1) / 3);
        $size = sprintf("%.{$precision}f", $bytes / pow(1024, $factor));

        return $size . _($this->filesizeUnits[$factor]);
    }

    public function formatDuration($duration)
    {
        return floor($duration / 60) . ':' . str_pad($duration % 60, 2, '0', STR_PAD_LEFT);
    }

    public function writeModlog($actionId, $customInfo = '', ?int $postId = null, ?int $threadId = null, ?int $boardId = null)
    {
        global $db, $user;

        if (empty($actionId) || !is_numeric($actionId)) {
            $actionId = 0;
        }

        $user_id = $user->id;
        if ($actionId == 27) // No user id for logins
        {
            $user_id = 0;
        }

        if ($postId === null) {
            $postId = 'NULL';
        } else {
            $postId = (int)$postId;
        }

        if ($threadId === null) {
            $threadId = 'NULL';
        } else {
            $threadId = (int)$threadId;
        }

        if ($boardId === null) {
            $boardId = 'NULL';
        } else {
            $boardId = (int)$boardId;
        }

        $customInfo = $db->escape($customInfo);
        $ip = $db->escape($user->getIp());
        $q = $db->q("INSERT INTO admin_log (user_id, action_id, board_id, thread_id, post_id, custom_info, ip)
            VALUES (" . (int)$user_id . ", " . (int)$actionId . ", " . $boardId . ", " . $threadId . ", " . $postId . ", '" . $customInfo . "', INET6_ATON('" . $ip . "'))");

        return $q !== false;
    }

    public function formatTime($locale, $user, $time): string
    {
        if (!isset($this->timeFormatter)) {
            $this->timeFormatter = new IntlDateFormatter(
                $locale,
                IntlDateFormatter::MEDIUM,
                IntlDateFormatter::MEDIUM,
                $user->getTimezone()
            );
        }

        return $this->timeFormatter->format($time);
    }

    public function loadLocale($locale, $domain = 'default')
    {
        $locale = addslashes($locale);
        $domain = addslashes($domain);
        // Load localization
        setlocale(LC_ALL, $locale);
        bindtextdomain($domain, $this->cfg->siteDir . '/inc/i18n');
        bind_textdomain_codeset($domain, 'UTF-8');
        textdomain($domain);
    }

    public function getCaptchaText(): string
    {
        return sprintf(_('Protected by reCAPTCHA (%sPrivacy%s - %sTerms%s)'),
            '<a href="https://policies.google.com/privacy" rel="noopener nofollow">', '</a>',
            '<a href="https://policies.google.com/terms" rel="noopener nofollow">', '</a>');
    }

    public function verifyReCaptchaV3($response): bool
    {
        global $engine;

        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = [
            'secret' => $this->cfg->reCaptchaPrivateKey,
            'response' => $response,
            'remoteip' => $_SERVER['REMOTE_ADDR'],
        ];

        $context = stream_context_create([
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
            ],
        ]);
        $result = file_get_contents($url, false, $context);
        if (!$result) {
            error_log('CAPTCHA: Error validating');

            return false;
        }
        $origResult = $result;
        $result = json_decode($result);

        if (!empty($result->success) && $result->success && !empty($result->score) && $result->score >= $engine->cfg->captchaScoreLimit) {
            return true;
        }

        error_log($origResult);

        return false;
    }

    public function getNewestPostId($cacheTime = 60)
    {
        global $db, $cache;

        $cached = $cache->get('newestPostId' . $cacheTime);
        if ($cached) {
            return $cached;
        }

        if (empty($db)) {
            return false;
        }
        $q = $db->q("SELECT `id` FROM `post` ORDER BY `id` DESC LIMIT 1");

        $newestPostId = $q->fetch_assoc()['id'];
        $cache->set('newestPostId' . $cacheTime, $newestPostId, $cacheTime);

        return $newestPostId;
    }

    public function return_not_found(int $responseCode = null, string $additionalInfo = '')
    {
        global $db, $html, $user, $board;

        if ($responseCode === 410) {
            http_response_code(410);
        }

        include(__DIR__ . '/../404.php');
        die();
    }

    public function user_can_access_board()
    {
        global $board, $user;

        // Board not defined
        if (empty($_GET['board'])) {
            return false;
        }

        // Invalid board
        if (!$board->isLoaded) {
            return false;
        }

        // Too much pages
        if (!empty($_GET['page']) && $_GET['page'] > $board->info['pageCount']) {
            return false;
        }

        // Special boards
        if ($board->info['url'] == 'bilderberg' AND !$user->hasGoldAccount) {
            $this->old_dieWithError('Tämä alue on vain kultatililäisten käytettävissä.<br /><br /><a href="' . $this->cfg->goldAccountLink . '">Lue lisää kultatilistä...</a>');
            return false;
        }
        if ($board->info['url'] == 'platina' AND !$user->hasPlatinumAccount) {
            return false;
        }

        return true;
    }

    public function user_can_access_thread()
    {
        global $board, $user;

        // Thread not defined
        if (empty($_GET['thread'])) {
            return false;
        }

        // Thread does not exist
        if (empty($board->threads)) {
            return false;
        }

        return true;
    }

    public function randString($length = 40, $lowercase = false)
    {

        $chars = '1234567890abcdefghijklmnopqrstuvwxyz';
        if (!$lowercase) {
            $chars .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }

        $str = "";
        for ($i = 0; $i < $length; ++$i) {
            $str .= $chars[mt_rand(0, (strlen($chars) - 1))];
        }

        return $str;
    }

    public function antispam(): int
    {
        global $db;

        if (empty($this->antispam)) {
            $q = $db->q('SELECT enabled FROM antispam WHERE enabled_time > DATE_SUB(NOW(), INTERVAL 12 HOUR) LIMIT 1');
            $entry = $q->fetch_assoc();
            if ($entry && $entry['enabled']) {
                $this->antispam = $this->cfg->antispam;
            } else {
                $this->antispam = 0;
            }
        }

        return $this->antispam;
    }
}


