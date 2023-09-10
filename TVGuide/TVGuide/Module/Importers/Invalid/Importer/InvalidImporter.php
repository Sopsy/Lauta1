<?php
declare(strict_types=1);

namespace TVGuide\Module\Importers\Invalid\Importer;

use DateTimeImmutable;
use SimpleXMLElement;
use TVGuide\Contract\Channel;
use TVGuide\Contract\Importer;

final class InvalidImporter implements Importer
{

    public function __construct(string $filename, SimpleXMLELement $xml)
    {

    }

    public function programs(): array
    {
        return [];
    }

    public function __toString()
    {
        return 'InvalidImporter';
    }

    public function startTime(): DateTimeImmutable
    {
        return null;
    }

    public function endTime(): DateTimeImmutable
    {
        return null;
    }

    public function channel(): Channel
    {
        return null;
    }

    public static function canImport(SimpleXMLElement $xml): bool
    {
        return isset($xml->status);
    }
}