<?php

declare(strict_types=1);

namespace MakinaCorpus\ApiGenerator\Source;

use MakinaCorpus\ApiGenerator\Configuration;
use MakinaCorpus\ApiGenerator\Type;

class ArraySource extends Source
{
    public function __construct(
        public readonly array $classNames,
    ) {}

    #[\Override]
    public function resolveType(Configuration $configuration, string $nativeType): ?Type
    {
        return $this->createTypeUsingReflection($nativeType, $configuration);
    }

    #[\Override]
    protected function getTypeList(Configuration $configuration): iterable
    {
        return $this->classNames;
    }
}
