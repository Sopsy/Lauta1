<?php
declare(strict_types=1);

namespace TVGuide\Module\Importers\Viacom\Model;

use DateTimeImmutable;
use DateTimeZone;
use SimpleXMLElement;
use TVGuide\Contract\Channel;
use TVGuide\Contract\Image;
use TVGuide\Contract\Program;

final class ViacomProgram implements Program
{
    private $channel;
    private $program;

    public function __construct(SimpleXMLElement $program, Channel $channel)
    {
        $this->channel = $channel;
        $this->program = $program;
    }

    public function title(): string
    {
        return (string)$this->program->title;
    }


    public function channel(): Channel
    {
        return $this->channel;
    }

    public function description(): string
    {
        if (isset($this->program->desc)) {
            return (string)$this->program->desc;
        }
        if (isset($this->program->desc_short)) {
            return (string)$this->program->desc_short;
        }
        if (isset($this->program->format_desc)) {
            return (string)$this->program->format_desc;
        }

        return '';
    }

    public function startTime(): DateTimeImmutable
    {
        $format = 'YmdHis O';
        $date = (string)$this->program->attributes()['start'];

        return DateTimeImmutable::createFromFormat($format, $date)->setTimezone(new DateTimeZone('UTC'));
    }

    public function endTime(): DateTimeImmutable
    {
        $format = 'YmdHis O';
        $date = (string)$this->program->attributes()['stop'];

        return DateTimeImmutable::createFromFormat($format, $date)->setTimezone(new DateTimeZone('UTC'));
    }

    public function season(): ?int
    {
        if (isset($this->program->{'season-num'})) {
            return (int)$this->program->{'season-num'};
        }

        return null;
    }

    public function episode(): ?int
    {
        if (isset($this->program->{'episode-num'})) {
            return (int)$this->program->{'episode-num'};
        }

        return null;
    }

    public function episodes(): ?int
    {
        return null;
    }

    /**
     * @return Image[]
     */
    public function images(): array
    {
        # ???
        return [];
    }
}