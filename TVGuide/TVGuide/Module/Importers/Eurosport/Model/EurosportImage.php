<?php
declare(strict_types=1);

namespace TVGuide\Module\Importers\Venetsia\Model;

use TVGuide\Contract\Image;

final class EurosportImage implements Image
{

    public function __construct(string $url)
    {
        $this->path = $url;
    }

    public function path(): string
    {
        return $this->path;
    }
}