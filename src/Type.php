<?php

declare(strict_types=1);

namespace MakinaCorpus\ApiGenerator;

use MakinaCorpus\ApiGenerator\Attribute\GeneratedProperty;
use MakinaCorpus\ApiGenerator\Attribute\GeneratedType;

/**
 * Represent both a source type, and a type to generate.
 */
#[GeneratedType(name: 'Type', namespace: 'generator')]
class Type
{
    public const USAGE_ENTITY = 'entity';
    public const USAGE_EVENT = 'event';
    public const USAGE_MESSAGE = 'message';

    public function __construct(
        /**
         * Source class name (usually a PHP local class name).
         */
        public string $name,

        /**
         * Source namespace (usually a PHP namespace name).
         */
        #[GeneratedProperty(type: 'string')]
        public ?TypeNamespace $namespace = null,

        /**
         * Native complete type name if known.
         *
         * This will be used as this structure identifier all along the processing
         * and final code generation for reconciliation.
         */
        public readonly ?string $nativeName = null,

        /**
         * Determine how is used this type, this is purely arbitrary and might be
         * used by dedicated plugins to build generated code differently.
         */
        public readonly string $usage = self::USAGE_ENTITY,

        /**
         * Is type abstract.
         */
        public readonly bool $abstract = false,

        /**
         * Parent type.
         */
        public ?Type $parent = null,

        /**
         * Properties.
         *
         * @var Property[]
         */
        public array $properties = [],

        /**
         * Source type this was generated from.
         */
        public ?Type $source = null,
    ) {}

    public function equals(Type $other): bool
    {
        return $other instanceof self && $this->getId() === $other->getId();
    }

    public function getNativeType(): string
    {
        return $this->nativeName ?? ($this->namespace . '\\' . $this->name);
    }

    public function getId(): string
    {
        return $this->nativeName ?? ($this->namespace . '###' . $this->name);
    }
}
