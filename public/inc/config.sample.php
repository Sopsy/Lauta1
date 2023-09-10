<?php /** @noinspection DuplicatedCode */

$cfg->goldAccountLink = '/gold';

// Bans, posting limits
$cfg->antispam = 3600;
$cfg->reportBlockedUsers = [
    1234,
    5678,
];

// Database settings
$cfg->dbHost = 'p:localhost';
$cfg->dbName = 'ylilauta';
$cfg->dbUser = 'root';
$cfg->dbPass = 'vagrant';

// Site general settings
$cfg->siteName = 'Ylilauta';
$cfg->siteMotto = 'Suomalaisten sananvapauden puolestapuhuja';
$cfg->siteLogoUrl = '/img/logo/norppa_ylilauta.svg';
$cfg->siteUrl = 'http://localhost:9002';
$cfg->noreplyEmail = 'noreply@ylilauta.org';
$cfg->replyToEmail = 'info@ylilauta.org';
$cfg->staticUrl = '/static';
$cfg->thumbsUrl = '/files/thumb';
$cfg->filesUrl = '/files/full';
$cfg->videosUrl = '/files/full';
$cfg->siteDir = dirname(__DIR__);
$cfg->staticDir = '/vagrant/static';
$cfg->filesDir = '/vagrant/files';
$cfg->flagsDir = '/img/flags';
$cfg->uidCryptPepper = 'some long string here';
$cfg->passwordHashType = PASSWORD_ARGON2I;
$cfg->passwordHashOptions = [
    'memory_cost' => 1024 * 64,
    'time_cost' => 10,
    'threads' => 2,
];
$cfg->emailHashType = PASSWORD_ARGON2I;
$cfg->emailHashOptions = [
    'memory_cost' => 1024 * 64,
    'time_cost' => 40,
    'threads' => 2,
];

// RRD
$cfg->rrdDir = $cfg->siteDir . '/../data/rrd';
$cfg->rrdGraphOutputDir = $cfg->staticDir . '/img/graphs';
$cfg->rrdGraphOutputUrl = $cfg->staticUrl . '/img/graphs';
$cfg->rrdGraphOptions = [
    "--end" => time(),
    "--color=BACK#FFE",
    "--color=FONT#800",
    "--color=ARROW#0000",
    "--color=AXIS#8005",
    "--color=CANVAS#00000000",
    "--color=MGRID#80000050",
    "--color=GRID#80000020",
    "--width" => '900',
    "--height" => '200',
    "--border" => "0",
    "--lower-limit" => "0",
    "--imgformat" => "SVG",
    "--slope-mode",
    "--full-size-mode",
    "--no-legend",
];

// Gold account purchases
$cfg->securycastId = '';
$cfg->securycastAuth = '';

// File uploads
$cfg->maxImageSize = 50000000; // Pixels in total (100000000 = 100 megapixels = approx 1GB memory required for conversion (10 bytes per pixel))
$cfg->maxFileSize = 104857600;
$cfg->minFreeSpace = 1073741824;
$cfg->gifMaxFrames = 4000;
$cfg->audioMaxLength = 3600;
$cfg->videoMaxLength = 900;
$cfg->phpIp2LocationDBPath = $cfg->siteDir . '/../data/ip2location-db/db12-ipv6.bin';
$cfg->phpIp2ProxyDBPath = $cfg->siteDir . '/../data/ip2location-db/px2.bin';
$cfg->allowedFiletypes = [
    'jpg' => ['image/jpeg', 'image/pjpeg'],
    'jpeg' => ['image/jpeg', 'image/pjpeg'],
    'png' => ['image/png'],
    'gif' => ['image/gif'],
    'aac' => ['audio/aac', 'audio/x-hx-aac-adts'],
    'mp3' => ['audio/mpeg', 'audio/mp3'],
    'm4a' => ['audio/mp4', 'audio/x-m4a'],
    'mp4' => ['video/mp4', 'audio/mp4', 'video/x-m4v'],
    'mov' => ['video/quicktime'],
    'webm' => ['video/webm', 'audio/webm'],
];

// Thumbnailing
$cfg->pngMaxFullSize = 262144;
$cfg->jpgQuality = 80;
$cfg->thumbJpgQuality = 50;
$cfg->pngCompression = 95;
$cfg->thumbWidth = 240;
$cfg->thumbHeight = 240;
$cfg->jpegtranBin = '/usr/bin/jpegtran';
$cfg->pngcrushBin = '/usr/bin/pngcrush';
$cfg->pngcrushOptions = '-reduce -fix -rem alla -l 9';
$cfg->imagickMemoryLimit = 128 * 1024 * 1024; // Bytes
$cfg->imagickMapLimit = 256 * 1024 * 1024; // Bytes
$cfg->imagickDiskLimit = 512 * 1024 * 1024; // Bytes

require 'config/tags.php';

