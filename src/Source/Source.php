<?php

declare(strict_types=1);

namespace MakinaCorpus\ApiGenerator\Source;

use MakinaCorpus\ApiGenerator\Configuration;
use MakinaCorpus\ApiGenerator\Property;
use MakinaCorpus\ApiGenerator\Type;
use MakinaCorpus\ApiGenerator\TypeNamespace;
use MakinaCorpus\ApiGenerator\Attribute\GeneratedType;

/**
 * Finds classes to generate.
 */
abstract class Source
{
    public function __construct(
        /** @var PropertyExtractor[] */
        private null|iterable $propertyExtractors = null,
    ) {}

    /**
     * Find generable classes.
     *
     * @return Type[]
     */
    public abstract function findTypes(Configuration $configuration): iterable;

    /**
     * From PHP class name, get source type instance.
     */
    protected function createTypeUsingReflection(string $className, Configuration $configuration): Type
    {
        $className = \trim($className, '\\');
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
                continue;
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

    protected function getPropertyExtrators(): iterable
    {
        return $this->propertyExtractors ??= [new ClassPropertyExtractor()];
    }

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
}
