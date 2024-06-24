<?php

declare(strict_types=1);

namespace MakinaCorpus\ApiGenerator\Bridge\Symfony\DependencyInjection;

use MakinaCorpus\ApiGenerator\Bridge\Doctrine\ORM\DoctrineORMSource;
use MakinaCorpus\ApiGenerator\Configuration;
use MakinaCorpus\ApiGenerator\Source\ArraySource;
use MakinaCorpus\ApiGenerator\Source\FinderSource;
use MakinaCorpus\ApiGenerator\Source\SourceChain;
use MakinaCorpus\ApiGenerator\Source\SourceConfiguration;
use MakinaCorpus\ApiGenerator\Source\SourceConfigurationRegistry;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class ApiGeneratorExtension extends Extension
{
    #[\Override]
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(\dirname(__DIR__).'/Resources/config'));
        $loader->load('services.yaml');

        $sourceConfigs = [];
        if (isset($config['defaults']['sources'])) {
            // @phpstan-ignore-next-line
            $sourceConfigs['default'] = new Reference($this->registerConfiguration($container, $config['defaults'] ?? []));
        }
        foreach (($config['targets'] ?? []) as $name => $options) {
            $sourceConfigs[$name] = new Reference($this->registerConfiguration($container, $options, $name));
        }

        $definition = new Definition();
        $definition->setClass(SourceConfigurationRegistry::class);
        $definition->setArgument('$instances', $sourceConfigs);
        $container->setDefinition('api_generator.source.registry', $definition);
    }

    private function registerConfiguration(ContainerBuilder $container, array $config, ?string $name = null): string
    {
        $path = $name ? 'api_generator.targets.' . $name : 'api_generator.defaults';
        $serviceId = 'api_generator.source' . ($name ? '.' . $name : '');

        $definition = new Definition();
        $definition->setClass(Configuration::class);
        $definition->setArguments([
            '$groups' => $this->validateGroups($config['groups'] ?? null, $path . '.groups'),
            '$ignoreInternal' => $config['ignore_internal'] ?? true,
            // '$logger' => '', @todo
            '$namespaceInputPrefix' => $config['namespace_prefix_input'] ?? null,
            '$namespaceOutputPrefix' => $config['namespace_prefix_output'] ?? null,
        ]);
        $container->setDefinition($serviceId . '.configuration', $definition);

        if (empty($config['directory'])) {
            throw new InvalidArgumentException(\sprintf('"%s.directory" is missing.', $path));
        }
        $directory = $container->getParameterBag()->resolveValue($config['directory']);
        if (!\is_dir($directory)) {
            // @todo This is a bad idea to fail upon this.
            // throw new InvalidArgumentException(\sprintf('"%s.directory": %s: directory does not exist.', $path, $directory));
        }

        $definition = new Definition();
        $definition->setClass(SourceConfiguration::class);
        $definition->setArguments([
            '$configuration' => new Reference($serviceId . '.configuration'),
            '$directory' => $directory,
            '$language' => new Reference($this->getLanguageServiceId($container, $config['language'] ?? 'typescript')),
            '$source' => new Reference($this->registerSources($container, $config['sources'] ?? null, $name)),
        ]);
        $container->setDefinition($serviceId, $definition);

        return $serviceId;
    }

    private function registerSources(ContainerBuilder $container, ?array $config, ?string $name): string
    {
        $path = $name ? 'api_generator.targets.' . $name . '.source' : 'api_generator.defaults.source';
        $serviceId = 'api_generator.source' . ($name ? '.' . $name : '') . '.source';

        // Attempt automatic detection.
        if (empty($config)) {
            if (DoctrineORMSource::checkRequirements()) {
                $config = [['type' => 'doctrine']];
            } else {
                throw new InvalidArgumentException(\sprintf("'%s': could not determine a default source.", $path));
            }
        }

        $services = [];
        foreach ($config as $index => $childConfig) {
            $services[] = new Reference($this->registerSource($container, $childConfig, $path, $serviceId, $index));
        }

        if (1 === \count($services)) {
            return (string) $services[0]->__toString();
        }

        $definition = new Definition();
        $definition->setClass(SourceChain::class);
        $definition->setArguments([$services]);
        $container->setDefinition($serviceId, $definition);

        return $serviceId;
    }

    private function registerSource(ContainerBuilder $container, mixed $config, string $path, string $parentId, int $index): string
    {
        $path = $path . '.' . $index;
        $serviceId = $parentId . '.' . $index;

        if (!isset($config['type'])) {
            throw new InvalidArgumentException(\sprintf("'%s': 'type' is missing.", $path));
        }

        $arguments = [];
        switch ($config['type']) {
            case 'doctrine':
                if (!DoctrineORMSource::checkRequirements()) {
                    throw new InvalidArgumentException(\sprintf('"%s": doctrine/orm requirement checks have failed, please ensure at least version 3.0.0 is installed.', $path));
                }
                $className = DoctrineORMSource::class;
                $arguments = [
                    '$entityManagerProvider' => new Reference('doctrine.orm.command.entity_manager_provider'),
                ];
                break;

            case 'array':
                if (empty($config['classes'])) {
                    throw new InvalidArgumentException(\sprintf('"%s": "classes" option is missing.', $path));
                }

                $className = ArraySource::class;
                $arguments = [
                    '$classNames' => \array_values(\array_unique($config)),
                ];
                break;

            case 'finder':
                if (empty($config['directory'])) {
                    throw new InvalidArgumentException(\sprintf('"%s": "directory" option is missing.', $path));
                }

                $className = FinderSource::class;
                $arguments = [
                    '$directory' => $config['directory'],
                ];
        }

        $definition = new Definition();
        // @phpstan-ignore-next-line
        $definition->setClass($className);
        $definition->setArguments($arguments);
        $container->setDefinition($serviceId, $definition);

        return $serviceId;
    }

    private function getLanguageServiceId(ContainerBuilder $container, string $language): string
    {
        return match ($language) {
            'typescript' => 'api_generator.language.typescript',
            default => throw new InvalidArgumentException(\sprintf('Invalid language "%s", expected one of "typescript', $language)),
        };
    }

    private function validateGroups(mixed $value, string $path): ?array
    {
        if (empty($value)) {
            return null;
        }

        if (\is_string($value)) {
            $value = [$value];
        }

        if (!\is_array($value)) {
            throw new InvalidArgumentException(\sprintf('"%s": expected "string" or "array", got "%s".', $path, \get_debug_type($value)));
        }

        foreach ($value as $index => $group) {
            if (!\is_string($group)) {
                throw new InvalidArgumentException(\sprintf('"%s.%s": expected "string", got "%s".', $path, $index, \get_debug_type($value)));
            }
        }

        return \array_values(\array_unique($value));
    }

    #[\Override]
    public function getConfiguration(array $config, ContainerBuilder $container): ?ConfigurationInterface
    {
        return new ApiGeneratorConfiguration();
    }
}
