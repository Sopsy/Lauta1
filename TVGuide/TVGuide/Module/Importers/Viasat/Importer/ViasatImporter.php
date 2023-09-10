<?php
declare(strict_types=1);

namespace TVGuide\Module\Importers\Viasat\Importer;

use DateTimeImmutable;
use SimpleXMLElement;
use TVGuide\Contract\Channel;
use TVGuide\Contract\Importer;
use TVGuide\Module\Importers\Viasat\Model\ViasatChannel;
use TVGuide\Module\Importers\Viasat\Model\ViasatProgram;

final class ViasatImporter implements Importer
{
    private $channel;
    private $xml;

    public function __construct(string $filename, SimpleXMLELement $xml)
    {
        $this->xml = $xml;
        $this->channel = new ViasatChannel((string)$this->xml->eventList->event[0]->channelId);
    }

    public function programs(): array
    {
        $programs = [];
        foreach ($this->xml->eventList->event as $event) {
            foreach ($this->xml->contentList->content as $content) {
                if ((string)$event->contentIdRef === (string)$content->contentId) {
                    $programs[] = new ViasatProgram($event, $content, $this->channel);
                }
            }
        }
        return $programs;
    }

    public function __toString()
    {
        return 'ViasatImporter';
    }

    public function startTime(): DateTimeImmutable
    {
        return new DateTimeImmutable((string)$this->xml->from);
    }

    public function endTime(): DateTimeImmutable
    {
        return new DateTimeImmutable((string)$this->xml->to);
    }

    public function channel(): Channel
    {
        return $this->channel;
    }

    public static function canImport(SimpleXMLElement $xml): bool
    {
        return isset($xml->providerId);
    }
}