<?php

declare (strict_types=1);

namespace MakinaCorpus\ApiGenerator\Tests\Functional;

use MakinaCorpus\ApiGenerator\Configuration;
use MakinaCorpus\ApiGenerator\Generator;
use MakinaCorpus\ApiGenerator\GeneratorContext;
use MakinaCorpus\ApiGenerator\Property;
use MakinaCorpus\ApiGenerator\Type;
use MakinaCorpus\ApiGenerator\Output\Language\TypeScriptLanguage;
use MakinaCorpus\ApiGenerator\Source\ArraySource;
use PHPUnit\Framework\TestCase;

class GeneratorTest extends TestCase
{
    public function testTypeScriptGenerator(): void
    {
        $source = new ArraySource([
            GeneratorContext::class,
            Type::class,
            Property::class,
        ]);

        $generator = new Generator();

        $generator->generate(
            context: new GeneratorContext(
                configuration: new Configuration(
                    namespaceInputPrefix: 'MakinaCorpus\\ApiGenerator',
                    namespaceOutputPrefix: null,
                ),
            ),
            directory: __DIR__ . '/test',
            source: $source,
            language: new TypeScriptLanguage(),
        );
    }
}
