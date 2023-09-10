<?php
declare(strict_types=1);

namespace TVGuide\Module\Media\Model;

use TVGuide\Contract\Identifiable;
use TVGuide\Contract\Image as ImageInterface;
use function abs;
use function getimagesize;

final class Image implements ImageInterface, Identifiable
{
    private $id;
    private $filename;
    private $basePath;
    private $width;
    private $height;

    public function __construct(int $id, string $filename)
    {
        $this->id = $id;
        $this->filename = $filename;
        $this->basePath = $basePath;
    }

    public function id(): int
    {
        return $this->id;
    }

    public function filename(): string
    {
        return $this->filename[0] . '/' . $this->filename . '.jpg';
    }

    public function fullPath(): string
    {
        return $this->basePath . '/' . $this->filename();
    }

    public function width(): int
    {
        $this->loadImageSize();

        return $this->width;
    }

    public function height(): int
    {
        $this->loadImageSize();

        return $this->height;
    }

    public function weight(): float
    {
        $this->loadImageSize();

        return abs($this->width / $this->height - 16 / 9);
    }

    private function loadImageSize(): void
    {
        if ($this->width !== null) {
            return;
        }

        $size = getimagesize($this->fullPath());
        $this->width = $size[0];
        $this->height = $size[1];
    }
}