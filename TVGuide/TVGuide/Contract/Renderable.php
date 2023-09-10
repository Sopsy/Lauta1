<?php
declare(strict_types=1);

namespace TVGuide\Contract;

interface Renderable
{
    public function render(): string;
}