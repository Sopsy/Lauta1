<?php
declare(strict_types=1);

namespace TVGuide\Module\Program\Repository;

use TVGuide\Contract\Importer;
use TVGuide\Contract\Logger;
use TVGuide\Module\Channel\Repository\ChannelRepository;
use function array_fill;
use function count;
use DateTimeImmutable;
use Exception;
use function implode;
use TVGuide\Contract\Channel;
use TVGuide\Contract\Program;
use TVGuide\Contract\Identifiable;
use TVGuide\Module\Channel\Model\StoredChannel;
use TVGuide\Module\Database\DbConnection;
use TVGuide\Module\Program\Model\StoredProgram;
use TVGuide\Module\Program\Model\ProgramList;

final class ProgramRepository
{
    private $db;

    public function __construct(DbConnection $db)
    {
        $this->db = $db;
    }

    public function storeMultiple(Logger $logger, ChannelRepository $channelRepository, Program ...$allPrograms): void
    {
        // This is significantly more efficient than adding every program one by one
        // But we have to chunk the array because of the MySQL prepare value limit of 65535
        // If you add more values, remember to change chunk size accordingly
        // Chunk size is counted by dividing 65536 by number of values -1
        foreach (array_chunk($allPrograms, 8191) as $programs) {
            /** @var $programs Program[] */
            $logger->info('ProgramRepository: Storing a chunk of ' . count($programs) . ' programs');
            $template = array_fill(0, count($programs), '(?,?,?,?,?,?,?,?)');
            $template = implode(',', $template);
            $query = $this->db->prepare('INSERT IGNORE INTO program (title, channel_id, description, start_time, end_time, season, episode, episodes) VALUES' . $template);
            $values = [];
            foreach ($programs as $program) {
                $values[] = $program->title();
                $values[] = $channelRepository->getStored($program->channel())->id();
                $values[] = $program->description();
                $values[] = $program->startTime()->format('Y-m-d H:i:s');
                $values[] = $program->endTime()->format('Y-m-d H:i:s');
                $values[] = $program->season();
                $values[] = $program->episode();
                $values[] = $program->episodes();
            }
            $query->execute($values);
        }
    }

    public function addProgramImages(StoredProgram $program, Identifiable ...$images): void
    {
        foreach ($images as $image) {
            $this->addProgramImage($program, $image);
        }
    }

    public function addProgramImage(StoredProgram $program, Identifiable $image): void
    {
        $query = $this->db->prepare('INSERT IGNORE INTO program_image(program_id, image_id) VALUES (:program_id, :image_id)');
        $query->bindValue(':program_id', $program->id());
        $query->bindValue(':image_id', $image->id());
        $query->execute();
    }

    public function deleteObsoletePrograms(ChannelRepository $channelRepository, Importer ...$importers): int
    {
        if (empty($importers)) {
            return 0;
        }
        $template = array_fill(0, count($importers), '(channel_id = ? AND start_time >= ? AND start_time <= ?)');
        $conditions = implode(' OR ', $template);
        $query = $this->db->prepare('DELETE FROM program WHERE' . $conditions);
        $values = [];
        foreach ($importers as $importer) {
            $values[] = $channelRepository->getStored($importer->channel())->id();
            $values[] = $importer->startTime()->format('Y-m-d H:i:s');
            $values[] = $importer->endTime()->format('Y-m-d H:i:s');
        }
        $query->execute($values);

        return $query->rowCount();
    }

