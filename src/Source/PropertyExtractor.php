<?php

declare(strict_types=1);

namespace MakinaCorpus\ApiGenerator\Source;

use MakinaCorpus\ApiGenerator\Configuration;
use MakinaCorpus\ApiGenerator\Property;
use MakinaCorpus\ApiGenerator\Type;

\class_exists(Property::class);

/**
 * Extract properties from a source class.
 */
interface PropertyExtractor
{
    /**
     * Find PHP class name properties.
     *
     * @return Property[]
     */
    public function findProperties(Type $type, Configuration $configuration): iterable;
}
