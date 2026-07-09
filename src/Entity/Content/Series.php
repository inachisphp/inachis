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
use Inachis\Entity\Traits\BidirectionalCollectionTrait;
use Inachis\Entity\User\User;
use Inachis\Enum\EditorialStatus;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;

/**
 * Object for handling {@link Series} entities
 *
 * @phpstan-type SeriesShape array{
 *     id: string,
 *     title?: string,
 *     subTitle?: string,
 *     url: string,
 *     description?: string,
 *     firstDate?: string,
 *     lastDate?: string,
 *     items: array<array{id: string}>|array{},
 *     image?: string,
 *     author?: string,
 *     createdAt: string,
 *     updatedAt: string,
 *     visible: bool
 * }
 */
#[ORM\Entity(repositoryClass: 'Inachis\Repository\Content\SeriesRepository', readOnly: false)]
#[ORM\Index(name: 'search_idx', columns: ['title'])]
#[ORM\Index(name: "fulltext_title_content", columns: ['title', 'sub_title', 'description'], flags: ["fulltext"])]
#[ORM\Index(columns: ['image_id'], name: 'series_image_idx')]
#[ORM\HasLifecycleCallbacks]
class Series
{
    use BidirectionalCollectionTrait;

    /**
     * @var UuidInterface|null
     */
    #[ORM\Id]
    #[ORM\Column(type: 'uuid_binary', unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    protected ?UuidInterface $id = null;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    protected string $title = '';

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $subTitle = '';

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 255, unique: true, nullable: false)]
    protected string $url = '';

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
    #[ORM\ManyToMany(targetEntity: Page::class, mappedBy: 'series')]
    #[ORM\OrderBy(['postDate' => 'ASC'])]
    protected Collection $items;

    /**
     * @var Image|null
     */
    #[ORM\ManyToOne(targetEntity: 'Inachis\Entity\Media\Image')]
    #[ORM\JoinColumn(name: 'image_id', referencedColumnName: 'id')]
    protected ?Image $image = null;

    /**
     * @var User|null The UUID of the {@link User} that created the {@link Series}
     */
    #[ORM\ManyToOne(targetEntity: 'Inachis\Entity\User\User')]
    #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id')]
    protected ?User $author = null;

    /**
     * @var DateTimeImmutable
     */
    #[ORM\Column(type: 'datetime_immutable')]
    protected DateTimeImmutable $createdAt;


    /**
     * @var DateTimeImmutable
     */
    #[ORM\Column(type: 'datetime_immutable')]
    protected DateTimeImmutable $updatedAt;

    /**
     * @var bool Determining if a {@link Series} is visible to the public
     */
    #[ORM\Column(type: 'boolean')]
    protected bool $visible = false;

    /**
     * Series constructor.
     */
    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new DateTimeImmutable();

        $this->createdAt ??= $now;
        $this->updatedAt ??= $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
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
     * @param UuidInterface $value The UUID of the {@link Revision}
     * @return self
     */
    public function setId(UuidInterface $value): self
    {
        $this->id = $value;
        return $this;
    }

    /**
     * Gets the value of {@link title}.
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Sets the value of {@link title}.
     *
     * @param string $title The title to set
     * @return Series
     */
    public function setTitle(string $title): self
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
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Sets the value of {@link url}.
     *
     * @param string $url The URL to set
     * @return Series
     */
    public function setUrl(string $url): self
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
     * Adds an item to the {@link items}.
     *
     * @param Page $page The item to add
     * @return Series
     */
    public function addItem(Page $page): self
    {
        $this->addBidirectional($this->items, $page, 'addSeries');
        return $this;
    }

    /**
     * Remove Page from Series
     *
     * @param Page $page
     * @return self
     */
    public function removeItem(Page $page): self
    {
        $this->removeBidirectional($this->items, $page, 'removeSeries');
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
     * Gets the value of {@link createdAt}.
     *
     * @return DateTimeImmutable
     */
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Sets the value of {@link createdAt}.
     *
     * @param DateTimeImmutable $createdAt The create date to set
     * @return Series
     */
    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Gets the value of {@link updatedAt}.
     *
     * @return DateTimeImmutable
     */
    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Sets the value of {@link updatedAt}.
     *
     * @param DateTimeImmutable $updatedAt The modification date to set
     * @return Series
     */
    public function setUpdatedAt(DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * Gets the value of {@link visible}.
     *
     * @return bool
     */
    public function isVisible(): bool
    {
        return $this->visible;
    }

    /**
     * Sets the value of {@link visible}.
     *
     * @param bool $visible The visibility to set
     * @return Series
     */
    public function setVisible(bool $visible = false): self
    {
        $this->visible = $visible;
        return $this;
    }

    /**
     * Returns result of testing if this series has Pages/Posts attached to it
     *
     * @return bool
     */
    public function hasItems(): bool
    {
        return !$this->items->isEmpty();
    }

    /**
     * Returns the number of items in this Series
     *
     * @return int
     */
    public function getItemCount(): int
    {
        return $this->items->count();
    }

    /**
     * Returns the number of public and private items in the series

     * @return array{public: int, private: int}
     */
    public function getItemVisibilityCounts(): array
    {
        $public = 0;
        $private = 0;

        foreach ($this->items as $item) {
            if (
                $item->getStatus() === EditorialStatus::PUBLISHED &&
                !$item->isScheduledPage() &&
                $item->isVisible()
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
