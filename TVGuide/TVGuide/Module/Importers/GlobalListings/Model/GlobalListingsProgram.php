<?php
declare(strict_types=1);

namespace TVGuide\Module\Importers\GlobalListings\Model;

use DateTimeImmutable;
use function preg_match;
use SimpleXMLElement;
use function trim;
use TVGuide\Contract\Channel;
use TVGuide\Contract\Image;
use TVGuide\Contract\Program;
use TVGuide\Module\DataImporter\Exception\DataException;

final class GlobalListingsProgram implements Program
{
    private $programXML;
    private $channel;

    public function __construct(SimpleXMLElement $programXML, Channel $channel)
    {
        $this->programXML = $programXML;
        $this->channel = $channel;
    }

    public function title(): string
    {
        $title = $this->programXML->BROADCAST_TITLE;

        if ($title === false || !isset($title)) {
            return '';
        }

        preg_match('#^([^(]*)#', (string)$title[0], $matches);

        if (empty($matches)) {
            return '';
        }

        return trim($matches[0]);
    }

    public function channel(): Channel
    {
        return $this->channel;
    }

    public function description(): string
    {
        $description = $this->programXML->TEXT_TEXT;

        if ($description === false || !isset($description[0])) {
            return '';
        }

        return trim((string)$description[0]);
    }

    public function startTime(): DateTimeImmutable
    {
        $time = (string)$this->programXML->BROADCAST_START_DATETIME;
        if ($time === false || !isset($time[0])) {
            throw new DataException('Start time for program not found');
        }

        return new DateTimeImmutable($time);
    }

    public function endTime(): DateTimeImmutable
    {
        $time = (string)$this->programXML->BROADCAST_END_TIME;

        if ($time === false || !isset($time[0])) {
            throw new DataException('Start time for program not found');
        }

        return new DateTimeImmutable($time);
    }

    public function season(): ?int
    {
        return (int)$this->programXML->SERIES_NUMBER;
    }

    public function episode(): ?int
    {
        return (int)$this->programXML->EPISODE_NUMBER;
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
        return [];
    }
}