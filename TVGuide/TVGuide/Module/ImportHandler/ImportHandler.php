<?php
declare(strict_types=1);

namespace TVGuide\Module\ImportHandler;

use TVGuide\Contract\Channel;
use TVGuide\Contract\Importer;
use TVGuide\Contract\Logger;
use TVGuide\Contract\Program;
use TVGuide\Module\Channel\Repository\ChannelRepository;
use TVGuide\Module\Database\DbConnection;
use TVGuide\Module\Program\Repository\ProgramRepository;

final class ImportHandler
{
    private $importers;
    private $channelRepository;
    private $programRepository;
    private $logger;

    public function __construct(DbConnection $db, Logger $logger, Importer ...$importers)
    {
        $this->logger = $logger;
        $this->importers = $importers;
        $this->channelRepository = new ChannelRepository($db);
        $this->programRepository = new ProgramRepository($db);
    }

    public function import(): void
    {
        $this->logger->info('ImportHandler: Storing channels');

        foreach ($this->importers as $importer) {
            $this->storeChannel($importer->channel());
        }

        $programs = $this->programs();
        $this->logger->info('ImportHandler: ' . count($programs) . ' programs imported');
        $this->logger->info('ImportHandler: Deleting obsolete programs');
        $number = $this->programRepository->deleteObsoletePrograms($this->channelRepository, ...$this->importers);
        $this->logger->info("ImportHandler: {$number} programs deleted");
        $this->logger->info('ImportHandler: Storing programs');
        $this->storePrograms(...$programs);
    }

    public function verify(): bool{
        //TODO: Function which checks that there are programs for all allowed channels today, if not, logs error
    }

    private function storePrograms(Program ...$programs): void
    {
        $this->programRepository->storeMultiple($this->logger, $this->channelRepository, ...$programs);
    }

    private function storeChannel(Channel $channel): void
    {
        if (!$this->channelRepository->exists($channel)) {
            $this->channelRepository->store($channel);
        }
    }

    /**
     * @return Program[]
     */
    private function programs(): array
    {
        $programs = [];
        $this->logger->info('ImportHandler: Importing programs');

        foreach ($this->importers as $importer) {
            $programs[] = $importer->programs();
        }
        if (empty($programs)) {
            return [];
        }

        return array_merge(...$programs);
    }
}