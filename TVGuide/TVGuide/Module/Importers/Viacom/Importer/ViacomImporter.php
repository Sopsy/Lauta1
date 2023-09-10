<?php
declare(strict_types=1);

namespace TVGuide\Module\Importers\Viacom\Importer;

use DateTimeImmutable;
use DateTimeZone;
use SimpleXMLElement;
use TVGuide\Contract\Channel;
use TVGuide\Contract\Importer;
use TVGuide\Module\Importers\Viacom\Model\ViacomChannel;
use TVGuide\Module\Importers\Viacom\Model\ViacomProgram;

final class ViacomImporter implements Importer
{
    private $channel;
    private $xml;

    public function __construct(string $filename, SimpleXMLELement $xml)
    {
        $this->xml = $xml;
        $this->channel = new ViacomChannel((string)$this->xml->channel->attributes()['id']);
    }

    public function programs(): array
    {
        $programs = [];
        foreach ($this->xml->programme as $program) {
            $programs[] = new ViacomProgram($program, $this->channel);
        }

        return $programs;
    }

    public function __toString()
    {
        return 'ViacomImporter';
    }

    public function startTime(): DateTimeImmutable
    {
        $format = 'YmdHis O';
        $date = (string)$this->xml->programme[0]->attributes()['start'];

        return DateTimeImmutable::createFromFormat($format, $date)->setTimezone(new DateTimeZone('UTC'));
    }

    public function endTime(): DateTimeImmutable
    {
        $format = 'YmdHis O';
        $date = (string)$this->xml->programme[count($this->xml->programme) - 1]->attributes()['start'];

        return DateTimeImmutable::createFromFormat($format, $date)->setTimezone(new DateTimeZone('UTC'));
    }

    public function channel(): Channel
    {
        return $this->channel;
    }

    public static function canImport(SimpleXMLElement $xml): bool
    {
        return isset($xml->channel);
    }
}