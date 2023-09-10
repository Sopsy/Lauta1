<?php
declare(strict_types=1);

namespace TVGuide\Module\Importers\Viacom\Model;

use TVGuide\Contract\Channel;
use TVGuide\Library\Text\ToUrlSafe;

final class ViacomChannel implements Channel
{
    private $name;
    private $urlSafeName;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function dataId(): string
    {
        return $this->urlSafeName();
    }

    public function name(): string
    {
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