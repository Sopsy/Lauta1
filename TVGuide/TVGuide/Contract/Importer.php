<?php
declare(strict_types=1);

namespace TVGuide\Contract;

use DateTimeImmutable;
use SimpleXMLElement;

interface Importer
{
    public function __construct(string $tag, SimpleXMLElement $xml);

    /**
     * @return Program[]
     */
    public function programs(): array;

    public function startTime(): DateTimeImmutable;

    public function endTime(): DateTimeImmutable;

    public function channel(): Channel;

    public static function canImport(SimpleXMLElement $xml): bool;
}