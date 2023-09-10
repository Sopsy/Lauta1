<?php
declare(strict_types=1);

namespace TVGuide\Module\SourceParser;

use SimpleXMLElement;
use TVGuide\Contract\Importer;
use TVGuide\Contract\Logger;
use TVGuide\Contract\Source;
use TVGuide\Module\Importers\Eurosport\Importer\EurosportImporter;
use TVGuide\Module\Importers\GlobalListings\Importer\GlobalListingsImporter;
use TVGuide\Module\Importers\Invalid\Importer\InvalidImporter;
use TVGuide\Module\Importers\Venetsia\Importer\VenetsiaImporter;
use TVGuide\Module\Importers\Viacom\Importer\ViacomImporter;
use TVGuide\Module\Importers\Viasat\Importer\ViasatImporter;

final class SourceParser
{
    private $logger;
    private $xmls = [];
    private $urls = [];

    public function __construct(Logger $logger, Source ...$sources)
    {
        $this->logger = $logger;

        foreach ($sources as $source) {
            foreach ($source->sources() as $sourceFile) {
                /** This is never used for anything but importer so the error does not matter.
                 * @noinspection BypassedUrlValidationInspection
                 */
                if (filter_var($sourceFile, FILTER_VALIDATE_URL)) {
                    $this->addUrl($sourceFile);
                } else {
                    $this->addXML($sourceFile);
                }
            }
        }

        $this->fetchUrls();
    }

    private function fetchUrls(): void
    {
        $this->logger->info('SourceParser: Fetching remote sources');
        foreach (array_chunk($this->urls, 20) as $urls) {
            $xmls = [];
            $requests = [];
            $multi = curl_multi_init();
            foreach ($urls as $url) {
                $request = curl_init();
                curl_setopt($request, CURLOPT_URL, $url);
                curl_setopt($request, CURLOPT_HEADER, 0);
                curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
                curl_multi_add_handle($multi, $request);
                $requests[$url] = $request;
            }
            $stillRunning = false;

            do {
                curl_multi_exec($multi, $stillRunning);
            } while ($stillRunning);

            foreach ($requests as $url => $request) {
                $this->logger->info("SourceParser: Loading file {$url}");
                $xmls[$url] = simplexml_load_string(curl_multi_getcontent($request));
                curl_multi_remove_handle($multi, $request);
            }

            curl_multi_close($multi);
            $this->xmls = array_merge($this->xmls, $xmls);
            usleep(100000);
        }

    }

    private function addUrl($filename): void
    {
        $this->urls[] = $filename;
    }

    private function addXML($filename): void
    {
        $this->logger->info("SourceParser: Loading file {$filename}");
        $this->xmls[$filename] = simplexml_load_string(file_get_contents($filename));
    }

    public function importers(): array
    {
        $importers = [];
        foreach ($this->xmls as $filename => $xml) {
            if (!$xml) {
                continue;
            }
            $importerClasses = $this->getImporters();
            $importerFound = false;
            foreach ($importerClasses as $importer) {
                if ($importer::canImport($xml)) {
                    $importerFound = true;
                    if ($importer === InvalidImporter::class){
                        continue;
                    }
                    $importers[] = new $importer($filename, $xml);
                }
            }
            if (!$importerFound) {
                $this->logger->error("SourceParser: Couldn't find a suitable importer for {$filename}");
            }
        }

        return $importers;
    }

    private function getImporters(): array
    {
        return [
            GlobalListingsImporter::class,
            VenetsiaImporter::class,
            EurosportImporter::class,
            ViasatImporter::class,
            ViacomImporter::class,
            InvalidImporter::class
        ];
    }
}
