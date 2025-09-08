<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;

/**
 */
#[ORM\Entity(repositoryClass: 'App\Repository\SeriesRepository', readOnly: false)]
#[ORM\Index(name: 'search_idx', columns: ['title'])]
class Series
{
    /**
     * @var \Ramsey\Uuid\UuidInterface
     */
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    protected $id;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    protected $title;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected $subTitle;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 255, unique: true, nullable: false)]
    protected $url;

    /**
     * @var string
     */
    #[ORM\Column(type: 'text', nullable: true)]
    protected $description;

    /**
     * @var string
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    protected $firstDate;

    /**
     * @var string
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    protected $lastDate;

    /**
     * @var Collection|Page[] The array of pages in the series
     */
    #[ORM\ManyToMany(targetEntity: 'App\Entity\Page', inversedBy: 'series', fetch: 'EAGER')]
    #[ORM\JoinTable(name: 'Series_pages')]
    #[ORM\JoinColumn(name: 'series_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'page_id', referencedColumnName: 'id')]
    #[ORM\OrderBy(['postDate' => 'ASC'])]
    protected $items = [];

    /**
     * @var Image
     */
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Image', cascade: ['detach'])]
    #[ORM\JoinColumn(name: 'image_id', referencedColumnName: 'id')]
    protected $image;

    /**
     * @var string
     */
    #[ORM\Column(type: 'datetime')]
    protected $createDate;


    /**
     * @var string
     */
    #[ORM\Column(type: 'datetime')]
    protected $modDate;

    /**
     * Series constructor.
     */
    public function __construct()
    {
        $this->items = new ArrayCollection();

        $currentTime = new \DateTime('now');
        $this->setCreateDate($currentTime);
        $this->setModDate($currentTime);
    }

    /**
     * @return string
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return $this
     */
    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getSubTitle(): ?string
    {
        return $this->subTitle;
    }

    /**
     * @param string $subTitle
     * @return $this
     */
    public function setSubTitle(?string $subTitle): self
    {
        $this->subTitle = $subTitle;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @param mixed $url
     * @return $this
     */
    public function setUrl($url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getFirstDate(): ?\DateTime
    {
        return $this->firstDate;
    }

    /**
     * @param \DateTime $firstDate
     * @return $this
     */
    public function setFirstDate(\DateTime $firstDate = null): self
    {
        $this->firstDate = $firstDate;

        return $this;
    }

    /**
     * @return string
     */
    public function getLastDate(): ?\DateTime
    {
        return $this->lastDate;
    }

    /**
     * @param \DateTime $lastDate
     *
     * @return $this
     */
    public function setLastDate(\DateTime $lastDate = null): self
    {
        $this->lastDate = $lastDate;

        return $this;
    }

    /**
     * @return array
     */
    public function getItems(): ?Collection
    {
        return $this->items;
    }

    /**
     * @param array $items
     *
     * @return $this
     */
    public function setItems(array $items): self
    {
        $this->items = $items;

        return $this;
    }

    /**
     * @param Page $item
     *
     * @return $this
     */
    public function addItem(Page $item): self
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * @return Image
     */
    public function getImage(): ?Image
    {
        return $this->image;
    }

    /**
     * @param Image $image
     * @return $this
     */
    public function setImage(?Image $image): self
    {
        $this->image = $image;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCreateDate(): ?\DateTime
    {
        return $this->createDate;
    }

    /**
     * @param mixed $createDate
     *
     * @return $this
     */
    public function setCreateDate(\DateTime $createDate): self
    {
        $this->createDate = $createDate;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getModDate(): ?\DateTime
    {
        return $this->modDate;
    }

    /**
     * @param mixed $modDate
     *
     * @return $this
     */
    public function setModDate(\DateTime $modDate): self
    {
        $this->modDate = $modDate;

        return $this;
    }
}
