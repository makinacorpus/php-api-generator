<?php

declare(strict_types=1);

namespace MakinaCorpus\ApiGenerator\Test\Unit;

use PHPUnit\Framework\TestCase;
use MakinaCorpus\ApiGenerator\TypeNamespace;

final class TypeNamespaceTest extends TestCase
{
    public function testRelativeThisSmaller(): void
    {
        $namespace = new TypeNamespace('foo/bar', '/');
        $other = new TypeNamespace('foo\\bar\\fizz\\buzz', '\\');

        self::assertSame('./fizz/buzz', (string) $namespace->relative($other));
    }

    public function testRelativeThisSmallerAndDifferent(): void
    {
        $namespace = new TypeNamespace('foo/bar', '/');
        $other = new TypeNamespace('foo\\fizz\\buzz', '\\');

        self::assertSame('../fizz/buzz', (string) $namespace->relative($other));
    }

    public function testRelativeOtherSmaller(): void
    {
        $namespace = new TypeNamespace('foo/bar/fizz/buzz', '/');
        $other = new TypeNamespace('foo\\bar', '\\');

        self::assertSame('../..', (string) $namespace->relative($other));
    }

    public function testRelativeOtherSmallerAndDifferent(): void
    {
        $namespace = new TypeNamespace('foo/fizz/buzz', '/');
        $other = new TypeNamespace('foo\\bar', '\\');

        self::assertSame('../../bar', (string) $namespace->relative($other));
    }

    public function testConcatSelf(): void
    {
        $namespace = new TypeNamespace('foo/bar', '/');

        self::assertSame('foo/bar/baz/bla', (string) $namespace->concat(new TypeNamespace('baz/bla', '/')));
    }

    public function testConcatString(): void
    {
        $namespace = new TypeNamespace('foo/bar', '/');

        self::assertSame('foo/bar/baz/bla', (string) $namespace->concat('baz/bla'));
    }

    public function testConcatSelfOtherSeparator(): void
    {
        $namespace = new TypeNamespace('foo/bar', '/');

        self::assertSame('foo/bar/baz/bla', (string) $namespace->concat(new TypeNamespace('baz\\bla', '\\')));
    }

    public function testShiftSelf(): void
    {
        $namespace = new TypeNamespace('foo/bar/baz', '/');

        self::assertSame('baz', (string) $namespace->shift(new TypeNamespace('foo/bar', '/')));
    }

    public function testShiftString(): void
    {
        $namespace = new TypeNamespace('foo/bar/baz', '/');

        self::assertSame('baz', (string) $namespace->shift('foo/bar'));
    }

    public function testShiftSelfOtherSeparator(): void
    {
        $namespace = new TypeNamespace('foo/bar/baz', '/');

        self::assertSame('baz', (string) $namespace->shift(new TypeNamespace('foo\\bar', '\\')));
    }

    public function testShiftNoMatch(): void
    {
        $namespace = new TypeNamespace('foo/bar/baz', '/');

        self::assertSame('foo/bar/baz', (string) $namespace->shift(new TypeNamespace('foo/buzz', '/')));
    }

    public function testUnshiftSelf(): void
    {
        $namespace = new TypeNamespace('foo/bar', '/');

        self::assertSame('baz/bla/foo/bar', (string) $namespace->unshift(new TypeNamespace('baz/bla', '/')));
    }

    public function testUnshiftString(): void
    {
        $namespace = new TypeNamespace('foo/bar', '/');

        self::assertSame('baz/bla/foo/bar', (string) $namespace->unshift('baz/bla'));
    }

    public function testUnshiftSelfOtherSeparator(): void
    {
        $namespace = new TypeNamespace('foo/bar', '/');

        self::assertSame('baz/bla/foo/bar', (string) $namespace->unshift(new TypeNamespace('baz\\bla', '\\')));
    }

    public function testConvert(): void
    {
        $namespace = new TypeNamespace('foo/bar/baz', '/');

        self::assertSame('foo\\bar\\baz', (string) $namespace->convert('\\'));
    }
}
