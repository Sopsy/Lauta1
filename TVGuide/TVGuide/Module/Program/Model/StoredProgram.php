<?php
declare(strict_types=1);

namespace TVGuide\Module\Program\Model;

use DateTimeImmutable;
use TVGuide\Contract\Channel;
use TVGuide\Contract\Identifiable;
use TVGuide\Contract\Image;
use TVGuide\Contract\Program;
use TVGuide\Module\Channel\Model\StoredChannel;
use function _;
use function strftime;

final class StoredProgram implements Identifiable, Program
{
    private $id;
    private $title;
    private $channel;
    private $description;
    private $startTime;
    private $endTime;
    private $season;
    private $episode;
    private $episodes;
    private $now;
    private $nowStamp;

    public function __construct(
        int $id,
        string $title,
        StoredChannel $channel,
        string $description,
        DateTimeImmutable $startTime,
        DateTimeImmutable $endTime,
        ?int $season,
        ?int $episode,
        ?int $episodes
    )
    {
        $this->id = $id;
        $this->title = $title;
        $this->channel = $channel;
        $this->description = $description;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->season = $season;
        $this->episode = $episode;
        $this->episodes = $episodes;
        $this->now = new DateTimeImmutable();
        $this->nowStamp = $this->now->getTimestamp();
        if ($this->description === '') {
            $this->description = _('No description.');
        }
    }

    public static function fromProgram(int $id, Program $program)
    {
        return new StoredProgram(
            $id,
            $program->title(),
            $program->channel(),
            $program->description(),
            $program->startTime(),
            $program->endTime(),
            $program->season(),
            $program->episode(),
            $program->episodes()
        );
    }

    public function id(): int
    {
        return $this->id;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function channel(): Channel
    {
        return $this->channel;
    }

    public function storedChannel(): StoredChannel
    {
        return $this->channel;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function startTime(): DateTimeImmutable
    {
        return $this->startTime;
    }

    public function endTime(): DateTimeImmutable
    {
        return $this->endTime;
    }

    public function endTimeLocal(): string
    {
        return strftime('%H:%M', $this->endTime->getTimestamp());
    }

    public function startTimeLocal(): string
    {
        return strftime('%H:%M', $this->startTime->getTimestamp());
    }

    public function startTimeLocalLong(): string
    {
        #
        return strftime('%A %d.%m. klo. %H:%M', $this->startTime->getTimestamp());
    }

    public function running(): bool
    {
        $currentTime = $this->now;

        return $this->startTime() < $currentTime && $this->endTime() > $currentTime;
    }

    public function hasEnded(): bool
    {
        return $this->endTime() < $this->now;
    }

    public function runningPercentage(): float
    {
        if ($this->hasEnded()) {
            return 100;
        }

        if (!$this->running()) {
            return 0;
        }

        $nowTimestamp = $this->nowStamp;
        $startTimestamp = $this->startTime->getTimestamp();
        $endTimestamp = $this->endTime->getTimestamp();
        $percentage = (int)(($nowTimestamp - $startTimestamp) / ($endTimestamp - $startTimestamp) * 100);

        return $percentage;
    }

    public function getEpisodeString(): string
    {
        $string = '';
        if ($this->season) {
            $string .= 'S' . $this->season;
        }
        if ($this->episode) {
            $string .= 'E' . $this->episode;
        }
        return $string;
    }

    public function season(): ?int
    {
        return $this->season;
    }

    public function episode(): ?int
    {
        return $this->episode;
    }

    public function episodes(): ?int
    {
        return $this->episodes;
    }

    /**
     * @return Image[]
     */
    public function images(): array
    {
        return [];
    }
}