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
use MakinaCorpus\ApiGenerator\Tests\Mock\ClassAliasedAliasedClassHolder;
use MakinaCorpus\ApiGenerator\Tests\Mock\ClassAliasedClassHolder;
use MakinaCorpus\ApiGenerator\Tests\Mock\ClassAliasedPrimitiveHolder;
use MakinaCorpus\ApiGenerator\Tests\Mock\ClassIgnoredHolder;
use MakinaCorpus\ApiGenerator\Tests\Mock\ClassNameChange;
use MakinaCorpus\ApiGenerator\Tests\Mock\ClassNamespaceChange;
use MakinaCorpus\ApiGenerator\Tests\Mock\PropertyChangeName;
use MakinaCorpus\ApiGenerator\Tests\Mock\PropertyIgnore;
use MakinaCorpus\ApiGenerator\Tests\Mock\PropertyTypeChange;
use PHPUnit\Framework\TestCase;

class GeneratorTest extends TestCase
{
    private ?string $directory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        //$this->directory = \sys_get_temp_dir() . '/api-generator-' . \uniqid('', true);
        $this->directory = __DIR__ . '/test/api-generator-' . \uniqid('', true);
        \mkdir($this->directory);
    }

    #[\Override]
    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->directory && \is_dir($this->directory)) {
            self::removeDirectory($this->directory);
        }
    }

    public function testClassNameChange(): void
    {
        $this->generateClasses(ClassNameChange::class);

        self::assertSameCode(
            <<<TS
            export interface ChangedName {
                readonly id: number;
            }
            TS,
            $this->directory . '/index.ts',
        );
    }

    public function testClassNamespaceChange(): void
    {
        $this->generateClasses(ClassNamespaceChange::class);

        self::assertSameCode(
            <<<TS
            export interface ClassNamespaceChange {
                readonly id: number;
            }
            TS,
            $this->directory . '/api/interface.ts',
        );
    }

    public function testClassAliasedClass(): void
    {
        $this->generateClasses([
            ClassAliasedClassHolder::class,
        ]);

        self::assertSameCode(
            <<<TS
            export interface ClassAliasedClassHolder {
                readonly property: SomeEntity;
            }
            export interface SomeEntity {
                readonly id: string;
            }
            TS,
            $this->directory . '/index.ts',
        );
    }

    public function testClassAliasedAliasedClass(): void
    {
        self::markTestSkipped("This is an edge case, but make aliasing recursive: eg. Foo -> Bar -> string");

        $this->generateClasses([
            ClassAliasedAliasedClassHolder::class,
        ]);

        self::assertSameCode(
            <<<TS
            export interface ClassAliasedAliasedClassHolder {
                readonly property: string;
            }
            TS,
            $this->directory . '/index.ts',
        );
    }

    public function testClassAliasedPrimitive(): void
    {
        $this->generateClasses([
            ClassAliasedPrimitiveHolder::class,
        ]);

        self::assertSameCode(
            <<<TS
            export interface ClassAliasedPrimitiveHolder {
                readonly property: string;
            }
            TS,
            $this->directory . '/index.ts',
        );
    }

    public function testClassIgnored(): void
    {
        $this->generateClasses([
            ClassIgnoredHolder::class,
        ]);

        self::assertSameCode(
            <<<TS
            export interface ClassIgnoredHolder {
                readonly property: any;
            }
            TS,
            $this->directory . '/index.ts',
        );
    }

    public function testPropertyNameChange(): void
    {
        $this->generateClasses(PropertyChangeName::class);

        self::assertSameCode(
            <<<TS
            export interface PropertyChangeName {
                readonly changedPropertyName?: any;
            }
            TS,
            $this->directory . '/index.ts',
        );
    }

    public function testPropertyTypeChange(): void
    {
        $this->generateClasses(PropertyTypeChange::class);

        self::assertSameCode(
            <<<TS
            export interface PropertyTypeChange {
                readonly propertyTypeChanged: string[];
            }
            TS,
            $this->directory . '/index.ts',
        );
    }

    public function testPropertyIgnore(): void
    {
        $this->generateClasses(PropertyIgnore::class);

        self::assertSameCode(
            <<<TS
            export interface PropertyIgnore {
            }
            TS,
            $this->directory . '/index.ts',
        );
    }

    public function testTypeScriptGenerator(): void
    {
        self::markTestSkipped("This was a test test.");

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

    /**
     * Assert that filename contains the expected code.
     */
    protected static function assertSameCode(string $expected, string $filename): void
    {
        if (!$data = \file_get_contents($filename)) {
            throw new \InvalidArgumentException(\sprintf("%s: filename does not exist or is empty.", $filename));
        }

        self::assertSame(self::normalize($expected), self::normalize($data));
    }

    /**
     * Generate classes with default configuration.
     */
    protected function generateClasses(array|string $classes): void
    {
        $source = new ArraySource((array) $classes);

        $generator = new Generator();

        $generator->generate(
            context: new GeneratorContext(
                configuration: new Configuration(
                    namespaceInputPrefix: 'MakinaCorpus\\ApiGenerator\\Tests\\Mock',
                    namespaceOutputPrefix: null,
                ),
            ),
            directory: $this->directory,
            source: $source,
            language: new TypeScriptLanguage(),
        );
    }

    /**
     * Normalize code (takes care of whitespace, mostly).
     */
    private static function normalize($string)
    {
        $string = \preg_replace('@\s*(\(|\))\s*@ms', '$1', $string);
        $string = \preg_replace('@\s*,\s*@ms', ',', $string);
        $string = \preg_replace('@\s+@ms', ' ', $string);
        $string = \strtolower($string);
        $string = \trim($string);

        return $string;
    }

    /**
     * Remove directory recursively.
     */
    private static function removeDirectory(string $directory)
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            if ($fileinfo->isDir()) {
                \rmdir($fileinfo->getRealPath());
            } else {
                \unlink($fileinfo->getRealPath());
            }
        }
        \rmdir($directory);
    }
}
