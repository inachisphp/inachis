<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
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
