<?php
declare(strict_types=1);

namespace TVGuide\Module\Importers\GlobalListings\Model;

use SimpleXMLElement;
use TVGuide\Contract\Channel;
use TVGuide\Library\Text\ToUrlSafe;

final class GlobalListingsChannel implements Channel
{
    /** @var string */
    private $id;
    /** @var string */
    private $urlSafeName;

    public function __construct(SimpleXMLElement $xml)
    {
        $this->id = (string)$xml->attributes()['CHANNEL_ID'];
    }

    public function dataId(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        $names = [
            'HIS.SD.Fin' => 'The History Channel',
            'H2.EU.Fin' => 'History 2',
            'APEUFIN' => 'Animal Planet',
            'DCFIFIN' => 'Discovery'
        ];
        if (!array_key_exists($this->id, $names)) {
            return '';
        }
        return $names[$this->id];
    }

    public function urlSafeName(): string
    {
        if ($this->urlSafeName !== null) {
            return $this->urlSafeName;
        }

        $this->urlSafeName = (new ToUrlSafe($this->name()))->string();

        return $this->urlSafeName;
    }
}