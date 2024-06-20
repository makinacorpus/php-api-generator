<?php

declare(strict_types=1);

namespace MakinaCorpus\ApiGenerator\Tests\Mock;

use MakinaCorpus\ApiGenerator\Attribute\GeneratedType;

#[GeneratedType(ignore: true)]
class ClassIgnored
{
    protected int $id;
}
