<?php

declare(strict_types=1);

namespace MakinaCorpus\ApiGenerator\Attribute;

/**
 * Mark a type as being an alias to another one.
 */
#[\Attribute(flags: \Attribute::TARGET_CLASS)]
class GeneratedTypeAlias
{
    public function __construct(
        /**
         * Other type alias, native or generated.
         */
        public readonly string $name,

        /**
         * Which generationg groups this is active.
         */
        public readonly array $groups = [],
    ) {}
}
