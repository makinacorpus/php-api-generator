<?php

declare(strict_types=1);

namespace MakinaCorpus\ApiGenerator\Source;

use MakinaCorpus\ApiGenerator\Configuration;

class ArraySource extends AbstractSource
{
    public function __construct(
        private readonly array $classNames,
    ) {}

    #[\Override]
    protected function getTypeList(Configuration $configuration): iterable
    {
        return $this->classNames;
    }
}
