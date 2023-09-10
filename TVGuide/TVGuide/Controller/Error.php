<?php
declare(strict_types=1);

namespace TVGuide\Controller;

use function http_response_code;

final class Error
{
    public function notFound(): void
    {
        // FIXME: Return renderable
        http_response_code(404);
        die('404 Not Found');
    }
}