<?php
declare(strict_types=1);

namespace TVGuide\Module\Importers\Eurosport\Model;

use TVGuide\Contract\Channel;
use TVGuide\Library\Text\ToUrlSafe;

final class EurosportChannel implements Channel
{
    private $id;
    private $urlSafeName;

    public function __construct(string $filename)
    {
        if (strpos($filename, 'EurosportFinland') !== false) {
            $this->id = 'es1';
        }

        if (strpos($filename, 'Eurosport2Sweden') !== false) {
            $this->id = 'es2';
        }
    }

    public function name(): string
    {
        return ['es1' => 'Eurosport 1', 'es2' => 'Eurosport 2'][$this->id];
    }

    public function urlSafeName(): string
    {
        if ($this->urlSafeName !== null) {
            return $this->urlSafeName;
        }

        $this->urlSafeName = (new ToUrlSafe($this->name()))->string();

        return $this->urlSafeName;
    }

    public function dataId(): string
    {
        return $this->id;
    }
}