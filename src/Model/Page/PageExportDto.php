<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Model\Page;

/**
 * Data Transfer Object for page export.
 */
final class PageExportDto
{
    public string $title;
    public ?string $subTitle = null;
    public ?string $content = null;
    public string $type;
    public string $status;
    public bool $visible;
    public bool $allowComments;
    public ?string $language = null;
    public ?string $timezone = null;
    public ?string $postDate = null;

    /**
     * @var CategoryPathDto[]
     */
    public array $categories = [];

    /**
     * @var TagDto[]
     */
    public array $tags = [];

    /**
     * @var UrlDto[]
     */
    public array $urls = [];
}
