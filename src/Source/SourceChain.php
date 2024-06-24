<?php

declare(strict_types=1);

namespace MakinaCorpus\ApiGenerator\Source;

use MakinaCorpus\ApiGenerator\Configuration;
use MakinaCorpus\ApiGenerator\Type;

/**
 * Implements the chain of responsability pattern by using many sources.
 */
class SourceChain implements Source
{
    public function __construct(
        /** @var Source[] */
        private iterable $instances,
    ) {}

    #[\Override]
    public function findTypes(Configuration $configuration): iterable
    {
        foreach ($this->instances as $instance) {
            \assert($instance instanceof Source);
            yield from $instance->findTypes($configuration);
        }
    }

    #[\Override]
    public function resolveType(Configuration $configuration, string $nativeType): ?Type
    {
        foreach ($this->instances as $instance) {
            \assert($instance instanceof Source);
            if ($type = $instance->resolveType($configuration, $nativeType)) {
                return $type;
            }
        }
        return null;
    }
}
