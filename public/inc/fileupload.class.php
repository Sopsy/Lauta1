<?php

class fileupload
{
    public function typeIsAllowed($file, $extension)
    {
        global $engine;

        if (!array_key_exists($extension, $engine->cfg->allowedFiletypes)) {
            return false;
        }

        $mime = $this->getMimeType($file);
        if (in_array($mime, $engine->cfg->allowedFiletypes[$extension])) {
            return true;
        } else {
            return [$mime, $engine->cfg->allowedFiletypes[$extension]];
        }
    }

    public function getMimeType($file)
    {
        $type = '';
        if (function_exists('finfo_file')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $type = finfo_file($finfo, $file);
            finfo_close($finfo);
        }
        if ($type == '') {
            $type = "application/force-download";
        }

        return $type;
    }

    public function createImage($source, $destination, $maxWidth = 128, $maxHeight = 128, bool $thumbnail = false)
    {
        global $engine;
        set_time_limit(60);
        Imagick::setResourceLimit(Imagick::RESOURCETYPE_MEMORY, $engine->cfg->imagickMemoryLimit);
        Imagick::setResourceLimit(Imagick::RESOURCETYPE_MAP, $engine->cfg->imagickMapLimit);
        Imagick::setResourceLimit(Imagick::RESOURCETYPE_DISK, $engine->cfg->imagickDiskLimit);

        if (is_file($destination)) {
            return false;
        }

        try {
            $image = new Imagick($source);
        } catch (Exception $e) {
            error_log($e);

            return false;
        }

        // Remove additional frames from non-gifs
        if ($image->getNumberImages() > 1) {
            $imageTmp = new Imagick();
            foreach ($image AS $frame) {
                $imageTmp->addImage($frame->getImage());
                break;
            }
            $image->destroy();
            $image = $imageTmp;
        }
        $pageSizes = $image->getImagePage();
        $sizes = $image->getImageGeometry();

        if ($pageSizes['width'] > $maxWidth OR $pageSizes['height'] > $maxHeight) {
            $image->setImagePage(0, 0, 0, 0);
        }

        if ($sizes['width'] > $maxWidth OR $sizes['height'] > $maxHeight) {
            $resize = true;
        } else {
            $resize = false;
        }

        // Resize the image
        if ($resize) {
            try {
                $image->resizeImage($maxWidth, $maxHeight, Imagick::FILTER_TRIANGLE, 1.0, true);
                $image->setImagePage(0, 0, 0, 0);
                $sizes = $image->getImageGeometry();
            } catch (Exception $e) {
                error_log($e);

                return false;
            }
        }

        if ($image->getImageColorspace() == Imagick::COLORSPACE_CMYK) {
            $image->transformImageColorspace(Imagick::COLORSPACE_RGB);
        }

        $image->setSamplingFactors([2, 1, 1]);
        if ($thumbnail) {
            $image->setImageCompressionQuality($engine->cfg->thumbJpgQuality);
        } else {
            $image->setImageCompressionQuality($engine->cfg->jpgQuality);
        }
        $image->stripImage();
        $image->setImageBackgroundColor('white');
        $image->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
        $image->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
        $image->writeImages('jpg:' . $destination, true);
        $image->destroy();

        return is_file($destination);
    }

    function hasVideoStream($source)
    {
        $probe = shell_exec('nice --adjustment=19 ffprobe -show_streams -of json ' . escapeshellarg($source) . ' -v quiet');
        $videoInfo = json_decode($probe, true)['streams'];

        foreach ($videoInfo AS $key => $stream) {
            if ($stream['codec_type'] == 'video') {
                return true;
            }
        }

        return false;
    }

    function jheadAutorot($source)
    {
        // Rotate jpeg by exif tag
        shell_exec('nice --adjustment=19 jhead -autorot ' . escapeshellarg($source));

        if (is_file($source) AND filesize($source) > 0) {
            return true;
        } else {
            return false;
        }
    }

    function jpegtran($source, $progressive = false)
    {
        global $engine;

        $com = $engine->cfg->jpegtranBin . ' -optimize';

        if ($progressive) {
            $com .= ' -progressive';
        }

        if ($engine->cfg->imagickMemoryLimit) {
            $com .= ' -maxmemory ' . $engine->cfg->imagickMemoryLimit / 1024;
        }

        $com .= ' -copy none -outfile ' . escapeshellarg($source) . ' ' . escapeshellarg($source);

        shell_exec('nice --adjustment=19 ' . $com);

        if (is_file($source) AND filesize($source) > 0) {
            return true;
        } else {
            return false;
        }
    }

    function pngcrush($source)
    {
        global $engine;
        shell_exec('nice --adjustment=19 convert -limit area 512MiB -limit memory 128MiB -limit map 256MiB - limit disk 1GiB -limit time 60 '
            . escapeshellarg($source) . '[0] +repage -strip -flatten ' . escapeshellarg('png:' . $source . '.tmp.png'));
        unlink($source);
        shell_exec('nice --adjustment=19 ' . $engine->cfg->pngcrushBin . ' ' . $engine->cfg->pngcrushOptions . ' ' . escapeshellarg($source . '.tmp.png') . ' ' . escapeshellarg($source . '.tmp2.png'));
        unlink($source . '.tmp.png');
        rename($source . '.tmp2.png', $source);

        return (is_file($source) !== false);
    }
}