// reCAPTCHA
$cfg->useCaptcha = true;
$cfg->newAccountCaptchaTime = 1; // seconds
$cfg->noCaptchaRequiredPosts = 100;
$cfg->reCaptchaPublicKey = '6LcfqKMUAAAAAPIBK_aGy8fAtU8E6CaZ72R1IkQa';
$cfg->reCaptchaPrivateKey = '6LcfqKMUAAAAAMVhZ8Jt1VqPRV6Pb3nqk3f0ow2P';
$cfg->captchaScoreLimit = 0.5;

// Limits
$cfg->maxRepliesPerPost = 5;
$cfg->maxThreadReplies = 1000;
$cfg->goldThreadDelay = 30;
$cfg->goldReplyDelay = 5;
$cfg->threadDelay = 60;
$cfg->replyDelay = 10;
$cfg->nameMaxLength = 30;
$cfg->subjectMaxLength = 60;
$cfg->messageMaxLength = 12000;
$cfg->msgPreviewLength = 500;
$cfg->appealTextMaxLength = 250;
$cfg->boardPages = 50;

// Cookies, caches and other time settings
$cfg->cookietime = (time() + 60 * 60 * 24 * 365);
$cfg->wordAutobanLength = 300;

// Appearance
$cfg->threadsPerPage = 10;
$cfg->threadsPerPageCompact = 50;
$cfg->threadsPerPageBox = 200;
$cfg->replyCount = 3;
$cfg->replyCountOpen = 500;
$cfg->bestThreadsPages = 10;

$cfg->defaultStyle = 'ylilauta';
$cfg->availableStyles = [
    'ylilauta' => [
        'name' => 'Ylilauta',
        'color' => '#ffe4c9',
    ],
    'peruslauta' => [
        'name' => 'Peruslauta',
        'color' => '#c9e4ff',
    ],
    'halloween' => [
        'name' =>  'Halloween',
        'color' => '#772200',
    ],
    'northboard' => [
        'name' =>  'Northboard',
        'color' => '#252525',
    ],
    'ylilauta_2011' => [
        'name' =>  'Ylilauta 2011 (no mobile support)',
        'color' => '#ffe4c9',
    ],
];

// Misc
$cfg->debug = false; // Should not be used in production. Prints some extra information at the end of the HTML-source.

// Rules
$cfg->ruleOptionsB = [
    'Muu' => [
        'boards' => ['*'],
        'Viestissä on keskustelua- ja/tai kuva minusta ja haluan sen poistettavan' => [],
        'Jokin muu syy' => [],
    ],
    'Kaikki keskustelualueet' => [
        'boards' => ['*'],
        'Laiton tai vaarallinen sisältö' => ['deletePost' => true, 'banLength' => 604800],
        'Roskapostitus' => ['deletePost' => true, 'deletePosts24h' => true, 'banLength' => 86400],
        'Haitallinen sisältö' => ['deletePost' => true, 'banLength' => 86400],
        'Mainostaminen' => ['deletePost' => true, 'deletePosts24h' => true, 'banLength' => 259200],
        'Häiriköinti' => ['deletePost' => true, 'banLength' => 86400],
        'Seksuaalinen sisältö' => ['deletePost' => true, 'banLength' => 86400],
    ],
    'Kaikki keskustelualueet pl. Satunnainen, Sekalainen ja International' => [
        'boards' => ['*', '!sekalainen', '!anime', '!bilderberg', '!int'],
        'Sopimaton sisältö' => ['deletePost' => true, 'banLength' => 86400],
    ],
];

// Mod stuff
$cfg->adminPermissions = [ // 0 = disabled, 1 = admins, 2 = supermods, 3 = regular mods
    'bulletinboard' => 3,
    'postcounts' => 2,
    'modlog' => 2,
    'admins' => 2,
    'infocategories' => 1,
    'infoposts' => 1,
    'manageboards' => 1,
    'wordfilters' => 1,
    'autobans' => 3,
    'searchfile' => 3,
    'deletedposts' => 1,
    'deletepost' => 2,
    'updatethread' => 3,
    'reportedposts' => 3,
    'banappeals' => 3,
    'addban' => 3,
    'managebans' => 3,
    'displaypostip' => 2,
    'viewpreviousbans' => 3,
    'managegold' => 1,
    'uidname' => 1,
    'poststream' => 2,
    'multipleposts' => 1,
    'userposts' => 1,
    'spamguard' => 3,
    'lockedusers' => 3,
];

// Internationalization
$cfg->availableLanguages = [
    'en_US.UTF-8' => 'English',
    'fi_FI.UTF-8' => 'Suomi (Finnish)',
    'sv_SE.UTF-8' => 'Svenska (Swedish)',
    'de_DE.UTF-8' => 'Deutsch (German)',
];
$cfg->fallbackTimezone = 'Europe/Helsinki';
$cfg->fallbackLanguage = 'fi_FI.UTF-8';

$cfg->countryNames = require 'config/countrynames.php';