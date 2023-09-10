<?php
declare(strict_types=1);

namespace TVGuide\Contract;

use DateTimeImmutable;

interface Program
{
    public function title(): string;

    public function channel(): Channel;

    public function description(): string;

    public function startTime(): DateTimeImmutable;

    public function endTime(): DateTimeImmutable;

    public function season(): ?int;

    public function episode(): ?int;

    public function episodes(): ?int;

    /**
     * @return Image[]
     */
    public function images(): array;

}