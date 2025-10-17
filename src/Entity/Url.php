<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;

/**
 * Object for handling custom URLs that are mapped to content.
 */
#[ORM\Entity(repositoryClass: 'App\Repository\UrlRepository', readOnly: false)]
#[ORM\Index(columns: [ 'linkCanonical' ], name: 'search_idx')]
class Url
{
    /**
     * @const The maximum size allowed for SEO-friendly short URLs
     */
    public const DEFAULT_URL_SIZE_LIMIT = 255;

    /**
     * @var UuidInterface|null The unique identifier for the Url
     */
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    protected ?UuidInterface $id = null;

    /**
     * @var Page The UUID of the content of the type specified by @see
     */
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Page', fetch: 'EAGER', inversedBy: 'urls')]
    #[ORM\JoinColumn(name: 'content_id', referencedColumnName: 'id')]
    protected Page $content;

    /**
     * @var string The SEO-friendly short link
     */
    #[ORM\Column(type: 'string', length: 512)]
    protected string $link;

    /**
     * @var string The canonical hash for the link
     */
    #[ORM\Column(name: 'linkCanonical', type: 'string', length: 255, unique: true)]
    protected string $linkCanonical;

    /**
     * @var bool Flag specifying if the URL is the canonical one to use
     */
    #[ORM\Column(name: 'defaultLink', type: 'boolean')]
    protected bool $default;

    /**
     * @var DateTime The date the Url was added
     */
    #[ORM\Column(type: 'datetime', nullable: false)]
    protected DateTime $createDate;

    /**
     * @var DateTime The date the Url was last modified
     */
    #[ORM\Column(type: 'datetime', nullable: false)]
    protected DateTime $modDate;

    /**
     * Default constructor for entity - by default the
     * URL will be specified as canonical. This can be overridden using
     * {@link Url::setDefault}.
     *
     * @param Page   $content The {@link Page} object the link is for
     * @param string|null $link The short link for the content
     * @param bool $default
     * @throws Exception
     */
    public function __construct(Page $content, ?string $link = '', ?bool $default = true)
    {
        $this->setContent($content);
        $this->setLink($link);
        $this->setDefault((bool) $default);
        $this->setCreateDate(new DateTime('now'));
        $this->setModDate(new DateTime('now'));
        $this->associateContent();
    }

    /**
     * Returns the UUID of the Url.
     *
     * @return string The UUID of the URL
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getLink(): string
    {
        return $this->link;
    }

    /**
     * @return string
     */
    public function getLinkCanonical(): string
    {
        return $this->linkCanonical;
    }

    /**
     * @return Page
     */
    public function getContent(): Page
    {
        return $this->content;
    }

    /**
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->default;
    }

    /**
     * @return DateTime
     */
    public function getCreateDate(): DateTime
    {
        return $this->createDate;
    }

    /**
     * @return DateTime
     */
    public function getModDate(): DateTime
    {
        return $this->modDate;
    }

    /**
     * @param UuidInterface $value
     * @return $this
     */
    public function setId(UuidInterface $value): self
    {
        $this->id = $value;
        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setLink(string $value): self
    {
        $this->link = $value;
        $this->linkCanonical = md5($value);
        return $this;
    }

    /**
     * @param Page $value
     * @return $this
     */
    public function setContent(Page $value): self
    {
        $this->content = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDefault($value): self
    {
        $this->default = (bool) $value;
        return $this;
    }

    /**
     * @param DateTime $value
     * @return $this
     */
    public function setCreateDate(DateTime $value): self
    {
        $this->createDate = $value;
        return $this;
    }

    /**
     * @param DateTime $value
     * @return $this
     */
    public function setModDate(DateTime $value): self
    {
        $this->modDate = $value;
        return $this;
    }

    /**
     * Sets the mod date for the {@link Url} to the current date.
     */
    public function setModDateToNow(): self
    {
        $this->setModDate(new DateTime('now'));
        return $this;
    }

    /**
     * Test if the current link is a valid SEO-friendly URL.
     *
     * @return bool The result of validating if the SEO friendly short URL
     *              contains only alphanumeric values and hyphens
     */
    public function validateURL(): bool
    {
        return preg_match('/^[a-z0-9\-]+$/i', $this->link) === 1;
    }

    /**
     * @return void
     */
    public function associateContent(): void
    {
        $this->content->addUrl($this);
    }
}
