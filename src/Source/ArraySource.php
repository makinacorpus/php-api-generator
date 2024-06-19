<?php

declare(strict_types=1);

namespace MakinaCorpus\ApiGenerator\Source;

use MakinaCorpus\ApiGenerator\Configuration;

class ArraySource extends Source
{
    public function __construct(
        public readonly array $classNames,
    ) {}

    #[\Override]
    public function findTypes(Configuration $configuration): iterable
    {
        foreach ($this->classNames as $className) {
            yield $this->createTypeUsingReflection($className, $configuration);
        }
    }
}
