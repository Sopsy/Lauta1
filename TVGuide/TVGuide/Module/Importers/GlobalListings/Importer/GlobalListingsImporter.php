<?php
declare(strict_types=1);

namespace TVGuide\Module\Importers\GlobalListings\Importer;

use DateTimeImmutable;
use SimpleXMLElement;
use TVGuide\Contract\Channel;
use TVGuide\Module\Importers\GlobalListings\Model\GlobalListingsProgram;
use TVGuide\Contract\Importer;
use TVGuide\Module\Importers\GlobalListings\Model\GlobalListingsChannel;

final class GlobalListingsImporter implements Importer
{
    private $xml;
    private $channel;

    public function __construct(string $filename, SimpleXMLElement $xml)
    {
        $this->xml = $xml;
        $this->channel = new GlobalListingsChannel($xml);
    }

    public function programs(): array
    {
        $programs = [];
        foreach ($this->xml->BROADCAST as $programXML) {
            $programs[] = new GlobalListingsProgram($programXML, $this->channel);
        }
        return $programs;
    }

    public function startTime(): DateTimeImmutable
    {
        // Find out timezone
        // Format: 2019-09-01T07:00
        return new DateTimeImmutable((string)$this->xml->BROADCAST[0]->BROADCAST_START_DATETIME);
    }

    public function endTime(): DateTimeImmutable
    {
        return new DateTimeImmutable((string)$this->xml->BROADCAST[count($this->xml) - 1]->BROADCAST_END_TIME);
    }

    public function channel(): Channel
    {
        return $this->channel;
    }

    public function __toString()
    {
        return 'GlobalListingsImporter';
    }

    public static function canImport(SimpleXMLElement $xml): bool
    {
        return isset($xml->BROADCAST);
    }
}