<?php
declare(strict_types=1);

namespace TVGuide\Module\Importers\Venetsia\Model;

use TVGuide\Contract\Image;

final class VenetsiaImage implements Image
{
    public function __construct(string $path, string $name)
    {
        $this->name = $name;
        $this->path = $path;
    }

    public function path(): string
    {
        return $this->path . $this->name;
    }
}