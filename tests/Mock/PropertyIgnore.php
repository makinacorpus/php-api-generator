<?php

declare(strict_types=1);

namespace MakinaCorpus\ApiGenerator\Tests\Mock;

use MakinaCorpus\ApiGenerator\Attribute\GeneratedProperty;

class PropertyIgnore
{
    #[GeneratedProperty(ignore: true)]
    protected mixed $ignoredProperty;
}
