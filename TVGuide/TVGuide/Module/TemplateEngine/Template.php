<?php
declare(strict_types=1);

namespace TVGuide\Module\TemplateEngine;

use TVGuide\Contract\Renderable;
use function extract;
use function ob_get_clean;
use const EXTR_OVERWRITE;
use function ob_start;

final class Template implements Renderable
{
    private $template;
    private $view;
    private $title;
    private $data;

    /**
     * Template constructor.
     *
     * @param string $template
     * @param Renderable $view
     * @param string $pageTitle
     * @param array $data
     */
    public function __construct(string $template, Renderable $view, string $pageTitle, array $data = [])
    {
        $this->template = $template;
        $this->view = $view;
        $this->data = $data;
        $this->title = $pageTitle;
    }

    public function render(): string
    {
        extract($this->data, EXTR_OVERWRITE);

        ob_start();
        /** @noinspection PhpIncludeInspection */
        require $this->template;

        return ob_get_clean();
    }

    private function title(): string
    {
        return $this->title;
    }

    private function view(): Renderable
    {
        return $this->view;
    }
}