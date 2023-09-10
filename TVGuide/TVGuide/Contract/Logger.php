<?php
declare(strict_types=1);

namespace TVGuide\Contract;

interface Logger
{
    public function info(string $message): void;

    public function error(string $message): void;
}