<?php

declare(strict_types=1);

namespace MakinaCorpus\ApiGenerator;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class Configuration implements LoggerAwareInterface
{
    /** Prefix that will be removed from input namespaces. */
    public readonly null|TypeNamespace $namespaceInputPrefix;
    /** Prefix that will be added to output namespaces. */
    public readonly null|TypeNamespace $namespaceOutputPrefix;

    public function __construct(
        /** Prefix that will be removed from input namespaces. */
        null|string|TypeNamespace $namespaceInputPrefix = 'App',
        /** Prefix that will be added to output namespaces. */
        null|string|TypeNamespace $namespaceOutputPrefix = 'api',
        /** Ignore properties and classes with the @internal annotation. */
        public readonly bool $ignoreInternal = false,
        /** Logger for outputing debug, notice and warning messages. */
        public LoggerInterface $logger = new NullLogger(),
        /** Groups. */
        public readonly null|array $groups = null,
    ) {
        if ($namespaceInputPrefix) {
            $this->namespaceInputPrefix = \is_string($namespaceInputPrefix) ? new TypeNamespace($namespaceInputPrefix, '\\') : $namespaceInputPrefix;
        } else {
            $this->namespaceInputPrefix = null;
        }
        if ($namespaceOutputPrefix) {
            $this->namespaceOutputPrefix = \is_string($namespaceOutputPrefix) ? new TypeNamespace($namespaceOutputPrefix, '/') : $namespaceOutputPrefix;
        } else {
            $this->namespaceOutputPrefix = null;
        }
    }

    #[\Override]
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
