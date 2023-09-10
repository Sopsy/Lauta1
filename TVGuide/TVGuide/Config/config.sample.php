<?php
declare(strict_types=1);
putenv('TMPDIR=/var/www/html/dev_data/tmp');

return [
    'baseUrl' => '',
    # Directory for converted images
    'imgDir' => '/var/www/html/TVGuide/images',
    # Url for images
    'imgUrl' => '/images',
    # Url for static files
    'staticUrl' => '/static',
    # Max image width and height
    'imgMaxSize' => 1280,
    # Image quality (all images are jpeg)
    'imgQuality' => 80,
    'sources' => [
        '/var/www/html/dev_data/venetsia',
        'ftp://EPG:eurosport@delivery.eurosport.com/EPG_Eurosport/EurosportFinland/2019_6Weeks_DESCRIPTION_FINNISH.xml',
        'ftp://EPG:eurosport@delivery.eurosport.com/EPG_Eurosport/Eurosport2Sweden/2019_3Weeks_DESCRIPTION_FINNISH.xml',
        '/var/www/html/dev_data/globallistings',
        'https://discoveryexports.pawa.tv/discovery/europe/APEUFIN.xml',
        'https://discoveryexports.pawa.tv/discovery/europe/DCFIFIN.xml'
    ],
    # Database configuration
    'database' => [
        'dsn' => 'mysql:host=mysql;dbname=tvguide;charset=utf8mb4;',
        'user' => 'root',
        'pass' => 'tvguide',
        'options' => [
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        ]
    ]
];
