<?php
declare(strict_types=1);

namespace TVGuide\Module\Importers\Venetsia\Model;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use function explode;
use function preg_match;
use SimpleXMLElement;
use function strtolower;
use function trim;
use TVGuide\Contract\Channel;
use TVGuide\Contract\Program;
use TVGuide\Module\DataImporter\Exception\DataException;

final class VenetsiaProgram implements Program
{
    private $programXML;
    private $season;
    private $episode;
    private $episodes;
    private $channel;

    public function __construct(SimpleXMLElement $programXML, Channel $channel)
    {
        $this->programXML = $programXML->ProgramInformation->{'tva.ProgramDescription'}->children('tva', true);
        $this->channel = $channel;
        $this->parseEpisode();
    }

    private function parseEpisode(): void
    {
        $season = 0;
        $episode = 0;
        $episodes = 0;
        $description = strtolower($this->description());

        preg_match('#(\d+)\/(\d+)[., ]?#i', $description, $matches);
        if (!empty($matches)) {
            $episode = (int)$matches[1];
            $episodes = (int)$matches[2];
        }

        preg_match('#(?>osa|jakso) (\d+)#i', $description, $matches);
        if (!empty($matches)) {
            $episode = (int)$matches[1];
        }

        preg_match('#(\d+)[., ]? kausi#i', $description, $matches);
        if (!empty($matches)) {
            $season = (int)$matches[1];
        }

        preg_match('#(?>jakso |osa )(\d+)(?>\/(\d+)[., ]?)#i', $description, $matches);
        if (!empty($matches)) {
            $episode = (int)$matches[1];
            $episodes = (int)$matches[2];
        }

        preg_match('#kausi (\d+)[,.] (?>jakso |osa )?(\d+)(?>\/(\d+))?[., ]?#i', $description, $matches);
        if (!empty($matches)) {
            $season = (int)$matches[1];
            $episode = (int)$matches[2];
            if(!empty($matches[3])) {
                $episodes = (int)$matches[3];
            }
        }

        $this->season = $season;
        $this->episode = $episode;
        $this->episodes = $episodes;
    }

    public function title(): string
    {
        $title = $this->programXML->ProgramInformationTable->ProgramInformation->BasicDescription->Title;

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
        $description = $this->programXML->ProgramInformationTable->ProgramInformation->BasicDescription->Synopsis;

        if ($description === false || !isset($description[0])) {
            return '';
        }

        return trim((string)$description[0]);
    }

    public function startTime(): DateTimeImmutable
    {
        $time = $this->programXML->ProgramLocationTable->BroadcastEvent->PublishedStartTime;

        if ($time === false || !isset($time[0])) {
            throw new DataException('Start time for program not found');
        }

        try {
            return $this->fixedDateTime((string)$time);
        } catch (Exception $e) {
            throw new DataException('Invalid StartTime for program', 0, $e);
        }
    }

    public function endTime(): DateTimeImmutable
    {
        $time = $this->programXML->ProgramLocationTable->BroadcastEvent->PublishedEndTime;

        if ($time === false || !isset($time[0])) {
            throw new DataException('Start time for program not found');
        }
        try {
            $time = $this->fixedDateTime((string)$time);
            // If end is before start, forget about the date and just try to use the hours.
            // Yup. This happens a lot. Believe it or not.
            if ($time < $this->startTime()) {
                $endHour = $time->format('H:i:s');
                $endDate = $this->startTime()->format('Y-m-d');
                return $this->fixedDateTime("{$endDate}T{$endHour}+02:00");
            }
            return $time;
        } catch (Exception $e) {
            throw new DataException('Invalid StartTime for program', 0, $e);
        }
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

    public function images(): array
    {
        return [];
    }

    /**
     * @param string $time
     * @return DateTimeImmutable
     * @throws Exception
     *
     * Venetsia data timestamps have a wrong timezone during DST (+0200 instead of +0300), so we ignore that and
     * change it to the proper for Europe/Helsinki.
     */
    private function fixedDateTime(string $time): DateTimeImmutable
    {
        $time = explode('+', $time, 2)[0];
        $finnishZone = new DateTimeZone('Europe/Helsinki');
        $UTC = new DateTimeZone('UTC');

        return (new DateTimeImmutable($time, $finnishZone))->setTimezone($UTC);
    }
}