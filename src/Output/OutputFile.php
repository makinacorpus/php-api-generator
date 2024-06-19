<?php

declare(strict_types=1);

namespace MakinaCorpus\ApiGenerator\Output;

use MakinaCorpus\ApiGenerator\Type;

final class OutputFile
{
    /** @var string[] */
    private array $codeBlocks = [];
    /** @var Type[] */
    private array $dependencies = [];

    public function __construct(
        public readonly string $name,
        public readonly string $namespace,
    ) {}

    /** @return Type[] */
    public function getAllDependencies(): iterable
    {
        return $this->dependencies;
    }

    /** @return string[] */
    public function getAllCodeBlocks(): iterable
    {
        return $this->codeBlocks;
    }

    public function addDependency(Type $dependency): void
    {
        foreach ($this->dependencies as $candidate) {
            if ($dependency->equals($candidate)) {
                return;
            }
        }
        $this->dependencies[] = $dependency;
    }

    public function addCodeBlock(string $code): void
    {
        $this->codeBlocks[] = $code;
    }
}