    /**
     * @param Channel $channel
     * @param string $date
     * @return ProgramList
     * @throws Exception
     */
    public function getProgramListsByDate(string $date): array
    {
        $query = $this->db->prepare('
            SELECT *, c.id, (SELECT GROUP_CONCAT(group_id) FROM group_channel WHERE channel_id = d.id) AS groupsString
            FROM
                (SELECT *, RANK() OVER (PARTITION BY channel_id ORDER BY start_time DESC) AS rnk
                FROM program
                WHERE start_time >= ? AND start_time < ?) c
                    LEFT JOIN channel d ON c.channel_id = d.id
            WHERE allowed AND NOT radio
            ORDER BY `order`,start_time
        ');
        $query->execute(["{$date} 00:00:00", "{$date} 23:59:59"]);

        return $this->mapResults($query);
    }

    public function getProgramLists(): array
    {
        $query = $this->db->query('
            SELECT *, c.id, (SELECT GROUP_CONCAT(group_id) FROM group_channel WHERE channel_id = d.id) AS groupsString
            FROM (
                SELECT *
                FROM (
                     SELECT *, RANK() OVER (PARTITION BY channel_id ORDER BY start_time DESC) AS rnk
                         FROM program
                         WHERE start_time < NOW() AND start_time > DATE_SUB(NOW(), INTERVAL 2 DAY)) a 
                    WHERE rnk <= 2
                UNION ALL
                SELECT *
                FROM ( 
                    SELECT *, RANK() OVER (PARTITION BY channel_id ORDER BY start_time) AS rnk 
                    FROM program
                    WHERE start_time > NOW() AND start_time < DATE_ADD(NOW(), INTERVAL 5 DAY)) b
                WHERE rnk <= 10) c
            LEFT JOIN channel d ON c.channel_id = d.id WHERE allowed AND NOT radio ORDER BY `order`,start_time');

        return $this->mapResults($query);
    }

    public function getRadioProgramListsByDate(string $date): array
    {
        $query = $this->db->prepare('
            SELECT *, c.id, (SELECT GROUP_CONCAT(group_id) FROM group_channel WHERE channel_id = d.id) AS groupsString
            FROM 
                 (SELECT *, RANK() OVER (PARTITION BY channel_id ORDER BY start_time DESC) AS rnk
                 FROM program
                 WHERE DATE(start_time) = ?) c
                     LEFT JOIN channel d ON c.channel_id = d.id
            WHERE allowed AND radio
            ORDER BY `order`,start_time');
        $query->execute([$date]);

        return $this->mapResults($query);
    }

    public function getRadioProgramLists(): array
    {
        $query = $this->db->query('
            SELECT *, c.id, (SELECT GROUP_CONCAT(group_id) FROM group_channel WHERE channel_id = d.id) AS groupsString
            FROM (
                 SELECT *
                 FROM (
                     SELECT *, RANK() OVER (PARTITION BY channel_id ORDER BY start_time DESC) AS rnk
                         FROM program
                         WHERE start_time < NOW() AND start_time > DATE_SUB(NOW(), INTERVAL 2 DAY)) a 
                 WHERE rnk <= 2
                 UNION ALL
                 SELECT *
                 FROM (
                     SELECT *, RANK() OVER (PARTITION BY channel_id ORDER BY start_time) AS rnk
                         FROM program
                         WHERE start_time > NOW() AND start_time < DATE_ADD(NOW(), INTERVAL 5 DAY)) b
                 WHERE rnk <= 10) c
            LEFT JOIN channel d ON c.channel_id = d.id
            WHERE allowed AND radio
            ORDER BY `order`,start_time');

        return $this->mapResults($query);
    }

    private function mapResults($query): array
    {
        $programLists = [];
        while ($row = $query->fetchObject()) {
            $program = new StoredProgram(
                (int)$row->id,
                $row->title,
                new StoredChannel($row->channel_id, $row->data_id, $row->name, $row->url_name, $row->groupsString ?? ''),
                $row->description,
                new DateTimeImmutable($row->start_time),
                new DateTimeImmutable($row->end_time),
                $row->season,
                $row->episode,
                $row->episodes
            );
            if (!isset($programLists[$row->channel_id])) {
                $programLists[$row->channel_id] = new ProgramList($program->storedChannel(), $program);
            } else {
                $programLists[$row->channel_id] = $programLists[$row->channel_id]->append($program);
            }
        }

        return $programLists;
    }

    public function getStoredProgramTimeRange(): array
    {
        $row = $this->db->query('SELECT MIN(start_time) AS min,MAX(start_time) AS max FROM program')->fetchObject();
        $ar = [new DateTimeImmutable($row->min ?? ''), new DateTimeImmutable($row->max ?? '')];

        return $ar;
    }

    /**
     * @param string $id
     * @return Program
     * @throws Exception
     */
    public function getProgramById(string $id): Program
    {
        $query = $this->db->prepare('
            SELECT *, a.id as program_id, b.id as channel_id, (SELECT GROUP_CONCAT(group_id) FROM group_channel WHERE channel_id = b.id) AS groupsString
            FROM program a
                LEFT JOIN channel b ON a.channel_id = b.id
            WHERE a.id = :id');
        $query->bindValue(':id', (int)$id);
        $query->execute();
        $row = $query->fetchObject();

        return new StoredProgram(
            (int)$row->program_id,
            $row->title,
            new StoredChannel($row->channel_id, $row->data_id, $row->name, $row->url_name, $row->groupsString ?? ''),
            $row->description,
            new DateTimeImmutable($row->start_time),
            new DateTimeImmutable($row->end_time),
            $row->season,
            $row->episode,
            $row->episodes
        );
    }

    /**
     * @param Program $program
     * @param int $limit
     * @return Program[]
     * @throws Exception
     */
    public function getUpcomingBroadcasts(Program $program, int $limit = 10): array
    {
        $programs = [];
        $query = $this->db->prepare('
            SELECT *, a.id as program_id, b.id as channel_id, (SELECT GROUP_CONCAT(group_id) FROM group_channel WHERE channel_id = b.id) AS groupsString
            FROM program a
                LEFT JOIN channel b ON a.channel_id = b.id
            WHERE a.title = :title AND a.start_time > :start_time LIMIT :limit');
        $query->bindValue(':title', $program->title());
        $query->bindValue(':start_time', $program->startTime()->format('Y-m-d H:i:s'));
        $query->bindValue(':limit', $limit);
        $query->execute();

        while ($row = $query->fetchObject()) {
            $programs[] = new StoredProgram(
                (int)$row->id,
                $row->title,
                new StoredChannel((int)$row->channel_id, $row->data_id, $row->name, $row->url_name, $row->groupsString ?? ''),
                $row->description,
                new DateTimeImmutable($row->start_time),
                new DateTimeImmutable($row->end_time),
                $row->season,
                $row->episode,
                $row->episodes
            );
        }

        return $programs;
    }
}