<?php
/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

final readonly class ManifestLoader
{
    /**
     * Constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    /**
     * Loads and parses a YAML manifest.
     *
     * Returns null if the file does not exist, cannot be parsed, or does
     * not contain a valid YAML object.
     *
     * @param string $filename
     * @return array<mixed>|null
     */
    public function load(string $filename): ?array
    {
        if (!is_file($filename)) {
            return null;
        }

        try {
            $manifest = Yaml::parseFile(
                $filename,
                Yaml::PARSE_EXCEPTION_ON_ALIAS
            );
        } catch (ParseException $exception) {
            $this->logger->warning(sprintf(
                'Failed to parse manifest "%s": %s',
                $filename,
                $exception->getMessage()
            ));

            return null;
        }

        if (!is_array($manifest)) {
            return null;
        }

        return $manifest;
    }
}
