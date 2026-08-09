<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Content\Page;

use Inachis\Enum\DiffBlockType;
use Inachis\Model\Page\DiffBlock;

class RevisionDiffRenderer
{
    /**
     * Converts the content array currently produced by RevisionController
     * into a Twig-friendly collection of DiffBlock DTOs.
     *
     * @param list<array{
     *     tag: string,
     *     old: array{offset: int, lines:list<string>},
     *     new: array{offset: int, lines:list<string>}
     * }|array{}|string> $content
     *
     * @return list<DiffBlock>
     */
    public function render(array $content): array
    {
        $blocks = [];
        $skipLines = 0;

        foreach ($content as $contentBlock) {
            if ($skipLines > 0) {
                --$skipLines;
                continue;
            }

            if (!is_array($contentBlock) || !isset($contentBlock['tag'])) {
                if (!is_string($contentBlock)) {
                    continue;
                }

                $blocks[] = new DiffBlock(
                    DiffBlockType::UNCHANGED,
                    $contentBlock,
                );

                continue;
            }

            $tag = (string) $contentBlock['tag'];

            switch ($tag) {
                case 'del':
                    $oldLines = $this->extractLines(
                        $contentBlock,
                        'old',
                    );

                    $skipLines = max(0, count($oldLines) - 1);

                    $blocks[] = new DiffBlock(
                        DiffBlockType::DELETED,
                        implode(PHP_EOL, $oldLines),
                    );

                    break;

                case 'rep':
                    $oldLines = $this->extractLines($contentBlock, 'old');
                    $newLines = $this->extractLines($contentBlock, 'new');

                    $skipLines = max(0, count($oldLines) - 1);

                    $old = $this->stripHtml(implode(PHP_EOL, $oldLines));
                    $new = $this->stripHtml(implode(PHP_EOL, $newLines));

                    $blocks[] = new DiffBlock(
                        DiffBlockType::REPLACED,
                        $this->renderInlineDiff($old, $new),
                    );

                    break;
            }
        }

        return $blocks;
    }

    /**
     * @param array<string, mixed> $change
     *
     * @return list<string>
     */
    private function extractLines(
        array $change,
        string $side,
    ): array {
        if (
            !isset($change[$side])
            || !is_array($change[$side])
            || !isset($change[$side]['lines'])
            || !is_array($change[$side]['lines'])
        ) {
            return [];
        }

        return array_values(
            array_filter(
                $change[$side]['lines'],
                static fn (mixed $line): bool => is_string($line),
            ),
        );
    }

    /**
     * Render inline diff between two strings.
     */
    public function renderInlineDiff(string $old, string $new): string
    {
        $oldTokens = $this->tokenize($old);
        $newTokens = $this->tokenize($new);

        $ops = $this->diff($oldTokens, $newTokens);

        $html = '';

        foreach ($ops as [$type, $text]) {
            $safe = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            switch ($type) {
                case 'insert':
                    $html .= '<ins class="diff diff--inserted">'.$safe.'</ins>';
                    break;

                case 'delete':
                    $html .= '<del class="diff diff--deleted">'.$safe.'</del>';
                    break;

                default:
                    $html .= $safe;
                    break;
            }
        }

        return $html;
    }

    /**
     * Very simple tokenizer (word, punctuation, and newline aware for CMS text).
     *
     * @return list<string>
     */
    private function tokenize(string $text): array
    {
        // 1. Normalize line endings first, but DO NOT remove them
        $text = str_replace("\r\n", "\n", $text);

        // 2. Match words, punctuation, newlines specifically (\n), or other standard horizontal spaces
        preg_match_all('/\w+|\n|[^\w\s]|[ \t]+/u', $text, $matches);

        return $matches[0];
    }

    /**
     * A true, memory-efficient Myers Diff algorithm.
     * Uses a flat array of integers tracking diagonal paths.
     * Memory complexity: O(D) where D is the edit script length (extremely small).
     *
     * @param list<string> $a
     * @param list<string> $b
     *
     * @return list<array{0:string,1:string}>
     */
    private function diff(array $a, array $b): array
    {
        $n = count($a);
        $m = count($b);

        // Shortcut for empty sides
        if (0 === $n) {
            return array_map(static fn ($token) => ['insert', $token], $b);
        }
        if (0 === $m) {
            return array_map(static fn ($token) => ['delete', $token], $a);
        }

        $max = $n + $m;
        $v = [1 => 0];
        $trace = [];

        for ($d = 0; $d <= $max; ++$d) {
            for ($k = -$d; $k <= $d; $k += 2) {
                if ($k === -$d || ($k !== $d && ($v[$k - 1] ?? -1) < ($v[$k + 1] ?? -1))) {
                    $x = $v[$k + 1] ?? 0;
                } else {
                    $x = ($v[$k - 1] ?? 0) + 1;
                }

                $y = $x - $k;

                while ($x < $n && $y < $m && $a[$x] === $b[$y]) {
                    ++$x;
                    ++$y;
                }

                $v[$k] = $x;

                if ($x >= $n && $y >= $m) {
                    $trace[] = $v;
                    break 2;
                }
            }
            $trace[] = $v;
        }

        // Backtrack to build the edit script
        $ops = [];
        $x = $n;
        $y = $m;

        for ($d = count($trace) - 1; $d >= 0; --$d) {
            $v = $trace[$d];
            $k = $x - $y;

            if ($k === -$d || ($k !== $d && ($v[$k - 1] ?? -1) < ($v[$k + 1] ?? -1))) {
                $prevK = $k + 1;
            } else {
                $prevK = $k - 1;
            }

            $prevX = $v[$prevK] ?? 0;
            $prevY = $prevX - $prevK;

            while ($x > $prevX && $y > $prevY) {
                $ops[] = ['equal', $a[$x - 1]];
                --$x;
                --$y;
            }

            if ($d > 0) {
                if ($x > $prevX) {
                    $ops[] = ['delete', $a[$x - 1]];
                    --$x;
                } elseif ($y > $prevY) {
                    $ops[] = ['insert', $b[$y - 1]];
                    --$y;
                }
            }
        }

        return array_reverse($ops);
    }

    private function stripHtml(string $text): string
    {
        return html_entity_decode(
            strip_tags($text),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8',
        );
    }
}
