<?php

declare(strict_types=1);

namespace MakinaCorpus\ApiGenerator\Attribute;

#[\Attribute(flags: \Attribute::TARGET_CLASS)]
class GeneratedType
{
    public function __construct(
        /**
         * Generated interface or property name.
         *
         * If none provided, use the source one.
         */
        public readonly ?string $name = null,

        /**
         * Generated filename, without extension.
         *
         * If none providen, generates one using default strategy.
         */
        public readonly ?string $namespace = null,

        /**
         * Arbitrary usage.
         */
        public readonly ?string $usage = null,

        /**
         * Set this to true to blacklist type or property.
         */
        public readonly bool $ignore = false,

        /**
         * Which generationg groups this is active.
         */
        public readonly array $groups = [],
    ) {}
}
