<?php

declare (strict_types=1);

namespace MakinaCorpus\ApiGenerator\Bridge\Symfony;

use MakinaCorpus\ApiGenerator\Bridge\Symfony\DependencyInjection\ApiGeneratorExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ApiGeneratorBundle extends Bundle
{
    #[\Override]
    public function build(ContainerBuilder $container): void
    {
    }

    #[\Override]
    protected function getContainerExtensionClass(): string
    {
        return ApiGeneratorExtension::class;
    }
}
