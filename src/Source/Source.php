<?php

declare(strict_types=1);

namespace MakinaCorpus\ApiGenerator\Source;

use MakinaCorpus\ApiGenerator\Configuration;
use MakinaCorpus\ApiGenerator\Type;

/**
 * Finds classes to generate.
 */
interface Source
{
    /**
     * Find generable classes.
     *
     * @return Type[]
     */
    public function findTypes(Configuration $configuration): iterable;

    /**
     * Resolve a single type.
     */
    public function resolveType(Configuration $configuration, string $nativeType): ?Type;
}
