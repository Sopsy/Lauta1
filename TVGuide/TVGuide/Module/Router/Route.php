<?php
declare(strict_types=1);

namespace TVGuide\Module\Router;

final class Route
{
    private $controller;
    private $method;
    private $requestUri;
    private $params;

    public function __construct(array $route, string $requestUri, array $params)
    {
        $this->controller = $route[0];
        $this->method = $route[1];
        $this->requestUri = $requestUri;
        $this->params = $params;
    }

    public function controller(): string
    {
        return $this->controller;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function requestUri(): string
    {
        return $this->requestUri;
    }

    public function params(): array
    {
        return $this->params;
    }
}