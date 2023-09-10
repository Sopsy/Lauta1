<?php
declare(strict_types=1);

namespace TVGuide\Module\Program\Model;

use TVGuide\Contract\Channel;
use TVGuide\Contract\Program;
use function array_merge;

final class ProgramList
{
    /** @var Program[] */
    private $programs;
    private $channel;

    public function __construct(Channel $channel, StoredProgram ...$programs)
    {
        $this->channel = $channel;
        $this->programs = $programs;
    }

    public function channel(): Channel
    {
        return $this->channel;
    }

    public function append(StoredProgram $program): ProgramList
    {
        $list = $this->programs;
        $list[] = $program;
        return new ProgramList($this->channel, ...$list);
    }

    /**
     * @return Program[]
     */
    public function programs(): array
    {
        return $this->programs;
    }

    public function programGroups(): array
    {
        $programGroupList = [];
        foreach ($this->programs as $program) {
            $day = (int)$program->startTime()->format('d');
            $group = $this->getGroup($program);
            if (!isset($programGroupList[$day])) {
                $programGroupList[$day] = [];
            }
            if (!isset($programGroupList[$day][$group])) {
                $programGroupList[$day][$group] = [];
            }
            $programGroupList[$day][$group][] = $program;
        }

        return array_merge(...$programGroupList);
    }

    private function getGroup(StoredProgram $program)
    {
        $hour = (int)$program->startTime()->format('H');

        return (int)($hour / 3) * 3;
    }
}