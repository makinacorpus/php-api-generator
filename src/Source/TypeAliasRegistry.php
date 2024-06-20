<?php

declare(strict_types=1);

namespace MakinaCorpus\ApiGenerator\Source;

use MakinaCorpus\ApiGenerator\Configuration;
use MakinaCorpus\ApiGenerator\Attribute\GeneratedTypeAlias;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Uid\AbstractUid;

class TypeAliasRegistry
{
    /**
     * Get arbitrary alias for type.
     *
     * This returns a native name string.
     */
    public function getTypeAlias(Configuration $configuration, string $nativeName): ?string
    {
        // Handle a few hardcoded types.
        // @todo Make this configuration.
        if ($this->typeImplements($nativeName, AbstractUid::class)) {
            return 'string';
        }
        if ($this->typeImplements($nativeName, \DateTimeInterface::class)) {
            return 'string';
        }
        if ($this->typeImplements($nativeName, UuidInterface::class)) {
            return 'string';
        }

        if (\class_exists($nativeName)) {
            $refClass = new \ReflectionClass($nativeName);
            foreach ($refClass->getAttributes(GeneratedTypeAlias::class) as $refAttr) {
                $instance = $refAttr->newInstance();
                \assert($instance instanceof GeneratedTypeAlias);

                // @todo handle groups.
                return $instance->name;
            }
        }

        // @todo This is where user configuration will belong.
        return null;
    }

    private function typeImplements(string $type, string $implements): bool
    {
        return $type === $implements || ((\interface_exists($implements) || \class_exists($implements)) && \is_subclass_of($type, $implements));
    }
}
