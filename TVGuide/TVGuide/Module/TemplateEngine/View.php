<?php
declare(strict_types=1);

namespace TVGuide\Module\TemplateEngine;

use TVGuide\Contract\Renderable;
use TVGuide\Module\TemplateEngine\Exception\TemplateEngineException;
use function extract;
use function is_file;
use function ob_get_clean;
use function ob_start;
use function substr;
use const EXTR_OVERWRITE;

final class View implements Renderable
{
    private $viewFile;
    private $data;

    /**
     * View constructor.
     *
     * @param string $viewFile
     * @throws TemplateEngineException when view file is invalid or not found
     */
    public function __construct(string $viewFile, array $data = [])
    {
        if (!is_file($viewFile)) {
            throw new TemplateEngineException("View file not found: {$viewFile}");
        }

        if (substr($viewFile, -6) !== '.phtml') {
            throw new TemplateEngineException("Invalid view file, does not end in .phtml: {$viewFile}");
        }

        $this->viewFile = $viewFile;
        $this->data = $data;
    }

    public function render(): string
    {
        extract($this->data, EXTR_OVERWRITE);

        ob_start();
        /**
         * @noinspection PhpIncludeInspection
         * File is validated in constructor
         */
        require $this->viewFile;

        return ob_get_clean();
    }
}