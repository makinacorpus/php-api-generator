<?php

declare(strict_types=1);

namespace MakinaCorpus\ApiGenerator\Tests\Mock;

use MakinaCorpus\ApiGenerator\Attribute\GeneratedTypeAlias;

#[GeneratedTypeAlias(name: SomeId::class)]
class ClassAliasedAliasedClass
{
    protected int $id;
}
