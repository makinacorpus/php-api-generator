<?php

declare(strict_types=1);

namespace MakinaCorpus\ApiGenerator\Tests\Unit;

use MakinaCorpus\ApiGenerator\Configuration;
use MakinaCorpus\ApiGenerator\Source\TypeAliasRegistry;
use MakinaCorpus\ApiGenerator\Tests\Mock\SomeEntity;
use MakinaCorpus\ApiGenerator\Tests\Mock\SomeId;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Uid\AbstractUid;
use Symfony\Component\Uid\Ulid;

final class TypeAliasRegistryTest extends TestCase
{
    public function testGeneratedTypeAliasAttribute(): void
    {
        $instance = new TypeAliasRegistry();
        $configuration = new Configuration();

        self::assertSame('string', $instance->getTypeAlias($configuration, SomeId::class));
    }

    public function testNoAlias(): void
    {
        $instance = new TypeAliasRegistry();
        $configuration = new Configuration();

        self::assertNull($instance->getTypeAlias($configuration, SomeEntity::class));
    }

    public function testWellKnown(): void
    {
        $instance = new TypeAliasRegistry();
        $configuration = new Configuration();

        self::assertSame('string', $instance->getTypeAlias($configuration, \DateTimeImmutable::class));
        self::assertSame('string', $instance->getTypeAlias($configuration, \DateTimeInterface::class));
        self::assertSame('string', $instance->getTypeAlias($configuration, AbstractUid::class));
        self::assertSame('string', $instance->getTypeAlias($configuration, Ulid::class));
        self::assertSame('string', $instance->getTypeAlias($configuration, Uuid::class));
        self::assertSame('string', $instance->getTypeAlias($configuration, UuidInterface::class));
    }
}
