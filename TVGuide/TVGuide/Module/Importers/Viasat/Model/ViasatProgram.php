<?php
declare(strict_types=1);

namespace TVGuide\Module\Importers\Viasat\Model;

use DateTimeImmutable;
use SimpleXMLElement;
use TVGuide\Contract\Channel;
use TVGuide\Contract\Image;
use TVGuide\Contract\Program;

final class ViasatProgram implements Program
{
    private $channel;
    private $content;
    private $event;

    public function __construct(SimpleXMLElement $event, SimpleXMlElement $content, Channel $channel)
    {
        $this->channel = $channel;
        $this->event = $event;
        $this->content = $content;
    }

    public function title(): string
    {
        foreach ($this->content->titleList->title as $title) {
            if ((string)$title['language'] === 'fin') {
                return (string)$title;
            }
        }
        return (string)$this->content->titleList->title[0];
    }

    public function channel(): Channel
    {
        return $this->channel;
    }

    public function description(): string
    {
        foreach ($this->content->descriptionList->description as $description) {
            if ((string)$description['language'] === 'fin') {
                return (string)$description;
            }
        }
        if (empty($this->content->descriptionList->description->title)) return '';
        return (string)$this->content->descriptionList->description->title[0];
    }

    public function startTime(): DateTimeImmutable
    {
        return new DateTimeImmutable((string)$this->event->timeList->time->startTime);
    }

    public function endTime(): DateTimeImmutable
    {
        return new DateTimeImmutable((string)$this->event->timeList->time->endTime);
    }

    public function season(): ?int
    {
        return null;

    }

    public function episode(): ?int
    {
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
        // from content imagelist image imageref get image(link)
        return [];
    }
}