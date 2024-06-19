<?php

declare(strict_types=1);

namespace MakinaCorpus\ApiGenerator;

use MakinaCorpus\ApiGenerator\Output\OutputFile;

/**
 * @todo
 *   - Implement class blacklist
 *   - Implement namespace match list
 */
final class GeneratorContext
{
    /** @var array<string,OutputFile> */
    private array $files = [];
    /** @var array<string,Type> */
    private array $types = [];

    public function __construct(
        public readonly Configuration $configuration = new Configuration(),
    ) {}

    public function resolveNamespace(TypeNamespace $inputNamespace, ?string $separator = null): TypeNamespace
    {
        $namespace = $inputNamespace;

        if ($this->configuration->namespaceInputPrefix) {
            $namespace = $namespace->shift($this->configuration->namespaceInputPrefix);
        }
        if ($this->configuration->namespaceOutputPrefix) {
            $namespace = $namespace->unshift($this->configuration->namespaceOutputPrefix);
        }
        if ($separator) {
            $namespace = $namespace->convert($separator);
        }

        return $namespace;
    }

    public function addType(Type $output): void
    {
        $typeId = $output->getId();

        if (\array_key_exists($typeId, $this->types)) {
            throw new \LogicException();
        }

        $this->types[$typeId] = $output;
    }

    public function getType(string $typeId): ?Type
    {
        return $this->types[$typeId] ?? null;
    }

    public function hasType(string $typeId): bool
    {
        return \array_key_exists($typeId, $this->types);
    }

    /** @return Type[] */
    public function getAllTypes(): iterable
    {
        return $this->types;
    }

    /** @return OutputFile[] */
    public function getAllFiles(): iterable
    {
        return $this->files;
    }

    public function getFile(string $name, string $namespace): OutputFile
    {
        if ($found = $this->files[$name] ?? null) {
            \assert($found instanceof OutputFile);

            if ($found->namespace !== $namespace) {
                throw new \InvalidArgumentException(\sprintf("Namespace mismatch, file contains '%s', given '%s'", $found->namespace, $namespace));
            }

            return $found;
        }

        return $this->files[$name] = new OutputFile($name, $namespace);
    }
}
