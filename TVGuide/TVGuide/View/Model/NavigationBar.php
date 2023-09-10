<?php
declare(strict_types=1);

namespace TVGuide\View\Model;

use DateInterval;
use DateTimeImmutable;
use IntlDateFormatter;
use function var_dump;

final class NavigationBar
{
    private $date;
    private $customDateActive;
    private $firstProgramDate;
    private $lastProgramDate;

    public function __construct(
        DateTimeImmutable $date,
        bool $customDateActive,
        DateTimeImmutable $firstProgramDate,
        DateTimeImmutable $lastProgramDate
    )
    {
        $this->date = $date;
        $this->customDateActive = $customDateActive;
        $this->firstProgramDate = $firstProgramDate;
        $this->lastProgramDate = $lastProgramDate;
    }

    public function previousDate(): string
    {
        return $this->date->sub(new DateInterval('P1D'))->format('Y-m-d');
    }

    public function currentDate(): string
    {
        return $this->date->format('Y-m-d');
    }

    public function currentDateIntl(): string
    {
        $formatter = new IntlDateFormatter(
            'fi_FI.UTF-8',
            IntlDateFormatter::SHORT,
            IntlDateFormatter::NONE,
            'Europe/Helsinki'
        );

        return $formatter->format($this->date);
    }

    public function nextDate(): string
    {
        return $this->date->add(new DateInterval('P1D'))->format('Y-m-d');
    }

    public function firstProgramDate(): string
    {
        return $this->firstProgramDate->format('Y-m-d');
    }

    public function lastProgramDate(): string
    {
        return $this->lastProgramDate->format('Y-m-d');
    }
}