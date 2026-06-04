<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Model\Page;

class ReviewThreadDto
{
    public string $id;

    public bool $resolved;

    public int $startOffset;

    public int $endOffset;

    public string $selectedText;

    /** @var array<int,array<string,mixed>> */
    public array $comments = [];
}
