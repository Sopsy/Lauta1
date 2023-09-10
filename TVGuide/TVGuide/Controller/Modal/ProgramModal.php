<?php
declare(strict_types=1);

namespace TVGuide\Controller\Modal;

use TVGuide\Contract\Renderable;
use TVGuide\Module\Database\DbConnection;
use TVGuide\Module\Program\Repository\ProgramRepository;
use TVGuide\Module\TemplateEngine\View;

final class ProgramModal
{
    private $db;
    private $cfg;

    public function __construct(DbConnection $db, array $cfg)
    {
        $this->db = $db;
        $this->cfg = $cfg;
    }

    public function index(string $id): Renderable
    {
        $programRepository = new ProgramRepository($this->db);

        $program = $programRepository->getProgramById($id);
        $broadcasts = $programRepository->getUpcomingBroadcasts($program, 100);

        $data = [
            'imgUrl' => $this->cfg['imgUrl'],
            'program' => $program,
            'broadcasts' => $broadcasts,
        ];
        date_default_timezone_set('Europe/Helsinki');
        return new View(__DIR__ . '/../../View/Modal.phtml', $data);
    }
}