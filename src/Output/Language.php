<?php

declare(strict_types=1);

namespace MakinaCorpus\ApiGenerator\Output;

use MakinaCorpus\ApiGenerator\GeneratorContext;
use MakinaCorpus\ApiGenerator\Type;

abstract class Language
{
    /**
     * Prepare context, such as register basic type mapping.
     */
    public function prepareContext(GeneratorContext $context): void
    {
    }

    /**
     * Resolve output type from input type.
     *
     * Do not process properties, it will be done later by the generator.
     */
    public function revolveType(GeneratorContext $context, Type $input): ?Type
    {
        return new Type(
            name: $input->name,
            namespace: $context->resolveNamespace($input->namespace, '/'),
            nativeName: $input->nativeName,
            source: $input,
            usage: $input->usage
        );
    }

    /**
     * Get target file for given source class in given context.
     *
     * Some languages will require one class per file, other can aggregate
     * many in the same file. It's up to the implementation here to do
     * whatever feels right.
     */
    public abstract function resolveTargetFile(GeneratorContext $context, Type $output): string;

    /**
     * Generate output type code in target language.
     */
    public abstract function generateTypeCode(GeneratorContext $context, Type $output): string;

    /**
     * Generate file header, usually where import/use statements are.
     */
    public abstract function generateFileHeader(GeneratorContext $context, OutputFile $outputFile): string;
}
