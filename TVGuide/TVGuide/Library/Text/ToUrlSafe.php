<?php
declare(strict_types=1);

namespace TVGuide\Library\Text;

final class ToUrlSafe
{
    private $sourceString;
    private $urlSafe;

    public function __construct(string $sourceString)
    {
        $this->sourceString = $sourceString;
    }

    public function string(): string
    {
        if ($this->urlSafe !== null) {
            return $this->urlSafe;
        }

        $urlSafeStr = mb_strtolower($this->sourceString);
        $urlSafeStr = str_replace(' ', '-', $urlSafeStr);
        $urlSafeStr = preg_replace('#[^a-z0-9\-\_]#', '-', $urlSafeStr);
        $urlSafeStr = preg_replace('#-([-]+)#', '-', $urlSafeStr);
        $urlSafeStr = preg_replace('#^([-]+)#', '', $urlSafeStr);

        $this->urlSafe = preg_replace('#([-]+)$#', '', $urlSafeStr);

        return $this->urlSafe;
    }
}