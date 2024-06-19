<?php

declare(strict_types=1);

namespace MakinaCorpus\ApiGenerator;

use MakinaCorpus\ApiGenerator\Attribute\GeneratedType;

#[GeneratedType(name: 'TypeProperty', namespace: 'generator')]
final class Property
{
    public function __construct(
        /**
         * PHP property name.
         */
        public string $name,

        /**
         * List of allowed native type identifiers.
         *
         * This can be empty if no type was resolved.
         *
         * @var string[]
         */
        public array $types = [],

        /**
         * If set to true, types are sum type.
         */
        public bool $isSumType = false,

        /**
         * Is property nullable.
         */
        public bool $nullable = true,

        /**
         * Is property a collection.
         */
        public bool $collection = false,
    ) {}
}
