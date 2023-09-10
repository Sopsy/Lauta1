<?php
declare(strict_types=1);

namespace TVGuide\Contract;

interface Channel
{
    public function dataId(): string;

    public function name(): string;

    public function urlSafeName(): string;
}