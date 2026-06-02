<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Entity;

use DateTimeImmutable;
use Exception;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;

/**
 * Object for handling custom URLs that are mapped to content.
 */
#[ORM\Entity(repositoryClass: 'Inachis\Repository\UrlRepository', readOnly: false)]
#[ORM\Index(columns: ['linkCanonical'], name: 'search_idx')]
class Url
{
    /**
     * @const The maximum size allowed for SEO-friendly short URLs
     */
    public const DEFAULT_URL_SIZE_LIMIT = 255;

    /**
     * @var UuidInterface The unique identifier for the Url
     */
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    protected UuidInterface $id;

    /**
     * @var Page The UUID of the content of the type specified by @see
     */
    #[ORM\ManyToOne(targetEntity: 'Inachis\Entity\Page', fetch: 'EAGER', inversedBy: 'urls')]
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
     * @var DateTimeImmutable The date the Url was added
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: false)]
    protected DateTimeImmutable $createDate;

    /**
     * @var DateTimeImmutable The date the Url was last modified
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: false)]
    protected DateTimeImmutable $modDate;

    /**
     * Default constructor for entity - by default the
     * URL will be specified as canonical. This can be overridden using
     * {@link Url::setDefault}.
     *
     * @param Page $content The {@link Page} object the link is for
     * @param string $link The short link for the content
     * @param bool $default
     * @throws Exception
     */
    public function __construct(Page $content, string $link = '', bool $default = true)
    {
        $this->setContent($content);
        $this->setLink($link);
        $this->setDefault($default);
        $this->setCreateDate(new DateTimeImmutable());
        $this->setModDate(new DateTimeImmutable());
        $this->associateContent();
    }

    /**
     * Returns the UUID of the Url.
     *
     * @return UuidInterface The UUID of the URL
     */
    public function getId(): UuidInterface
    {
        return $this->id;
    }

    /**
     * Returns the value of {@link link}.
     *
     * @return string The value of {@link link}
     */
    public function getLink(): string
    {
        return $this->link;
    }

    /**
     * Returns the URL path used for sitemap generation.
     */
    public function getPath(): string
    {
        return '/' . ltrim($this->link, '/');
    }

    /**
     * Returns the value of {@link linkCanonical}.
     *
     * @return string The value of {@link linkCanonical}
     */
    public function getLinkCanonical(): string
    {
        return $this->linkCanonical;
    }

    /**
     * Returns the value of {@link content}.
     *
     * @return Page The value of {@link content}
     */
    public function getContent(): Page
    {
        return $this->content;
    }

    /**
     * Returns the value of {@link default}.
     *
     * @return bool The value of {@link default}
     */
    public function isDefault(): bool
    {
        return $this->default;
    }

    /**
     * Returns the value of {@link createDate}.
     *
     * @return DateTimeImmutable The value of {@link createDate}
     */
    public function getCreateDate(): DateTimeImmutable
    {
        return $this->createDate;
    }

    /**
     * Returns the value of {@link modDate}.
     *
     * @return DateTimeImmutable The value of {@link modDate}
     */
    public function getModDate(): DateTimeImmutable
    {
        return $this->modDate;
    }

    /**
     * Sets the value of {@link id}.
     *
     * @param UuidInterface $value The value to set
     * @return $this
     */
    public function setId(UuidInterface $value): self
    {
        $this->id = $value;
        return $this;
    }

    /**
     * Sets the value of {@link link}.
     *
     * @param string $value The value to set
     * @return $this
     */
    public function setLink(string $value): self
    {
        $this->link = $value;
        $this->linkCanonical = md5($value);
        return $this;
    }

    /**
     * Sets the value of {@link content}.
     *
     * @param Page $value The value to set
     * @return $this
     */
    public function setContent(Page $value): self
    {
        $this->content = $value;
        return $this;
    }

    /**
     * Sets the value of {@link default}.
     *
     * @param bool $value The value to set
     * @return $this
     */
    public function setDefault(bool $value): self
    {
        $this->default = $value;
        return $this;
    }

    /**
     * Sets the value of {@link createDate}.
     *
     * @param DateTimeImmutable $value The value to set
     * @return $this
     */
    public function setCreateDate(DateTimeImmutable $value): self
    {
        $this->createDate = $value;
        return $this;
    }

    /**
     * Sets the value of {@link modDate}.
     *
     * @param DateTimeImmutable $value The value to set
     * @return $this
     */
    public function setModDate(DateTimeImmutable $value): self
    {
        $this->modDate = $value;
        return $this;
    }

    /**
     * Sets the mod date for the {@link Url} to the current date.
     *
     * @return $this
     */
    public function setModDateToNow(): self
    {
        $this->setModDate(new DateTimeImmutable());
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
     * Associates the {@link Url} with the {@link Page}.
     *
     * @return void
     */
    public function associateContent(): void
    {
        $this->content->addUrl($this);
    }
}
