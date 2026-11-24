<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Service\Import\Parser;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Registry for file parsers used during import.
 */
class FileParserRegistry
{
    /**
     * @var list<FileParserInterface>
     */
    private array $parsers = [];

    /**
     * @param iterable<FileParserInterface> $parsers
     */
    public function __construct(
        #[AutowireIterator('inachis.file_parser')]
        iterable $parsers = []
    ) {
        foreach ($parsers as $parser) {
            $this->parsers[] = $parser;
        }
    }

    /**
     * Returns a parser capable of parsing files with the given extension.
     *
     * @throws \InvalidArgumentException If no parser supports the extension
     */
    public function getParserForExtension(string $extension): FileParserInterface
    {
        $extension = strtolower(ltrim($extension, '.'));

        foreach ($this->parsers as $parser) {
            if ($parser->supports($extension)) {
                return $parser;
            }
        }

        throw new \InvalidArgumentException(sprintf('No parser found for file extension "%s".', $extension));
    }
}
