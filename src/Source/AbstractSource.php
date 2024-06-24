<?php

declare(strict_types=1);

namespace MakinaCorpus\ApiGenerator\Source;

use MakinaCorpus\ApiGenerator\Attribute\GeneratedType;
use MakinaCorpus\ApiGenerator\Configuration;
use MakinaCorpus\ApiGenerator\Property;
use MakinaCorpus\ApiGenerator\Type;
use MakinaCorpus\ApiGenerator\TypeNamespace;

/**
 * Finds classes to generate.
 */
abstract class AbstractSource implements Source
{
    /** @var PropertyExtractor[] */
    private null|array $propertyExtractors = null;

    #[\Override]
    public function findTypes(Configuration $configuration): iterable
    {
        foreach ($this->getTypeList($configuration) as $nativeType) {
            if ($type = $this->resolveType($configuration, $nativeType)) {
                yield $type;
            }
        }
    }

    #[\Override]
    public function resolveType(Configuration $configuration, string $nativeType): ?Type
    {
        return $this->createTypeUsingReflection($nativeType, $configuration);
    }

    /**
     * Find all types to export.
     *
     * @return string[]
     */
    protected abstract function getTypeList(Configuration $configuration): iterable;

    /**
     * From PHP class name, get source type instance.
     */
    protected function createTypeUsingReflection(string $className, Configuration $configuration): ?Type
    {
        $className = \trim($className, '\\');

        if (!\class_exists($className)) {
            return null;
        }

        $outputTypeName = null;
        $outputNamespace = null;
        $refClass = new \ReflectionClass($className);

        $foundUsingAttributes = false;
        foreach ($refClass->getAttributes(GeneratedType::class) as $refAttr) {
            $instance = $refAttr->newInstance();
            \assert($instance instanceof GeneratedType);

            if ($instance->groups && (!$configuration->groups || !\array_intersect($instance->groups, $configuration->groups))) {
                $configuration->logger->notice("'{type}' is ignored using attribute (unmatched groups)", ['type' => $className]);
                continue;
            }

            if ($instance->ignore) {
                $configuration->logger->notice("'{type}' is ignored using attribute (ignored)", ['type' => $className]);
                return null;
            }

            $outputTypeName = $instance->name;
            $outputNamespace = $instance->namespace; // @todo This probably will not work.

            if ($foundUsingAttributes) {
                $configuration->logger->warning("'{type}' more than one attribute compete for types", ['type' => $className]);
                throw new \InvalidArgumentException();
            }
            $foundUsingAttributes = true;
        }

        $parentClass = $refClass->getParentClass();

        $type = new Type(
            abstract: $refClass->isAbstract() || $refClass->isInterface() || !$refClass->isInstantiable(),
            name: $outputTypeName ?? $refClass->getShortName(),
            namespace: new TypeNamespace($outputNamespace ?? $refClass->getNamespaceName(), '\\'),
            nativeName: $className,
            parent:  $parentClass ? $this->createTypeUsingReflection($parentClass->getName(), $configuration) : null,
        );

        $this->findProperties($type, $configuration);

        return $type;
    }

    /**
     * Find properties in type.
     */
    protected function findProperties(Type $type, Configuration $configuration): void
    {
        foreach ($this->getPropertyExtrators() as $propertyExtractor) {
            \assert($propertyExtractor instanceof PropertyExtractor);

            foreach ($propertyExtractor->findProperties($type, $configuration) as $property) {
                \assert($property instanceof Property);

                if (\array_key_exists($property->name, $type->properties)) {
                    $configuration->logger->notice("'{type}.{property}' was previously found", ['type' => $type->getId(), 'property' => $property->name]);
                    continue;
                }

                $type->properties[$property->name] = $property;
            }
        }
    }

    /**
     * When the source is initialized, this method gives a change to child
     * implementation to either replace all or add new property extractors.
     *
     * @return PropertyExtractor[]
     */
    protected function createDefaultPropertyExtractors(): iterable
    {
        yield new ClassPropertyExtractor();
    }

    /**
     * Set additional property extractors.
     */
    protected function setPropertyExtractors(array $propertyExtractors): void
    {
        if (null !== $this->propertyExtractors) {
            throw new \LogicException("Object was already initializd.");
        }

        $this->propertyExtractors = $propertyExtractors;

        foreach ($this->createDefaultPropertyExtractors() as $propertyExtractor) {
            $this->propertyExtractors[] = $propertyExtractor;
        }
    }

    /**
     * Get property extractors.
     */
    protected function getPropertyExtrators(): iterable
    {
        if (null === $this->propertyExtractors) {
            $this->propertyExtractors = [];

            foreach ($this->createDefaultPropertyExtractors() as $propertyExtractor) {
                $this->propertyExtractors[] = $propertyExtractor;
            }
        }

        return $this->propertyExtractors;
    }
}
