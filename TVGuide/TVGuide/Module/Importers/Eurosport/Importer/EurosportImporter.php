<?php
declare(strict_types=1);

namespace TVGuide\Module\Importers\Eurosport\Importer;

use DateTimeImmutable;
use SimpleXMLElement;
use TVGuide\Contract\Channel;
use TVGuide\Module\Importers\Eurosport\Model\EurosportProgram;
use TVGuide\Contract\Importer;
use TVGuide\Module\Importers\Eurosport\Model\EurosportChannel;

final class EurosportImporter implements Importer
{
    private $xml;
    private $channel;

    public function __construct(string $filename, SimpleXMLELement $xml)
    {
        $this->xml = $xml;
        $this->channel = new EurosportChannel($filename);
    }

    public function programs(): array
    {
        $programs = [];
        foreach ($this->xml->BroadcastDate_GMT as $programTableXML) {
            $date = (string)$programTableXML->attributes()['Day'];
            foreach ($programTableXML->Emission as $programXML) {
                $programs[] = new EurosportProgram($programXML, $this->channel, $date);
            }
        }
        return $programs;
    }

    public function __toString()
    {
        return 'EurosportImporter';
    }

    public function startTime(): DateTimeImmutable
    {
        $format = 'd/m/Y H:i';
        $date = (string)$this->xml->BroadcastDate_GMT[0]->attributes()['Day'];
        return DateTimeImmutable::createFromFormat($format, $date . ' ' . '00:00');
    }

    public function endTime(): DateTimeImmutable
    {
        $format = 'd/m/Y H:i';
        $date = (string)$this->xml->BroadcastDate_GMT[count($this->xml) - 1]->attributes()['Day'];
        return DateTimeImmutable::createFromFormat($format, $date . ' ' . '23:59');
    }

    public function channel(): Channel
    {
        return $this->channel;
    }

    public static function canImport(SimpleXMLElement $xml): bool
    {
        return isset($xml->BroadcastDate_GMT);
    }
}