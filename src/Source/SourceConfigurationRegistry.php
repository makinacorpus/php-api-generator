<?php

declare(strict_types=1);

namespace MakinaCorpus\ApiGenerator\Source;

/**
 * Exists for plugging this API into dependency injection containers.
 */
class SourceConfigurationRegistry
{
    public function __construct(
        /** @var array<string,SourceConfiguration> */
        private array $instances,
    ) {}

    public function get(string $name): SourceConfiguration
    {
        return $this->instances[$name] ?? throw new \InvalidArgumentException(\sprintf("Directory for source '%s' was not registered.", $name));
    }
}
