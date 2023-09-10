<?php
declare(strict_types=1);

namespace TVGuide\Module\ImageProcessor;

use function file_get_contents;
use Imagick;
use ImagickException;
use TVGuide\Contract\Image;

final class ImageProcessor
{
    private $imageDirectory;
    private $imageMaxSize;
    private $jpegQuality;

    public function __construct(string $imageDirectory, string $imageMaxSize, string $jpegQuality)
    {
        $this->imageDirectory = $imageDirectory;
        $this->imageMaxSize = $imageMaxSize;
        $this->jpegQuality = $jpegQuality;
    }

    public function process(Image $image): ProcessedImage
    {
        try {
            $image = new Imagick();
            $image->readImageBlob($image->path);
        } catch (ImagickException $e) {
            throw new ImageException('Imagick failed to open the image: ' . $e->getMessage(), 0, $e);
        }
        $name = md5($image->getImageSignature());
        ['width' => $width, 'height' => $height] = $image->getImageGeometry();
        if ($width > $height) {
            $newWidth = $this->imageMaxSize;
            $newHeight = (int)(($height / $width) * $this->imageMaxSize);
        } else {
            $newHeight = $this->imageMaxSize;
            $newWidth = (int)(($width / $height) * $this->imageMaxSize);
        }
        Log::info('ImageProcessor: Converting ' . $image->path . ' (' . $width . ', ' . $height . ') to ' . $name . '.jpg (' . $newWidth . ', ' . $newHeight . ')');
        $image->resizeImage($newWidth, $newHeight, imagick::FILTER_TRIANGLE, 1);
        $image->setImagePage($newWidth, $newHeight, 0, 0);
        $image->setImageFormat('jpeg');
        $image->setImageCompressionQuality($this->jpegQuality);
        $image->stripImage();
        $image->setImageBackgroundColor('white');
        try {
            $image->flattenImages();
        } catch (ImagickException $e) {
            throw new ImageException('Imagick failed to flatten the image', 0, $e);
        }
        return new ProcessedImage($name);
    }
}