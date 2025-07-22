<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;

/**
 * Object for handling {@link Waste} contents
 * @ORM\Entity(repositoryClass="App\Repository\WasteRepository")
 * @ORM\Table(indexes={@ORM\Index(name="search_idx", columns={"sourceType", "user_id"})})
 */
class Waste
{
    /**
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true, nullable=false)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=UuidGenerator::class)
     *
     * @var \Ramsey\Uuid\UuidInterface
     */
    private $id;
    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $sourceType;
    /**
     * @ORM\Column(type="string", length=255, nulllable=false)
     */
    private $sourceName;
    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title;
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $content;
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", cascade={"detach"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     *
     * @var User The author for the {@link Page}
     */
    private $user;
    /**
     * @ORM\Column(type="datetime")
     *
     * @var string The date the item was added to the bin
     */
    protected $modDate;

    /**
     * @return string|null
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
     * @return string|null
     */
    public function getSourceType(): ?string
    {
        return $this->sourceType;
    }

    /**
     * @param string $sourceType
     * @return $this
     */
    public function setSourceType(string $sourceType): self
    {
        $this->sourceType = $sourceType;

        return $this;
    }


    /**
     * @return string|null
     */
    public function getSourceName(): ?string
    {
        return $this->sourceName;
    }

    /**
     * @param string $sourceName
     * @return $this
     */
    public function setSourceName(string $sourceName): self
    {
        $this->sourceName = $sourceName;

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
     * @param mixed $title
     * @return $this
     */
    public function setTitle($title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * @param string|null $content
     * @return $this
     */
    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return DateTimeInterface|null The date the content was deleted
     */
    public function getModDate(): ?\DateTimeInterface
    {
        return $this->modDate;
    }

    /**
     * @param \DateTimeInterface $value
     * @return $this
     */
    public function setModDate(\DateTime $value = null): self
    {
        $this->modDate = $value;

        return $this;
    }

    /**
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Sets the value of {@link author}.
     *
     * @param User $value The UUID of user adding the {@link Waste}
     * @return $this
     */
    public function setUser(User $value = null): self
    {
        $this->user = $value;

        return $this;
    }
}
