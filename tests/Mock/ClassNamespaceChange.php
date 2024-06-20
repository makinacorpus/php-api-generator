<?php

declare(strict_types=1);

namespace MakinaCorpus\ApiGenerator\Tests\Mock;

use MakinaCorpus\ApiGenerator\Attribute\GeneratedType;

#[GeneratedType(namespace: 'api/interface')]
class ClassNamespaceChange
{
    protected int $id;
}
