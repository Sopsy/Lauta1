<?php
declare(strict_types=1);

namespace TVGuide\Module\Channel\Model;

use TVGuide\Contract\Channel;
use TVGuide\Contract\Identifiable;

final class StoredChannel implements Channel, Identifiable
{
    private $id;
    private $dataId;
    private $name;
    private $urlSafeName;
    private $groupsString;

    public function __construct(int $id, string $dataId, string $name, string $urlSafeName, string $groupsString)
    {
        $this->id = $id;
        $this->dataId = $dataId;
        $this->name = $name;
        $this->urlSafeName = $urlSafeName;
        $this->groupsString = $groupsString;
    }

    public function id(): int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function urlSafeName(): string
    {
        return $this->urlSafeName;
    }

    public function groupsString(): string
    {
        return $this->groupsString;
    }

    public function dataId(): string
    {
        return $this->dataId;
    }
}