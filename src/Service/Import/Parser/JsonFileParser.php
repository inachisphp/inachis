<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Service\Import\Parser;

/**
 * File parser for JSON files.
 */
class JsonFileParser implements FileParserInterface
{
    public function supports(string $extension): bool
    {
        return 'json' === strtolower(ltrim($extension, '.'));
    }

    public function parse(string $filePath): array
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new \InvalidArgumentException(sprintf('File "%s" does not exist or is not readable.', $filePath));
        }

        $content = file_get_contents($filePath);
        if (false === $content || '' === trim($content)) {
            throw new \InvalidArgumentException('Import file is empty.');
        }

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \InvalidArgumentException('Failed to parse JSON file: ' . $e->getMessage(), 0, $e);
        }

        if (!is_array($decoded)) {
            throw new \InvalidArgumentException('JSON root must be an array or object.');
        }

        if (array_is_list($decoded)) {
            return $decoded;
        }

        return [$decoded];
    }
}
