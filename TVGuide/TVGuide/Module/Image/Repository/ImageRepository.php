<?php
declare(strict_types=1);

namespace TVGuide\Module\Image\Repository;

use TVGuide\Contract\Program;
use TVGuide\Module\Database\DbConnection;
use TVGuide\Module\ImageProcessor\ProcessedImage;
use TVGuide\Module\Media\Model\Image;
use TVGuide\Module\Program\Model\StoredProgram;
use function hex2bin;

final class ImageRepository
{
    private $db;
    private $basePath;

    public function __construct(DbConnection $db)
    {
        $this->db = $db;
    }

    public function store(ProcessedImage $image): void
    {
        $query = $this->db->prepare('INSERT INTO image (name) VALUES (:name)
            ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)');
        $query->bindValue(':name', hex2bin($image->name()));
        $query->execute();
    }

    /**
     * @param Program $program
     * @return Image[]
     */
    public function getImagesForProgram(StoredProgram $program): array
    {
        $query = $this->db->prepare('SELECT a.* FROM image a JOIN program_image b WHERE a.id = b.image_id AND b.program_id = :program_id');
        $query->bindValue(':program_id', $program->id());
        $query->execute();

        $images = [];
        while ($row = $query->fetchObject()) {
            $images[] = new Image((int)$row->id, bin2hex($row->name), $this->basePath);
        }

        if (empty($images)) {
            $images = $this->getRelatedImagesForProgram($program);
        }

        return $images;
    }

    public function getRelatedImagesForProgram(Program $program): array
    {
        $query = $this->db->prepare('SELECT a.* FROM image a JOIN program_image b JOIN program c WHERE a.id = b.image_id AND b.program_id = c.id AND c.title = :program_title');
        $query->bindValue(':program_title', $program->title());
        $query->execute();

        $images = [];
        while ($row = $query->fetchObject()) {
            $images[] = new Image((int)$row->id, bin2hex($row->name));
        }

        return $images;
    }
}