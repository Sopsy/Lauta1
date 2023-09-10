<?php
declare(strict_types=1);

use TVGuide\Contract\Logger;
use TVGuide\Module\Database\DbConnection;
use TVGuide\Module\ImportHandler\ImportHandler;
use TVGuide\Module\Sources\DefaultSource;
use TVGuide\Module\SourceParser\SourceParser;
use TVGuide\Module\Logger\Log;
use TVGuide\Module\Sources\ViacomSource;
use TVGuide\Module\Sources\ViasatSource;

if (!isset($argv[1])) {
    die("Usage: php cli.php command [options]
--quiet, -q       - No output except for errors
update: Update data
  --nodelete, -d    - Don't delete local source files after import\n
prune: Remove past programs from database
  --days            - Number of days of past programs to keep, defaults to 30\n");
}

libxml_use_internal_errors(true);

$time_start = microtime(true);
ini_set('memory_limit', '1024M');

spl_autoload_register(static function ($className) {
    $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);

    /**
     * @noinspection PhpIncludeInspection
     * Dynamic include inspection always fails
     */
    require dirname(__DIR__) . '/' . $className . '.php';
});

$logger = new Log(in_array('--quiet', $argv, true) || in_array('-q', $argv, true));
switch ($argv[1]) {
    case 'update':
        update($argv, $logger);
        break;
    case 'prune':
        prune($argv, $logger);
        break;
    default:
        echo "Invalid command: {$argv[1]}\n";
        die();
}
$time_end = microtime(true);
$logger->info(round($time_end - $time_start, 2) . "s time spent\n");

function update(array $argv, Logger $logger): void
{
    $deleteAfter = !(in_array('--nodelete', $argv, true) || in_array('-d', $argv, true));
    $cfg = require __DIR__ . '/Config/config.php';

    $logger->info('Update started');
    try {
        $db = new DbConnection($cfg['database']['dsn'], $cfg['database']['user'], $cfg['database']['pass'], $cfg['database']['options']);
        $viacom = new ViacomSource($cfg['viacom']['url']);
        $viasat = new ViasatSource($cfg['viasat']['url'], $cfg['viasat']['version'], $cfg['viasat']['key']);
        $localXmlSources = new DefaultSource($cfg['localXmlSources']);
        $remoteXmlSources = new DefaultSource($cfg['remoteXmlSources']);
        $importers = (new SourceParser($logger, $viacom, $viasat, $localXmlSources, $remoteXmlSources))->importers();
        (new ImportHandler($db, $logger, ...$importers))->import();
    } catch (Exception $e) {
        $logger->error("TV Data update failed: {$e->getMessage()}\n");

        return;
    }

    if ($deleteAfter) {
        $localXmlSources->deleteSourceFiles();
    }
}

function prune(array $argv, Logger $logger): void
{
    $logger->error("Method not implemented\n");
}