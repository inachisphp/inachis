<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Content\Page;

use Inachis\Enum\DiffBlockType;
use Inachis\Model\Page\DiffBlock;

final readonly class RevisionDiffRenderer
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
                        'old'
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
                static fn (mixed $line): bool => is_string($line)
            )
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
                    $html .= '<ins class="diff diff--inserted">' . $safe . '</ins>';
                    break;

                case 'delete':
                    $html .= '<del class="diff diff--deleted">' . $safe . '</del>';
                    break;

                default:
                    $html .= $safe;
                    break;
            }
        }

        return $html;
    }

    /**
     * Very simple tokenizer (word + punctuation aware enough for CMS text).
     *
     * @return list<string>
     */
    private function tokenize(string $text): array
    {
        $text = str_replace(["\n", "\r"], ' ', $text);

        preg_match_all('/\w+|[^\w\s]|\s+/u', $text, $matches);

        return $matches[0];
    }

    /**
     * Myers-style fallback diff (simplified LCS-based implementation).
     *
     * @param list<string> $a
     * @param list<string> $b
     * @return list<array{0:string,1:string}>
     */
    private function diff(array $a, array $b): array
    {
        $matrix = [];
        $maxA = count($a);
        $maxB = count($b);

        for ($i = 0; $i <= $maxA; $i++) {
            $matrix[$i][0] = $i;
        }

        for ($j = 0; $j <= $maxB; $j++) {
            $matrix[0][$j] = $j;
        }

        for ($i = 1; $i <= $maxA; $i++) {
            for ($j = 1; $j <= $maxB; $j++) {
                if ($a[$i - 1] === $b[$j - 1]) {
                    $matrix[$i][$j] = $matrix[$i - 1][$j - 1];
                } else {
                    $matrix[$i][$j] = min(
                        $matrix[$i - 1][$j] + 1,
                        $matrix[$i][$j - 1] + 1,
                        $matrix[$i - 1][$j - 1] + 1
                    );
                }
            }
        }

        $ops = [];
        $i = $maxA;
        $j = $maxB;

        while ($i > 0 || $j > 0) {
            if ($i > 0 && $j > 0 && $a[$i - 1] === $b[$j - 1]) {
                $ops[] = ['equal', $a[$i - 1]];
                $i--;
                $j--;
            } elseif (
                $j > 0 &&
                ($i === 0 || $matrix[$i][$j - 1] <= $matrix[$i - 1][$j])
            ) {
                $ops[] = ['insert', $b[$j - 1]];
                $j--;
            } else {
                $ops[] = ['delete', $a[$i - 1]];
                $i--;
            }
        }

        return array_reverse($ops);
    }

    private function stripHtml(string $text): string
    {
        return html_entity_decode(
            strip_tags($text),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );
    }
}
