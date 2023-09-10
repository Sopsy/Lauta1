<?php
declare(strict_types=1);

namespace TVGuide\Module\Importers\Venetsia\Model;

use SimpleXMLElement;
use TVGuide\Library\Text\ToUrlSafe;
use function trim;
use TVGuide\Contract\Channel;
use TVGuide\Module\DataImporter\Exception\DataException;

final class VenetsiaChannel implements Channel
{
    /** @var SimpleXMLElement */
    private $channelXML;
    /** @var string */
    private $urlSafeName;

    public function __construct(SimpleXMLELement $channelXML)
    {
        $this->channelXML = $channelXML;
    }

    public function dataId(): string
    {
        if (!isset($this->channelXML)) {
            throw new DataException('Station element not found in ProgramTableInformation.');
        }
        $attributes = $this->channelXML->attributes();
        if (empty($attributes['serviceId'])) {
            throw new DataException('Can\'t find ServiceId for channel: serviceId-attribute not found in Station.');
        }

        return trim((string)$attributes['serviceId']);
    }

    public function name(): string
    {
        $channelName = $this->channelXML->xpath('.//tva:Name');
        if ($channelName === false || !isset($channelName[0])) {
            throw new DataException('Channel name not found');
        }

        return trim((string)$channelName[0]);
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