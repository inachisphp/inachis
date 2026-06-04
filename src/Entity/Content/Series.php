<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Entity\Content;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Inachis\Entity\Media\Image;
use Inachis\Entity\User\User;
use Inachis\Enum\EditorialStatus;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;

/**
 * Object for handling {@link Series} entities
 */
#[ORM\Entity(repositoryClass: 'Inachis\Repository\SeriesRepository', readOnly: false)]
#[ORM\Index(name: 'search_idx', columns: ['title'])]
#[ORM\Index(name: "fulltext_title_content", columns: ['title', 'sub_title', 'description'], flags: ["fulltext"])]
class Series
{
    /**
     * @const string Indicates a Series is public
     */
    public const PUBLIC = true;

    /**
     * @const string Indicates a Series is private
     */
    public const PRIVATE = false;

    /**
     * @var UuidInterface|null
     */
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    protected ?UuidInterface $id = null;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    protected ?string $title = '';

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $subTitle = '';

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 255, unique: true, nullable: false)]
    protected ?string $url = '';

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $description = '';

    /**
     * @var DateTimeImmutable|null
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    protected ?DateTimeImmutable $firstDate = null;

    /**
     * @var DateTimeImmutable|null
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    protected ?DateTimeImmutable $lastDate = null;

    /**
     * @var Collection<int, Page> The array of pages in the series
     */
    #[ORM\ManyToMany(targetEntity: 'Inachis\Entity\Content\Page', fetch: 'EAGER')]
    #[ORM\JoinTable(name: 'Series_pages')]
    #[ORM\JoinColumn(name: 'series_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'page_id', referencedColumnName: 'id')]
    #[ORM\OrderBy(['postDate' => 'ASC'])]
    protected Collection $items;

    /**
     * @var Image|null
     */
    #[ORM\ManyToOne(targetEntity: 'Inachis\Entity\Media\Image', cascade: ['detach'])]
    #[ORM\JoinColumn(name: 'image_id', referencedColumnName: 'id')]
    protected ?Image $image = null;

    /**
     * @var User|null The UUID of the {@link User} that created the {@link Series}
     */
    #[ORM\ManyToOne(targetEntity: 'Inachis\Entity\User\User', cascade: [ 'detach' ])]
    #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id')]
    protected ?User $author = null;

    /**
     * @var DateTimeImmutable
     */
    #[ORM\Column(type: 'datetime_immutable')]
    protected DateTimeImmutable $createDate;


    /**
     * @var DateTimeImmutable
     */
    #[ORM\Column(type: 'datetime_immutable')]
    protected DateTimeImmutable $modDate;

    /**
     * @var bool Determining if a {@link Series} is visible to the public
     */
    #[ORM\Column(type: 'boolean', length: 20)]
    protected bool $visibility = self::PRIVATE;

    /**
     * Series constructor.
     */
    public function __construct()
    {
        $this->image = null;
        $this->items = new ArrayCollection();
        $currentTime = new DateTimeImmutable();
        $this->setCreateDate($currentTime);
        $this->setModDate($currentTime);
    }

    /**
     * Gets the value of {@link id}.
     *
     * @return UuidInterface|null
     */
    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    /**
     * Sets the value of {@link id}.
     *
     * @param UuidInterface|null $id The UUID to set
     * @return Series
     */
    public function setId(?UuidInterface $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Gets the value of {@link title}.
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Sets the value of {@link title}.
     *
     * @param string|null $title The title to set
     * @return Series
     */
    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Gets the value of {@link subTitle}.
     *
     * @return string|null
     */
    public function getSubTitle(): ?string
    {
        return $this->subTitle;
    }

    /**
     * Sets the value of {@link subTitle}.
     *
     * @param string|null $subTitle The subtitle to set
     * @return Series
     */
    public function setSubTitle(?string $subTitle = ''): self
    {
        $this->subTitle = $subTitle;
        return $this;
    }

    /**
     * Gets the value of {@link url}.
     *
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * Sets the value of {@link url}.
     *
     * @param string|null $url The URL to set
     * @return Series
     */
    public function setUrl(?string $url): self
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Gets the value of {@link description}.
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Sets the value of {@link description}.
     *
     * @param string|null $description The description to set
     * @return Series
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Gets the value of {@link firstDate}.
     *
     * @return DateTimeImmutable|null
     */
    public function getFirstDate(): ?DateTimeImmutable
    {
        return $this->firstDate;
    }

    /**
     * Sets the value of {@link firstDate}.
     *
     * @param DateTimeImmutable|null $firstDate The first date to set
     * @return Series
     */
    public function setFirstDate(?DateTimeImmutable $firstDate): self
    {
        $this->firstDate = $firstDate;
        return $this;
    }

    /**
     * Gets the value of {@link lastDate}.
     *
     * @return DateTimeImmutable|null
     */
    public function getLastDate(): ?DateTimeImmutable
    {
        return $this->lastDate;
    }

    /**
     * Sets the value of {@link lastDate}.
     *
     * @param DateTimeImmutable|null $lastDate The last date to set
     * @return Series
     */
    public function setLastDate(?DateTimeImmutable $lastDate): self
    {
        $this->lastDate = $lastDate;
        return $this;
    }

    /**
     * Gets the value of {@link items}.
     *
     * @return Collection<int, Page>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    /**
     * Sets the value of {@link items}.
     *
     * @param Collection<int, Page> $items The array of pages in the series
     *
     * @return Series
     */
    public function setItems(Collection $items): self
    {
        $this->items = $items;
        return $this;
    }

    /**
     * Adds an item to the {@link items}.
     *
     * @param Page $item The item to add
     * @return Series
     */
    public function addItem(Page $item): self
    {
        $this->items->add($item);
        return $this;
    }

    /**
     * Gets the value of {@link image}.
     *
     * @return Image|null
     */
    public function getImage(): ?Image
    {
        return $this->image;
    }

    /**
     * Sets the value of {@link image}.
     *
     * @param Image|null $image The image to set
     * @return Series
     */
    public function setImage(?Image $image = null): self
    {
        $this->image = $image;
        return $this;
    }

    /**
     * Gets the value of {@link author}.
     *
     * @return User|null The UUID of the {@link Series} author
     */
    public function getAuthor(): ?User
    {
        return $this->author;
    }

    /**
     * Sets the value of {@link author}.
     *
     * @param User|null $value The {@link User} to set as the author
     * @return Series
     */
    public function setAuthor(?User $value = null): self
    {
        $this->author = $value;
        return $this;
    }

    /**
     * Gets the value of {@link createDate}.
     *
     * @return DateTimeImmutable|null
     */
    public function getCreateDate(): ?DateTimeImmutable
    {
        return $this->createDate;
    }

    /**
     * Sets the value of {@link createDate}.
     *
     * @param DateTimeImmutable $createDate The create date to set
     * @return Series
     */
    public function setCreateDate(DateTimeImmutable $createDate): self
    {
        $this->createDate = $createDate;

        return $this;
    }

    /**
     * Gets the value of {@link modDate}.
     *
     * @return DateTimeImmutable|null
     */
    public function getModDate(): ?DateTimeImmutable
    {
        return $this->modDate;
    }

    /**
     * Sets the value of {@link modDate}.
     *
     * @param DateTimeImmutable $modDate The modification date to set
     * @return Series
     */
    public function setModDate(DateTimeImmutable $modDate): self
    {
        $this->modDate = $modDate;
        return $this;
    }

    /**
     * Gets the value of {@link visibility}.
     *
     * @return bool|null
     */
    public function getVisibility(): ?bool
    {
        return $this->visibility;
    }

    /**
     * Sets the value of {@link visibility}.
     *
     * @param bool $visibility The visibility to set
     * @return Series
     */
    public function setVisibility(bool $visibility = self::PRIVATE): self
    {
        $this->visibility = $visibility;
        return $this;
    }

    /**
     * Returns the number of public and private items in the series

     * @return array<string, int>
     */
    public function getItemVisibilityCounts(): array
    {
        $public = 0;
        $private = 0;

        foreach ($this->items as $item) {
            if (
                $item->getStatus() === EditorialStatus::PUBLISHED &&
                !$item->isScheduledPage() &&
                $item->getVisibility()
            ) {
                $public++;
            } else {
                $private++;
            }
        }

        return [
            'public' => $public,
            'private' => $private,
        ];
    }
}
