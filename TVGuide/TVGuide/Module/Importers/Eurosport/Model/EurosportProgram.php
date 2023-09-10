<?php
declare(strict_types=1);

namespace TVGuide\Module\Importers\Eurosport\Model;

use DateTimeImmutable;
use function preg_match;
use SimpleXMLElement;
use function trim;
use TVGuide\Contract\Channel;
use TVGuide\Contract\Image;
use TVGuide\Contract\Program;
use TVGuide\Module\DataImporter\Exception\DataException;

final class EurosportProgram implements Program
{

    private $programXML;
    private $channel;
    private $date;

    public function __construct(SimpleXMLElement $programXML, Channel $channel, string $date)
    {
        $this->programXML = $programXML;
        $this->channel = $channel;
        $this->date = $date;
    }

    public function title(): string
    {
        $title = $this->programXML->Title;

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
        $description = $this->programXML->Feature;

        if ($description === false || !isset($description[0])) {
            return '';
        }

        return trim((string)$description[0]);
    }

    public function startTime(): DateTimeImmutable
    {
        $time = $this->programXML->StartTimeGMT;
        if ($time === false || !isset($time[0])) {
            throw new DataException('Start time for program not found');
        }

        return $this->getDate($time);
    }

    public function endTime(): DateTimeImmutable
    {
        $time = $this->programXML->EndTimeGMT;

        if ($time === false || !isset($time[0])) {
            throw new DataException('Start time for program not found');
        }

        return $this->getDate($time);
    }

    private function getDate($time)
    {
        $format = 'd/m/Y H:i';
        return DateTimeImmutable::createFromFormat($format, $this->date . ' ' . $time);
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
        return [new EurosportImage($this->programXML->ImageHD)];
    }
}