<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Image\Migration;

class MarkdownImageRewriter
{
    /**
     * Replace `/imgs/old-file.ext` with `/imgs/new-file.ext` strictly preserving boundaries and query strings.
     *
     * @param array<string, string> $primaryMap
     * @param array<string, string> $secondaryMap
     */
    public function rewriteContent(
        string $content,
        array $primaryMap,
        array $secondaryMap = []
    ): string {
        $combinedMap = array_merge($secondaryMap, $primaryMap);

        foreach ($combinedMap as $oldFile => $newFile) {
            if ($oldFile === $newFile) {
                continue;
            }
            $pattern = '~(?<=^|[\s"\'()<>\[\]])\/imgs\/' . preg_quote($oldFile, '~') . '(?=[\s"\'()<>\[\]\?#]|$)~';
            $content = (string) preg_replace($pattern, '/imgs/' . $newFile, $content);
        }

        return $content;
    }

    /**
     * Extract image filenames referenced in markdown or HTML content.
     *
     * @return list<string>
     */
    public function extractImageReferences(?string $content): array
    {
        if (empty($content)) {
            return [];
        }

        preg_match_all('~/imgs/([a-zA-Z0-9_\-\.]+\.[a-zA-Z0-9]{3,4})(?:[\?#][^\s"\'()<>\[\]]*)?~', $content, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }
}
