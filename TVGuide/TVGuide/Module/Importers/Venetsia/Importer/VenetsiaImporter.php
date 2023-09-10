<?php
declare(strict_types=1);

namespace TVGuide\Module\Importers\Venetsia\Importer;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use SimpleXMLElement;
use TVGuide\Contract\Channel;
use TVGuide\Module\Importers\Venetsia\Model\VenetsiaChannel;
use TVGuide\Module\Importers\Venetsia\Model\VenetsiaProgram;
use TVGuide\Contract\Importer;

final class VenetsiaImporter implements Importer
{
    private $xml;

    public function __construct(string $filename, SimpleXMLElement $xml)
    {
        $this->xml = $xml->ProgramTable;
    }

    /**
     * @return Programs[]
     */
    public function programs(): array
    {
        $programs = [];
        foreach ($this->xml->ProgramItem as $programXML) {
            $programs[] = new VenetsiaProgram($programXML, $this->channel());
        }
        return $programs;
    }

    public function channel(): Channel
    {
        return new VenetsiaChannel($this->xml->ProgramTableInformation->Station);
    }

    /**
     * @param string $time
     * @return DateTimeImmutable
     * @throws \Exception
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

    public function startTime(): DateTimeImmutable
    {
        return $this->fixedDateTime((string)$this->xml->ProgramTableInformation->StartDate);
    }

    public function endTime(): DateTimeImmutable
    {
        $endDate = $this->fixedDateTime((string)$this->xml->ProgramTableInformation->EndDate);
        // If end is before start, forget about the date and just try to use the hours.
        // Yup. This happens a lot. Believe it or not.
        if ($endDate < $this->startTime()) {
            $endHour = $endDate->format('H:i:s');
            $endDate = $this->startTime()->add(new DateInterval('P1D'))->format('Y-m-d');
            $endDate = $this->fixedDateTime("{$endDate}T{$endHour}+02:00");
        }
        return $endDate;
    }

    public function __toString()
    {
        return 'VenetsiaImporter';
    }

    public static function canImport(SimpleXMLElement $xml): bool
    {
        return isset($xml->ProgramTable);
    }
}