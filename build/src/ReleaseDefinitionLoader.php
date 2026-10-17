<?php
/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Build;

use RuntimeException;

final readonly class ReleaseDefinitionLoader
{
    public function __construct(
        private string $filename,
    ) {
    }

    public function load(): ReleaseDefinition
    {
        if (!is_file($this->filename)) {
            throw new RuntimeException(sprintf(
                'Release definition "%s" was not found.',
                $this->filename
            ));
        }

        $json = file_get_contents($this->filename);

        if ($json === false) {
            throw new RuntimeException(sprintf(
                'Unable to read "%s".',
                $this->filename
            ));
        }

        try {
            /** @var mixed $data */
            $data = json_decode(
                $json,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException $exception) {
            throw new RuntimeException(
                sprintf(
                    'Invalid release definition: %s',
                    $exception->getMessage()
                ),
                previous: $exception
            );
        }

        if (!is_array($data) || !isset($data['name']) || !is_string($data['name'])) {
            throw new RuntimeException('Release definition is missing "name".');
        }

        if (!isset($data['contents']) || !is_array($data['contents'])) {
            throw new RuntimeException('Release definition is missing "contents".');
        }

        $contents = [];

        foreach ($data['contents'] as $entry) {
            if (!is_array($entry) || !isset($entry['type'], $entry['path'])) {
                throw new RuntimeException(
                    'Every release entry must define "type" and "path".'
                );
            }

            $type = is_string($entry['type']) || is_int($entry['type']) ? $entry['type'] : '';
            $path = is_string($entry['path']) ? $entry['path'] : '';

            $contents[] = new ReleaseEntry(
                type: ReleaseEntryType::from($type),
                path: $path,
                optional: (bool) ($entry['optional'] ?? false),
            );
        }

        $composer = is_array($data['composer'] ?? null) ? $data['composer'] : [];

        /** @var array<mixed> $rawPersistent */
        $rawPersistent = is_array($data['persistent'] ?? null) ? $data['persistent'] : [];
        $persistent = array_values(array_filter($rawPersistent, 'is_string'));

        /** @var array<mixed> $rawCommands */
        $rawCommands = is_array($data['commands'] ?? null) ? $data['commands'] : [];
        $commands = array_values(array_filter($rawCommands, 'is_string'));

        return new ReleaseDefinition(
            name: $data['name'],
            contents: $contents,
            persistent: $persistent,
            commands: $commands,
            composerInstall: (bool) ($composer['install'] ?? true),
            composerNoDev: (bool) ($composer['noDev'] ?? true),
            composerOptimizeAutoloader: (bool) ($composer['optimizeAutoloader'] ?? true),
        );
    }
}
