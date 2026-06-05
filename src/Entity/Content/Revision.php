<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Entity\Content;

use Doctrine\ORM\Mapping as ORM;
use Inachis\Entity\User\User;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;
use DateTimeImmutable;
use Exception;

/**
 * Object for handling {@link Page} revisions
 */
#[ORM\Entity(repositoryClass: 'Inachis\Repository\RevisionRepository', readOnly: false)]
#[ORM\Index(columns: [ 'page_id', 'user_id' ], name: 'search_idx')]
class Revision
{
    /**
     * The UUID of the {@link Revision}
     * 
     * @var UuidInterface|null The UUID of the {@link Revision}
     */
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    protected ?UuidInterface $id = null;

    /**
     * The ID of the {@link Page}
     * 
     * @var string|null The ID of the {@link Page}
     */
    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    protected ?string $page_id;

    /**
     * The version number of the {@link Revision}
     * 
     * @var int The version number of the {@link Revision}
     */
    #[ORM\Column(type: 'integer', nullable: false)]
    protected int $versionNumber = 0;

    /**
     * The action type for the revision
     * 
     * @var string The action type for the revision
     */
    #[ORM\Column(type: 'string', length: 255)]
    protected string $action;

    /**
     * The title of the {@link Page}
     * 
     * @var string The title of the {@link Page}
     */
    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    protected string $title;

    /**
     * An optional sub-title for the {@link Page}
     * 
     * @var string|null An optional sub-title for the {@link Page}
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $subTitle = null;

    /**
     * The contents of the revision
     * 
     * @var string|null The contents of the revision
     */
    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $content;

    /**
     * The author for the {@link Page}
     * 
     * @var User|null The author for the {@link Page}
     */
    #[ORM\ManyToOne(targetEntity: 'Inachis\Entity\User\User', cascade: ['detach'])]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    protected ?User $user;

    /**
     * The date the {@link Page} was last modified
     * 
     * @var DateTimeImmutable The date the {@link Page} was last modified
     */
    #[ORM\Column(type: 'datetime_immutable')]
    protected DateTimeImmutable $modDate;

    public function __construct()
    {
        $this->modDate = new DateTimeImmutable();
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
     * @return self
     */
    public function setId(UuidInterface $value): self
    {
        $this->id = $value;
        return $this;
    }

    /**
     * Returns the value of {@link page_id}.
     * 
     * @return string|null The ID of the {@link Page}
     */
    public function getPageId(): ?string
    {
        return $this->page_id;
    }

    /**
     * Sets the value of {@link page_id}.
     * 
     * @param string $page_id The ID of the {@link Page}
     * @return self
     */
    public function setPageId(string $page_id): self
    {
        $this->page_id = $page_id;

        return $this;
    }

    /**
     * Returns the value of {@link versionNumber}.
     * 
     * @return int The version number of the {@link Revision}
     */
    public function getVersionNumber(): int
    {
        return $this->versionNumber;
    }

    /**
     * Sets the value of {@link versionNumber}.
     * 
     * @param int $value The version number of the {@link Revision}
     * @return self
     * @throws Exception
     */
    public function setVersionNumber(int $value): self
    {
        if ($value < 1) {
            throw new Exception('Invalid version number');
        }
        $this->versionNumber = $value;

        return $this;
    }

    /**
     * Returns the value of {@link modDate}.
     * 
     * @return DateTimeImmutable The date the {@link Page} was last modified
     */
    public function getModDate(): DateTimeImmutable
    {
        return $this->modDate;
    }

    /**
     * Sets the value of {@link modDate}.
     * 
     * @param DateTimeImmutable $value The date to set
     * @return Revision
     */
    public function setModDate(DateTimeImmutable $value): self
    {
        $this->modDate = $value;

        return $this;
    }

    /**
     * Returns the value of {@link user}.
     * 
     * @return User|null The user who created the {@link Revision}
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Sets the value of {@link user}.
     * 
     * @param User|null $value The user who created the {@link Revision}
     * @return self
     */
    public function setUser(?User $value = null): self
    {
        $this->user = $value;

        return $this;
    }

    /**
     * Returns the value of {@link action}.
     * 
     * @return string|null The action type for the {@link Revision}
     */
    public function getAction(): ?string
    {
        return $this->action;
    }

    /**
     * Sets the value of {@link action}.
     * 
     * @param string $value The action type for the {@link Revision}
     * @return self
     */
    public function setAction(string $value): self
    {
        $this->action = $value;

        return $this;
    }

    /**
     * Returns the value of {@link title}.
     * 
     * @return string|null The title of the {@link Revision}
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Sets the value of {@link title}.
     * 
     * @param string $value The title of the {@link Revision}
     * @return self
     */
    public function setTitle(string $value): self
    {
        $this->title = $value;

        return $this;
    }

    /**
     * Returns the value of {@link subTitle}.
     * 
     * @return string|null The sub-title of the {@link Revision}
     */
    public function getSubTitle(): ?string
    {
        return $this->subTitle;
    }

    /**
     * Sets the value of {@link subTitle}.
     * 
     * @param string|null $value The sub-title of the {@link Revision}
     * @return self
     */
    public function setSubTitle(?string $value = null): self
    {
        $this->subTitle = $value;

        return $this;
    }

    /**
     * Returns the value of {@link content}.
     * 
     * @return string|null The content of the {@link Revision}
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * Sets the value of {@link content}.
     * 
     * @param string|null $value The content of the {@link Revision}
     * @return self
     */
    public function setContent(?string $value): self
    {
        $this->content = $value;

        return $this;
    }
}
