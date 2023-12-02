<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use App\Exception\InvalidTimezoneException;

/**
 * Object for handling {@link Page} revisions
 * @ORM\Entity(repositoryClass="App\Repository\RevisionRepository")
 * @ORM\Table(indexes={@ORM\Index(name="search_idx", columns={"page_id", "user_id"})})
 */
class Revision
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
    private $page_id;
    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    private $versionNumber = 0;
    /**
     * @ORM\Column(type="string", length=255)
     */
    private $action;
    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     *
     * @var string The title of the {@link Page}
     */
    protected $title;
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @var string An optional sub-title for the {@link Page}
     */
    protected $subTitle = null;
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
     * @var string The date the {@link Page} was last modified
     */
    protected $modDate;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getPageId(): ?string
    {
        return $this->page_id;
    }

    public function setPageId(string $page_id): self
    {
        $this->page_id = $page_id;

        return $this;
    }

    public function getVersionNumber(): ?int
    {
        return $this->versionNumber;
    }

    /**
     * @param int $versionNumber
     * @return Revision
     * @throws \Exception
     */
    public function setVersionNumber(int $versionNumber): self
    {
        if ($versionNumber < 1) {
            throw new \Exception('Invalid version number');
        }
        $this->versionNumber = $versionNumber;

        return $this;
    }

    /**
     * Returns the value of {@link modDate}.
     *
     * @return string The date the {@link Page} was last modified
     */
    public function getModDate()
    {
        return $this->modDate;
    }

    /**
     * Sets the value of {@link modDate}.
     *
     * @param \DateTime $value The date to set
     * @return Revision
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
     * @param User $value The UUID of the {@link Page} author
     * @return Revision
     */
    public function setUser(User $value = null): self
    {
        $this->user = $value;

        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getSubTitle(): ?string
    {
        return $this->subTitle;
    }

    public function setSubTitle(string $subTitle = null): self
    {
        $this->subTitle = $subTitle;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }
}
