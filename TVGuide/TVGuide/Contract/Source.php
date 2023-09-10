<?php
declare(strict_types=1);

namespace TVGuide\Contract;

interface Source
{
    public function sources(): array;
}