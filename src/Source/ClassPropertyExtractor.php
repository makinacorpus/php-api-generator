<?php

declare(strict_types=1);

namespace MakinaCorpus\ApiGenerator\Source;

use MakinaCorpus\ApiGenerator\Attribute\GeneratedProperty;
use MakinaCorpus\ApiGenerator\Configuration;
use MakinaCorpus\ApiGenerator\Property;
use MakinaCorpus\ApiGenerator\Type;

class ClassPropertyExtractor implements PropertyExtractor
{
    #[\Override]
    public function findProperties(Type $type, Configuration $configuration): iterable
    {
        if (!$type->nativeName || !\class_exists($type->nativeName)) {
            return;
        }

        $refClass = new \ReflectionClass($type->nativeName);

        // Collect virtual properties.
        foreach ($refClass->getAttributes(GeneratedProperty::class) as $refAttr) {
            $instance = $refAttr->newInstance();
            \assert($instance instanceof GeneratedProperty);

            if (!$instance->name) {
                $configuration->logger->error("'{type}' has a virtual property using '{attr}' attribute with no name.", ['type' => $type->getId(), 'attr' => GeneratedProperty::class]);
                continue;
            }
            if (!$instance->type) {
                $configuration->logger->warning("'{type}.{property}' virtual property has no type", ['type' => $type->getId(), 'property' => $instance->name]);
            }

            yield new Property(
                collection: $instance->collection,
                isSumType: false,
                name: $instance->name,
                nullable: $instance->nullable,
                types: $instance->type ? \array_unique((array) $instance->type) : [],
            );
        }

        foreach ($refClass->getProperties() as $refProp) {
            \assert($refProp instanceof \ReflectionProperty);

            $propertyName = $refProp->getName();

            if ($refProp->getDeclaringClass()->getName() !== $refClass->getName()) {
                continue;
            }

            $foundUsingAttributes = false;
            $ignored = false;

            foreach ($refProp->getAttributes(GeneratedProperty::class) as $refAttr) {
                $instance = $refAttr->newInstance();
                \assert($instance instanceof GeneratedProperty);

                if ($instance->groups && (!$configuration->groups || !\array_intersect($instance->groups, $configuration->groups))) {
                    $configuration->logger->notice("'{type}.{property}' is ignored using attribute (unmatched groups)", ['type' => $type->getId(), 'property' => $propertyName]);
                    continue;
                }

                if ($ignored = $instance->ignore) {
                    $configuration->logger->notice("'{type}.{property}' is ignored using attribute (ignored)", ['type' => $type->getId(), 'property' => $propertyName]);
                    continue;
                }

                if ($instance->name) {
                    $propertyName = $instance->name;
                }

                if ($foundUsingAttributes) {
                    $configuration->logger->warning("'{type}.{property}' more than one attribute compete for types", ['type' => $type->getId(), 'property' => $propertyName]);
                    throw new \InvalidArgumentException();
                }

                if ($instance->type) {
                    $foundUsingAttributes = true;

                    yield new Property(
                        collection: $instance->collection,
                        isSumType: false, // @todo
                        name: $propertyName,
                        nullable: $instance->nullable,
                        types: \array_unique((array) $instance->type),
                    );
                }
            }

            if (!$foundUsingAttributes && !$ignored) {
                yield $this->createPropertyWithReflection($propertyName, $refProp);
            }
        }
    }

    private function createPropertyWithReflection(string $propertyName, \ReflectionProperty $refProp): Property
    {
        $nativeTypes = $candidates = [];
        $nullable = $collection = $isSumType = false;

        // @todo Handle sum types as well.
        if ($refProp->hasType() && ($type = $refProp->getType())) {
            if ($type instanceof \ReflectionUnionType) { // PHP 8+
                foreach ($type->getTypes() as $candidate) {
                    // Else impossible use case, but future PHP version might
                    // have new possibilites.
                    $candidates[] = $candidate->getName();
                }
            } elseif ($type instanceof \ReflectionNamedType) {
                $candidates[] = $type->getName();
            }
            // Else impossible use case, but future PHP version might
            // have new possibilites.

            foreach ($candidates as $name) {
                if ('true' === $name || 'false' === $name) {
                    $nativeTypes[] = 'bool';
                } else if ('null' === $name) {
                    $nullable = true;
                } else if ($this->typeIsCollection($name)) {
                    $collection = true;
                } else {
                    $nativeTypes[] = $name;
                }
            }

            $nullable = $nullable || $type->allowsNull();
        } else {
            // @todo In PHP8 we could condition $nullable to
            //   \ReflectionProperty::hasDefaultValue()
            $nullable = true;
            $nativeTypes[] = 'mixed';
        }

        return new Property(
            collection: $collection,
            isSumType: $isSumType,
            name: $propertyName,
            nullable: $nullable,
            types: \array_unique($nativeTypes),
        );
    }

    private function typeIsCollection(string $type): bool
    {
        return 'array' === $type || 'iterable' === $type || \is_subclass_of($type, \Traversable::class);
    }
}
