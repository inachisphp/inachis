<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Model\Page;

final class PageExportDto
{
    public string $title;
    public ?string $subTitle = null;
    public ?string $content = null;
    public string $type;
    public string $status;
    public bool $visibility;
    public bool $allowComments;
    public ?string $language = null;
    public ?string $timezone = null;
    public ?string $postDate = null;

    /** @var CategoryPathDto[] */
    public array $categories = [];

    /** @var TagDto[] */
    public array $tags = [];

    /** @var UrlDto[] */
    public array $urls = [];
}