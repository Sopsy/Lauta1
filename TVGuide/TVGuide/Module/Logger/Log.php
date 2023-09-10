<?php
declare(strict_types=1);

namespace TVGuide\Module\Logger;

use TVGuide\Contract\Logger;
use function date;
use function fwrite;
use const STDERR;
use const STDOUT;

final class Log implements Logger
{
    private $quiet;

    public function __construct(bool $quiet)
    {
        $this->quiet = $quiet;
    }

    public function info(string $message): void
    {
        if ($this->quiet) {
            return;
        }

        fwrite(STDOUT, $this->message('INFO', $message));
    }

    public function error(string $message): void
    {
        fwrite(STDERR, $this->message('ERROR', $message));
    }

    private function message(string $type, string $message): string
    {
        $timestamp = date('Y-m-d H:i:s');

        return "{$timestamp} [{$type}]: {$message}\n";
    }
}