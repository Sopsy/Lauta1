<?php
declare(strict_types=1);

namespace TVGuide\Module\Sources;

use TVGuide\Contract\Source;
use function unlink;

final class DefaultSource implements Source
{
    private array $sourceFiles = [];

    public function __construct(array $sources)
    {
        foreach ($sources as $source) {
            if (is_dir($source)) {
                $this->findXML($source);
            } else {
                $this->sourceFiles[] = $source;
            }
        }
    }

    public function sources(): array
    {
        return $this->sourceFiles;
    }

    public function deleteSourceFiles(): void
    {
        foreach ($this->sources() as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    private function findXML(string $directory): void
    {
        foreach (glob($directory . '/*.xml', GLOB_NOSORT) as $filename) {
            $this->sourceFiles[] = $filename;
        }
    }
}