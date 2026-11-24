<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Service\Import\Parser;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Interface for file parsers used during import.
 */
#[AutoconfigureTag('inachis.file_parser')]
interface FileParserInterface
{
    /**
     * Checks if the parser supports the given file extension.
     */
    public function supports(string $extension): bool;

    /**
     * Parses the file at the given path into an array of associative item arrays.
     *
     * @param string $filePath Absolute or relative path to the file to parse
     *
     * @return list<array<string, mixed>> List of parsed items
     */
    public function parse(string $filePath): array;
}
