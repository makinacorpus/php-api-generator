<?php

declare(strict_types=1);

namespace MakinaCorpus\ApiGenerator\Output\Language;

use MakinaCorpus\ApiGenerator\GeneratorContext;
use MakinaCorpus\ApiGenerator\Property;
use MakinaCorpus\ApiGenerator\Type;
use MakinaCorpus\ApiGenerator\Output\Language;
use MakinaCorpus\ApiGenerator\Output\OutputFile;

class TypeScriptLanguage extends Language
{
    #[\Override]
    public function resolveTargetFile(GeneratorContext $context, Type $output): string
    {
        if (!$output->namespace || $output->namespace->isEmpty()) {
            return 'index.ts'; // @todo Warning, files could be overwritten.
        }
        return ((string) $output->namespace) . '.ts';
    }

    #[\Override]
    public function generateTypeCode(GeneratorContext $context, Type $output): string
    {
        $interfaceName = $output->name;

        $ret = [];

        // Find parent class if any.
        if ($output->parent) {
            $extendsString = ' extends ' . $output->parent->name;
        } else {
            $extendsString = '';
        }

        $ret[] = "export interface " . $interfaceName . $extendsString . " {";

        foreach ($output->properties as $property) {
            \assert($property instanceof Property);

            $propertyName = $property->name;
            if ($property->nullable) {
                $propertyName .= '?';
            }

            $suffix = '';
            if ($property->collection) {
                $suffix = '[]';
            }

            $ret[] = "    readonly " . $propertyName . ": " . $this->exportTypeListSignature($context, $property->types) . $suffix . ";";
        }

        $ret[] = "}";

        return \implode("\n", $ret);
    }

    #[\Override]
    public function generateFileHeader(GeneratorContext $context, OutputFile $outputFile): string
    {
        $importGroups = [];
        foreach ($outputFile->getAllDependencies() as $dependency) {
            if (!$dependency->namespace?->isEmpty()) {
                continue;
            }
            // TypeScript project does relative imports ("../foo/bar.ts")
            // a JavaScript convention exists where "@/" is the project root
            // but we have no insurance this exists. Since we have a root
            // folder we can compute relative imports for pretty much
            // everything. This is the only way it'll work gracefully in
            // all projects.
            $relative = $outputFile->namespace->relative($dependency->namespace, '.', '..');
            $importGroups[(string) $relative][] = $dependency->name;
        }

        if ($importGroups) {
            $ret = [];
            \ksort($importGroups);
            foreach ($importGroups as $namespace => $nameList) {
                \sort($nameList);
                $ret[] = "import { " . \implode(', ', $nameList) . " } from '" . $namespace . "';";
            }
            return \implode("\n", $ret);
        }
        return '';
    }

    private function resolveInternalTypeAlias(GeneratorContext $context, string $name): ?string
    {
        // If the "array" type succeeds in arriving here, this means that the
        // property real type was missed, and that property was not extracted as
        // being a collection. We don't know what to do and expose it as "any".
        if ('string' === $name) {
            return 'string';
        }
        if ('resource' === $name) {
            return 'unknown';
        }
        if ('object' === $name || 'mixed' === $name || 'resource' === $name || 'array' === $name) {
            return 'any';
        }
        if ('int' === $name || 'float' === $name) {
            return 'number';
        }
        if ('bool' === $name) {
            return 'boolean';
        }
        return null;
    }

    private function exportTypeListSignature(GeneratorContext $context, array $nativeTypes): string
    {
        $ret = [];
        foreach ($nativeTypes as $name) {
            if ($output = $context->getType($name)) {
                $ret[] = $output->name;
            } else if ($name = $this->resolveInternalTypeAlias($context, $name)) {
                $ret[] = $name;
            }
        }

        return $ret ? \implode('|', $ret) : 'any';
    }
}
