<?php
declare(strict_types=1);

use TVGuide\Controller\Modal\ProgramModal;
use TVGuide\Controller\Error;
use TVGuide\Controller\Index;
use TVGuide\Controller\Radio;

return [
    '/' => [Index::class, 'index'],
    '/radio/' => [Radio::class, 'index'],
    '/radio/([0-9]{4}-[0-9]{2}-[0-9]{2})' => [Radio::class, 'date'],
    '/([0-9]{4}-[0-9]{2}-[0-9]{2})' => [Index::class, 'date'],
    '/modal/(\d\d*)' => [ProgramModal::class, 'index'],
    '.*' => [Error::class, 'notFound'],
];