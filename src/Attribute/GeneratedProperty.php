<?php

declare(strict_types=1);

namespace MakinaCorpus\ApiGenerator\Attribute;

#[\Attribute(flags: \Attribute::TARGET_PROPERTY | \Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class GeneratedProperty
{
    public function __construct(
        /**
         * Generated property name.
         *
         * If none provided, use the source one.
         */
        public readonly ?string $name = null,

        /**
         * Generated type name, overrides sourcee one.
         *
         * If none provided, use source type name, converted to output name
         * using this exact same attribute. If type could not be resolved,
         * property will be any and optional.
         *
         * As soon as you provide this value different from null, all other
         * information such as nullable or collection will be derived from
         * this attribute, and ignored from the property definition.
         *
         * @var null|string|array<string>
         */
        public readonly null|string|array $type = null,

        /**
         * Property is collection, ignored if types are not set.
         */
        public readonly bool $collection = false,

        /**
         * Property is nullable, ignored if types are not set.
         */
        public readonly bool $nullable = true,

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
