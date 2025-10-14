<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;

/**
 * Object for handling {@link Page} revisions
 */
#[ORM\Entity(repositoryClass: 'App\Repository\RevisionRepository', readOnly: false)]
#[ORM\Index(columns: [ 'page_id', 'user_id' ], name: 'search_idx')]
class Revision
{
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
    protected ?string $page_id;

    /**
     * @var int
     */
    #[ORM\Column(type: 'integer', nullable: false)]
    protected int $versionNumber = 0;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 255)]
    protected string $action;

    /**
     * @var string The title of the {@link Page}
     */
    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    protected string $title;

    /**
     * @var string|null An optional sub-title for the {@link Page}
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $subTitle = null;

    /**
     * @var string|null The contents of the revision
     */
    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $content;

    /**
     * @var User|null The author for the {@link Page}
     */
    #[ORM\ManyToOne(targetEntity: 'App\Entity\User', cascade: ['detach'])]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    protected ?User $user;

    /**
     * @var DateTime|null The date the {@link Page} was last modified
     */
    #[ORM\Column(type: 'datetime')]
    protected ?DateTime $modDate;

    public function __construct()
    {
        $this->modDate = new DateTime();
    }

    /**
     * Returns the value of {@link id}.
     *
     * @return UuidInterface|null The UUID of the {@link Revision}
     */
    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    /**
     * Sets the value of {@link id}.
     *
     * @param UuidInterface $value The UUID of the {@link Revision}
     * @return $this
     */
    public function setId(UuidInterface $value): self
    {
        $this->id = $value;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPageId(): ?string
    {
        return $this->page_id;
    }

    /**
     * @param string $page_id
     * @return $this
     */
    public function setPageId(string $page_id): self
    {
        $this->page_id = $page_id;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getVersionNumber(): ?int
    {
        return $this->versionNumber;
    }

    /**
     * @param int $versionNumber
     * @return Revision
     * @throws Exception
     */
    public function setVersionNumber(int $versionNumber): self
    {
        if ($versionNumber < 1) {
            throw new Exception('Invalid version number');
        }
        $this->versionNumber = $versionNumber;

        return $this;
    }

    /**
     * Returns the value of {@link modDate}.
     * @return DateTime The date the {@link Page} was last modified
     */
    public function getModDate() : DateTime
    {
        return $this->modDate;
    }

    /**
     * Sets the value of {@link modDate}.
     * @param DateTime|null $value The date to set
     * @return Revision
     */
    public function setModDate(?DateTime $value): self
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
     * @param User|null $value The UUID of the {@link Page} author
     * @return Revision
     */
    public function setUser(?User $value = null): self
    {
        $this->user = $value;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAction(): ?string
    {
        return $this->action;
    }

    /**
     * @param string $action
     * @return $this
     */
    public function setAction(string $action): self
    {
        $this->action = $action;

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
     * @param string $title
     * @return $this
     */
    public function setTitle(string $title): self
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
     * @return $this
     */
    public function setSubTitle(?string $subTitle = null): self
    {
        $this->subTitle = $subTitle;

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
}
