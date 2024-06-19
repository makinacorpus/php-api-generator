<?php

declare(strict_types=1);

namespace MakinaCorpus\ApiGenerator\Source;

use MakinaCorpus\ApiGenerator\Configuration;
use MakinaCorpus\ApiGenerator\Output\Language;

/**
 * Exists for plugging this API into dependency injection containers.
 */
class SourceConfiguration
{
    public function __construct(
        public readonly string $directory,
        public readonly Configuration $configuration,
        public readonly Source $source,
        public readonly Language $language,
    ) {}
}
