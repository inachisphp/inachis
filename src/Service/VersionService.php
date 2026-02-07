<?php
/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */
namespace Inachis\Service;

/**
 * Returns version information about the framework
 */
final class VersionService
{
    /**
     * @var array<string, string> Current version information
     */
    private array $version;

    /**
     * @param string $versionFile Path to the version file
     */
    public function __construct(string $versionFile)
    {
        $this->version = require $versionFile;
    }

    /**
     * @return array<string, string> All version information
     */
    public function getAll(): array
    {
        return $this->version;
    }

    /**
     * @return string The version number
     */
    public function getVersion(): string
    {
        return $this->version['version'];
    }

    /**
     * @return string The commit hash
     */
    public function getCommit(): string
    {
        return $this->version['commit'];
    }

    /**
     * @return string The build date
     */
    public function getBuildDate(): string
    {
        return $this->version['build_date'];
    }
}
