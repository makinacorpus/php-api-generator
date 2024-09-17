<?php

declare(strict_types=1);

namespace MakinaCorpus\ApiGenerator;

use MakinaCorpus\ApiGenerator\Output\Language;
use MakinaCorpus\ApiGenerator\Output\OutputFile;
use MakinaCorpus\ApiGenerator\Source\Source;

class Generator
{
    /**
     * Generate output code.
     */
    public function generate(
        Source $source,
        Language $language,
        string $directory,
        null|GeneratorContext|Configuration $context = null,
    ) {
        if (!\is_dir($directory)) {
            throw new \InvalidArgumentException(\sprintf("Directory does not exist: '%s'", $directory));
        }

        if ($context instanceof GeneratorContext) {
            // Nothing to do.
        } else if ($context instanceof Configuration) {
            $context = new GeneratorContext($context);
        } else {
            $context = new GeneratorContext();
        }

        $language->prepareContext($context);

        // Filter classes and keep only those we can generate.
        // Also resolves the parenting tree of each class.
        foreach ($source->findTypes($context->configuration) as $input) {
            \assert($input instanceof Type);

            $this->resolveType($source, $language, $context, $input);
        }

        // Once all types are resolved, create output file and resolve
        // dependencies, then generate code.
        foreach ($context->getAllTypes() as $output) {
            \assert($output instanceof Type);

            // Resolve output file for target type.
            $targetFile = \rtrim($directory, '/') . '/' . \ltrim($language->resolveTargetFile($context, $output), '/');
            $outputFile = $context->getFile($targetFile, $output->namespace);

            if ($output->parent) {
                $outputFile->addDependency($output->parent);
            }
            foreach ($output->properties as $property) {
                foreach ($property->types as $typeId) {
                    if ($context->hasType($typeId)) {
                        $outputFile->addDependency($context->getType($typeId));
                    }
                }
            }

            $outputFile->addCodeBlock($language->generateTypeCode($context, $output));
        }

        // Now generate all files.
        foreach ($context->getAllFiles() as $outputFile) {
            \assert($outputFile instanceof OutputFile);

            $targetFile = $outputFile->name;
            if (\file_exists($targetFile)) {
                $context->configuration->logger->warning(\sprintf("File overwrite: '%s'", $targetFile));
                if (!\unlink($targetFile)) {
                    throw new \InvalidArgumentException(\sprintf("Could not delete file: %s", $targetFile));
                }
            } else {
                $targetDirectory = \dirname($targetFile);
                if (!\is_dir($targetDirectory)) {
                    $context->configuration->logger->notice(\sprintf("Directory creation: '%s'", $targetDirectory));
                    if (!\mkdir($targetDirectory, 0755, true)) {
                        throw new \InvalidArgumentException(\sprintf("Could not create directory: '%s'", $targetDirectory));
                    }
                }
                $context->configuration->logger->notice(\sprintf("File creation: '%s'", $targetFile));
            }

            if (!$handle = \fopen($targetFile, "w+")) {
                throw new \InvalidArgumentException(\sprintf("Could not create file: '%s'", $targetFile));
            }
            try {
                // Header is written after all other pieces of code has been
                // resolved, this allows the code generation from the language
                // to arbitrarily add new dependencies along the way while
                // generating code.
                \fwrite($handle, $language->generateFileHeader($context, $outputFile) . "\n");

                foreach ($outputFile->getAllCodeBlocks() as $codeBlock) {
                    \fwrite($handle, "\n");
                    \fwrite($handle, $codeBlock);
                    \fwrite($handle, "\n");
                }
            } finally {
                @\fclose($handle);
            }
        }
    }

    /**
     * Type recursion (creates output types for all parents).
     */
    private function resolveType(
        Source $source,
        Language $language,
        GeneratorContext $context,
        Type $input,
    ): ?Type {
        /*
        if ($context->isClassBlacklisted($input)) {
            continue;
        }
         */

        $typeId = $input->getId();

        if ($context->hasType($typeId)) {
            return $context->getType($typeId);
        }

        $output = $language->revolveType($context, $input);

        if (!$output) {
            $context->configuration->logger->warning(\sprintf("Skipping source type: '%s'", $input->getId()));

            return null;
        }

        $context->configuration->logger->info(\sprintf("Adding type: '%s' as '%s/%s'", $output->getNativeType(), $output->namespace, $output->name));
        $context->addType($output);

        if ($input->parent) {
            $output->parent = $this->resolveType($source, $language, $context, $input->parent);
        }

        foreach ($input->properties as $property) {
            $output->properties[$property->name] = new Property(
                name: $property->name,
                types: \array_unique(\array_filter(\array_map(fn ($type) => $this->resolvePropertyType($source, $language, $context, $type), $property->types))),
                isSumType: $property->isSumType,
                nullable: $property->nullable,
                collection: $property->collection,
            );
        }

        return $output;
    }

    /**
     * Calls resolveType() but resolve from source first.
     */
    private function resolveTypeFromSource(
        Source $source,
        Language $language,
        GeneratorContext $context,
        string $nativeType,
    ): ?Type {
        if ($context->hasType($nativeType)) {
            return $context->getType($nativeType);
        }

        $input = $source->resolveType($context->configuration, $nativeType);

        return $input ? $this->resolveType($source, $language, $context, $input) : null;
    }

    /**
     * Property recursion.
     */
    private function resolvePropertyType(
        Source $source,
        Language $language,
        GeneratorContext $context,
        string $nativeType,
    ): string {
        $alias = $context->getTypeAlias($nativeType);

        if ($alias) {
            $context->configuration->logger->debug(\sprintf("Property with type: '%s' is aliased as '%s'", $nativeType, $alias));

            $output = $this->resolveTypeFromSource($source, $language, $context, $alias);
            if ($output) {
                $context->configuration->logger->debug(\sprintf("Alias: '%s' is resolved as '%s/%s'", $alias, $output->namespace, $output->name));
            }

            return $output?->getId() ?? $alias;
        }

        return $this->resolveTypeFromSource($source, $language, $context, $nativeType)?->getId() ?? $nativeType;
    }
}
