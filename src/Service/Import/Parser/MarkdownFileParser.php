<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Service\Import\Parser;

/**
 * File parser for Markdown (.md, .markdown) files.
 */
class MarkdownFileParser implements FileParserInterface
{
    public function supports(string $extension): bool
    {
        return in_array(strtolower(ltrim($extension, '.')), ['md', 'markdown'], true);
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

        $item = [
            'type' => 'post',
            'title' => '',
            'subTitle' => null,
            'content' => '',
            'categories' => [],
            'tags' => [],
            'postDate' => null,
        ];

        // Check for YAML Front Matter
        if (preg_match('/^---\s*\R(.*?)\R---\s*\R(.*)$/s', $content, $matches)) {
            $frontMatterRaw = $matches[1];
            $item['content'] = trim($matches[2]);

            $lines = preg_split('/\R/', $frontMatterRaw);
            if (is_array($lines)) {
                foreach ($lines as $line) {
                    if (str_contains($line, ':')) {
                        [$key, $val] = explode(':', $line, 2);
                        $key = strtolower(trim($key));
                        $val = trim($val);

                        if ((str_starts_with($val, '"') && str_ends_with($val, '"')) || (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
                            $val = substr($val, 1, -1);
                        }

                        match ($key) {
                            'title' => $item['title'] = $val,
                            'subtitle' => $item['subTitle'] = $val,
                            'date', 'postdate' => $item['postDate'] = $val,
                            'category', 'categories' => $item['categories'] = array_filter(array_map('trim', explode(',', trim($val, '[]')))),
                            'tag', 'tags' => $item['tags'] = array_filter(array_map('trim', explode(',', trim($val, '[]')))),
                            default => null,
                        };
                    }
                }
            }
        } else {
            $lines = preg_split('/\R/', trim($content));
            if (!$lines || count($lines) < 1) {
                throw new \InvalidArgumentException('Invalid markdown format.');
            }

            $offset = 0;
            if (preg_match('/^# (.+)$/', $lines[0], $m)) {
                $item['title'] = trim($m[1]);
                $offset = 1;
            }
            if (isset($lines[1]) && preg_match('/^## (.+)$/', $lines[1], $m)) {
                $item['subTitle'] = trim($m[1]);
                $offset = 2;
            }
            $item['content'] = trim(implode("\n", array_slice($lines, $offset)));
        }

        if (empty($item['title'])) {
            $item['title'] = pathinfo($filePath, PATHINFO_FILENAME);
        }

        return [$item];
    }
}
