<?php

declare(strict_types=1);

namespace MakinaCorpus\ApiGenerator\Command;

use MakinaCorpus\ApiGenerator\Generator;
use MakinaCorpus\ApiGenerator\GeneratorContext;
use MakinaCorpus\ApiGenerator\Source\SourceConfigurationRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'api-generator:generate', description: 'Generate API bindings/interfaces')]
final class GenerateCommand extends Command
{
    public function __construct(
        private SourceConfigurationRegistry $sourceConfigurationRegistry,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->addArgument('target', InputArgument::OPTIONAL, 'Source name as found in your configuration.', 'default');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sourceConfiguration = $this->sourceConfigurationRegistry->get($input->getArgument('target'));

        $sourceConfiguration->configuration->setLogger(new ConsoleLogger($output));

        $generator = new Generator();
        $generator->generate(
            context: new GeneratorContext(configuration: $sourceConfiguration->configuration),
            directory: $sourceConfiguration->directory,
            language: $sourceConfiguration->language,
            source: $sourceConfiguration->source,
        );

        return self::SUCCESS;
    }
}
