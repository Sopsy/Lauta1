<?php
declare(strict_types=1);

namespace TVGuide\Module\Importers\Viasat\Model;

use TVGuide\Contract\Channel;
use TVGuide\Library\Text\ToUrlSafe;

final class ViasatChannel implements Channel
{
    /** @var string */
    private $name;
    /** @var string */
    private $dataId;
    /** @var string */
    private $urlSafeName;

    public function __construct(string $dataId)
    {
        $this->dataId = $dataId;
    }

    public function dataId(): string
    {
        return $this->dataId;
    }

    public function name(): string
    {
        if ($this->name !== null) {
            return $this->name;
        }

        $words = explode('.', $this->dataId());
        $this->name = ucwords(implode(' ', array_slice($words, 1)));

        return $this->name;
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