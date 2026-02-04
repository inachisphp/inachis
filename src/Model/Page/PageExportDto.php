<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Model\Page;

/**
 * Data Transfer Object for page export
 */
final class PageExportDto
{
    /**
     * @var string
     */
    public string $title;
    /**
     * @var string|null
     */
    public ?string $subTitle = null;
    /**
     * @var string|null
     */
    public ?string $content = null;
    /**
     * @var string
     */
    public string $type;
    /**
     * @var string
     */
    public string $status;
    /**
     * @var bool
     */
    public bool $visibility;
    /**
     * @var bool
     */
    public bool $allowComments;
    /**
     * @var string|null
     */
    public ?string $language = null;
    /**
     * @var string|null
     */
    public ?string $timezone = null;
    /**
     * @var string|null
     */
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
