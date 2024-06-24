<?php

declare(strict_types=1);

namespace MakinaCorpus\ApiGenerator\Source;

use MakinaCorpus\ApiGenerator\Configuration;

/**
 * Not proud of this code, but it works.
 */
class FinderSource extends AbstractSource
{
    private ?array $classNames = null;

    public function __construct(
        private readonly string $directory,
    ) {}

    #[\Override]
    protected function getTypeList(Configuration $configuration): iterable
    {
        if (null !== $this->classNames) {
            return $this->classNames;
        }

        $this->classNames = [];

        if (!\is_dir($this->directory)) {
            throw new \LogicException(\sprintf("Given path '%s' is not a directory.", $this->directory));
        }

        // Find all PHP files in the given directory.
        $iterator = new \RegexIterator(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $this->directory,
                    \FilesystemIterator::SKIP_DOTS
                ),
                \RecursiveIteratorIterator::LEAVES_ONLY
            ),
            '/^.+' . \preg_quote('.php') . '$/i',
            \RecursiveRegexIterator::GET_MATCH,
        );

        foreach ($iterator as $file) {
            $matches = [];

            $sourceFile = $file[0];
            if (\preg_match('(^phar:)i', $sourceFile) === 0) {
                $sourceFile = \realpath($sourceFile);
            }

            $namespace = null;
            $sourceContent = \file_get_contents($sourceFile);
            if (\preg_match('@[^a-z0-9]namespace\s+([^\s]+)\s*;@ims', $sourceContent, $matches)) {
                $namespace = $matches[1];
            }

            if (\preg_match_all('@[^a-z0-9]class\s+([^\s]+)@ims', $sourceContent, $matches)) {
                foreach ($matches[1] as $className) {
                    if ($namespace) {
                        $className = $namespace . '\\' . $className;
                    }
                    if (\class_exists($className)) {
                        $this->classNames[] = $className;
                    }
                }
            }
        }

        return $this->classNames;
    }
}
