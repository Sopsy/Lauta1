<?php
declare(strict_types=1);

namespace TVGuide\Module\Channel\Model;

use TVGuide\Contract\Channel;
use TVGuide\Contract\Identifiable;

final class ChannelGroup
{
    private $id;
    private $name;
    private $channels;

    public function __construct(int $id, string $name, Channel ...$channels){
        $this->id = $id;
        $this->name = $name;
        $this->channels = $channels;
    }

    public function id(): int
    {
    return $this->id;
    }

    public function channels(): array
    {
        return $this->channels;
    }

    public function name(): string
    {
        return $this->name;
    }
}