<?php

declare(strict_types=1);

namespace MakinaCorpus\ApiGenerator\Tests\Mock;

use MakinaCorpus\ApiGenerator\Attribute\GeneratedProperty;

class PropertyTypeChange
{
    #[GeneratedProperty(type: 'string', collection: true, nullable: false)]
    protected mixed $propertyTypeChanged;
}
