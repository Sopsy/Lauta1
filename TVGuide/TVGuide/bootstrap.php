<?php
declare(strict_types=1);

use TVGuide\Module\Database\DbConnection;
use TVGuide\Module\Router\Router;
use TVGuide\Module\Router\RouterException;

spl_autoload_register(static function ($className) {
    $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    /**
     * @noinspection PhpIncludeInspection
     * Dynamic include inspection always fails
     */
    require dirname(__DIR__) . '/' . $className . '.php';
});

$cfg = require __DIR__ . '/Config/config.php';
$requestUri = preg_replace('#^' . $cfg['baseUrl'] . '#', '', $_SERVER['REQUEST_URI']);

// Route the request
try {
    $route = (new Router(require __DIR__ . '/Config/routes.php'))->route($requestUri);
} catch (RouterException $e) {
    /**
     * @noinspection ForgottenDebugOutputInspection
     * As intended. FIXME later to proper code.
     */
    error_log($e->getMessage());
    http_response_code(404);
    die('404 Not Found');
}

// Load localization
$locale = 'fi_FI.UTF-8';
$domain = 'default';
setlocale(LC_ALL, $locale);
/** @noinspection UnusedFunctionResultInspection */
bindtextdomain($domain, __DIR__ . '/i18n');
/** @noinspection UnusedFunctionResultInspection */
bind_textdomain_codeset($domain, 'UTF-8');
/** @noinspection UnusedFunctionResultInspection */
textdomain($domain);
$controller = $route->controller();
$method = $route->method();

$db = new DbConnection($cfg['database']['dsn'], $cfg['database']['user'], $cfg['database']['pass'], $cfg['database']['options']);

$controller = new $controller($db, $cfg);
$output = $controller->$method(...$route->params());

echo $output->render();