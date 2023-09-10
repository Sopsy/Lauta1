<?php
declare(strict_types=1);

namespace TVGuide\Module\ImageProcessor;

final class ProcessedImage
{
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function name(): string
    {
        return $this->name;
    }
}