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
        ?GeneratorContext $context = null,
    ) {
        if (!\is_dir($directory)) {
            throw new \InvalidArgumentException(\sprintf("Directory does not exist: '%s'", $directory));
        }

        $context ??= new GeneratorContext();
        $language->prepareContext($context);

        // Filter classes and keep only those we can generate.
        // Also resolves the parenting tree of each class.
        foreach ($source->findTypes($context->configuration) as $input) {
            $this->resolveType($context, $language, $input);
        }

        // Once all types are resolved, resolve properties.
        foreach ($context->getAllTypes() as $output) {
            \assert($output instanceof Type);

            // Resolve output file for target type.
            $targetFile = \rtrim($directory, '/') . '/' . \ltrim($language->resolveTargetFile($context, $output), '/');
            $outputFile = $context->getFile($targetFile, (string) $output->namespace);

            // Resolve parenting dependency.
            if ($output->parent && $output->parent->namespace !== $output->parent) {
                $outputFile->addDependency($output->parent);
            }

            // Once that done, resolve all properties and dependencies.
            if (!$output->properties && $output->source) {
                // @phpstan-ignore-next-line
                foreach ($output->source?->properties as $property) {
                    // @todo Clean this up.
                    $types = [];
                    foreach ($property->types as $nativeType) {
                        // @todo does not handle type name collision when
                        //    they come from different namespaces.
                        if ($context->hasType($nativeType)) {
                            $propertyType = $context->getType($nativeType);
                            $types[] = $propertyType->name;
                            if ($output->namespace && $propertyType->namespace && !$propertyType->namespace->equals($output->namespace)) {
                                $outputFile->addDependency($propertyType);
                            }
                        } else {
                            // @todo warning could not derivate type
                        }
                    }

                    $output->properties[] = new Property(
                        name: $property->name,
                        types: $types, // Note: can be empty.
                        isSumType: $property->isSumType,
                        nullable: $property->nullable,
                        collection: $property->collection,
                    );
                }

                $outputFile->addCodeBlock($language->generateTypeCode($context, $output));
            }
        }

        // Now generate all files.
        foreach ($context->getAllFiles() as $outputFile) {
            \assert($outputFile instanceof OutputFile);

            $targetFile = $outputFile->name;
            if (\file_exists($targetFile)) {
                $context->configuration->logger->warning(\sprintf('File overwrite: %s)', $targetFile));
                if (!\unlink($targetFile)) {
                    throw new \InvalidArgumentException(\sprintf("Could not delete file: %s", $targetFile));
                }
            } else {
                $targetDirectory = \dirname($targetFile);
                if (!\is_dir($targetDirectory)) {
                    $context->configuration->logger->notice(\sprintf('Directory creation: %s)', $targetDirectory));
                    if (!\mkdir($targetDirectory, 0755, true)) {
                        throw new \InvalidArgumentException(\sprintf("Could not create directory: %s", $targetDirectory));
                    }
                }
                $context->configuration->logger->notice(\sprintf('File creation: %s)', $targetFile));
            }

            if (!$handle = \fopen($targetFile, "w+")) {
                throw new \InvalidArgumentException(\sprintf("Could not create file: %s", $targetFile));
            }
            try {
                \fwrite($handle, $language->generateFileHeader($context, $outputFile) . "\n");

                // Maintenant on pose tous les blocks de code.
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
        GeneratorContext $context,
        Language $language,
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

        $context->addType($output);

        if ($input->parent) {
            $output->parent = $this->resolveType($context, $language, $input->parent);
        }

        return $output;
    }
}
