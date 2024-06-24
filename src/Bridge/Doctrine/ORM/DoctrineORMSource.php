<?php

declare(strict_types=1);

namespace MakinaCorpus\ApiGenerator\Bridge\Doctrine\ORM;

use Composer\InstalledVersions;
use Doctrine\ORM\Tools\Console\EntityManagerProvider;
use MakinaCorpus\ApiGenerator\Configuration;
use MakinaCorpus\ApiGenerator\Source\AbstractSource;

class DoctrineORMSource extends AbstractSource
{
    public static function checkRequirements(): bool
    {
        if (!\interface_exists(EntityManagerProvider::class)) {
            return false;
        }
        if (
            \class_exists(InstalledVersions::class) &&
            InstalledVersions::isInstalled('doctrine/orm') &&
            0 < \version_compare('3.0.0', InstalledVersions::getVersion('doctrine/orm'))
        ) {
            return false;
        }
        return true;
    }

    public function __construct(
        private EntityManagerProvider $entityManagerProvider,
    ) {}

    #[\Override]
    protected function getTypeList(Configuration $configuration): iterable
    {
        $entityManager = $this->entityManagerProvider->getDefaultManager();
        $metadataFactory = $entityManager->getMetadataFactory();

        foreach ($metadataFactory->getAllMetadata() as $metadata) {
            if ($metadata->isMappedSuperclass || $metadata->isEmbeddedClass) {
                continue;
            }

            yield $metadata->getName();
        }
    }
}
