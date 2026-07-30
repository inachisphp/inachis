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
            /** @var array{name:string,contents:list<array<string,mixed>>} $data */
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

        if (!isset($data['name']) || !is_string($data['name'])) {
            throw new RuntimeException('Release definition is missing "name".');
        }

        if (!isset($data['contents']) || !is_array($data['contents'])) {
            throw new RuntimeException('Release definition is missing "contents".');
        }

        $contents = [];

        foreach ($data['contents'] as $entry) {
            if (!isset($entry['type'], $entry['path'])) {
                throw new RuntimeException(
                    'Every release entry must define "type" and "path".'
                );
            }

            $contents[] = new ReleaseEntry(
                type: ReleaseEntryType::from($entry['type']),
                path: $entry['path'],
                optional: (bool)($entry['optional'] ?? false),
            );
        }

        return new ReleaseDefinition(
            name: $data['name'],
            contents: $contents,
        );
    }
}
