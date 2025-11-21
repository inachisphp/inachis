<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;

/**
 */
#[ORM\Entity(repositoryClass: 'App\Repository\SeriesRepository', readOnly: false)]
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
     * @var DateTime|null
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    protected ?DateTime $firstDate;

    /**
     * @var DateTime|null
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    protected ?DateTime $lastDate;

    /**
     * @var Collection|null The array of pages in the series
     */
    #[ORM\ManyToMany(targetEntity: 'App\Entity\Page', fetch: 'EAGER')]
    #[ORM\JoinTable(name: 'Series_pages')]
    #[ORM\JoinColumn(name: 'series_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'page_id', referencedColumnName: 'id')]
    #[ORM\OrderBy(['postDate' => 'ASC'])]
    protected ?Collection $items;

    /**
     * @var Image|null
     */
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Image', cascade: ['detach'])]
    #[ORM\JoinColumn(name: 'image_id', referencedColumnName: 'id')]
    protected ?Image $image = null;

    /**
     * @var User|null The UUID of the {@link User} that created the {@link Series}
     */
    #[ORM\ManyToOne(targetEntity: 'App\Entity\User', cascade: [ 'detach' ])]
    #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id')]
    protected ?User $author = null;

    /**
     * @var DateTime
     */
    #[ORM\Column(type: 'datetime')]
    protected DateTime $createDate;


    /**
     * @var DateTime
     */
    #[ORM\Column(type: 'datetime')]
    protected DateTime $modDate;

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
        $currentTime = new DateTime('now');
        $this->setCreateDate($currentTime);
        $this->setModDate($currentTime);
    }

    /**
     * @return UuidInterface|null
     */
    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    /**
     * @param UuidInterface|null $id
     * @return Series
     */
    public function setId(?UuidInterface $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string|null $title
     * @return Series
     */
    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSubTitle(): ?string
    {
        return $this->subTitle;
    }

    /**
     * @param string|null $subTitle
     * @return Series
     */
    public function setSubTitle(?string $subTitle = ''): self
    {
        $this->subTitle = $subTitle;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @param string|null $url
     * @return Series
     */
    public function setUrl(?string $url): self
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     * @return Series
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getFirstDate(): ?DateTime
    {
        return $this->firstDate;
    }

    /**
     * @param DateTime|null $firstDate
     * @return $this
     */
    public function setFirstDate(?DateTime $firstDate): self
    {
        $this->firstDate = $firstDate;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getLastDate(): ?DateTime
    {
        return $this->lastDate;
    }

    /**
     * @param DateTime|null $lastDate
     *
     * @return Series
     */
    public function setLastDate(?DateTime $lastDate): self
    {
        $this->lastDate = $lastDate;
        return $this;
    }

    /**
     * @return Collection|null
     */
    public function getItems(): ?Collection
    {
        return $this->items;
    }

    /**
     * @param Collection|null $items
     *
     * @return Series
     */
    public function setItems(?Collection $items): self
    {
        $this->items = $items;
        return $this;
    }

    /**
     * @param Page $item
     * @return Series
     */
    public function addItem(Page $item): self
    {
        $this->items->add($item);
        return $this;
    }

    /**
     * @return Image|null
     */
    public function getImage(): ?Image
    {
        return $this->image;
    }

    /**
     * @param Image|null $image
     * @return Series
     */
    public function setImage(?Image $image = null): self
    {
        $this->image = $image;
        return $this;
    }

    /**
     * Returns the value of {@link author}.
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
     * @return DateTime|null
     */
    public function getCreateDate(): ?DateTime
    {
        return $this->createDate;
    }

    /**
     * @param DateTime $createDate
     * @return $this
     */
    public function setCreateDate(DateTime $createDate): self
    {
        $this->createDate = $createDate;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getModDate(): ?DateTime
    {
        return $this->modDate;
    }

    /**
     * @param mixed $modDate
     * @return Series
     */
    public function setModDate(DateTime $modDate): self
    {
        $this->modDate = $modDate;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getVisibility(): ?bool
    {
        return $this->visibility;
    }

    /**
     * @param bool $visibility
     * @return Series
     */
    public function setVisibility(bool $visibility = self::PRIVATE): self
    {
        $this->visibility = $visibility;
        return $this;
    }
}
