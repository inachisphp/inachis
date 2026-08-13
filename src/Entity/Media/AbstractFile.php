<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Entity\Media;

use Doctrine\ORM\Mapping as ORM;
use Inachis\Entity\User\User;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * File entity properties common to different upload types
 * such as {@link Image} and {@link Download}.
 */
abstract class AbstractFile
{
    /** @var list<string> */
    public const ALLOWED_MIME_TYPES = [];

    /** @var list<string> */
    public const ALLOWED_TYPES = [];

    /**
     * @var UuidInterface|null The unique id of the category
     */
    #[ORM\Id]
    #[ORM\Column(type: 'uuid_binary', unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    protected ?UuidInterface $id = null;

    /**
     * @var string The title of the {@link Image}
     */
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    protected string $title = '';

    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $description = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    protected string $filename = '';

    #[Assert\Length(max: 127)]
    #[Assert\NotBlank]
    #[ORM\Column(type: 'string', length: 127, nullable: false)]
    protected string $filetype = '';

    #[Assert\PositiveOrZero]
    #[ORM\Column(type: 'integer')]
    protected int $filesize = 0;

    #[Assert\Length(exactly: 64)]
    #[Assert\NotBlank]
    #[Assert\Regex('/^[a-f0-9]{64}$/i')]
    #[ORM\Column(type: 'string', length: 64, nullable: false)]
    protected string $checksum = '';

    /**
     * @var User|null The UUID of the {@link User} that uploaded the file
     */
    #[ORM\ManyToOne(targetEntity: 'Inachis\Entity\User\User', cascade: ['detach'])]
    #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id')]
    protected ?User $author = null;

    #[ORM\Column(type: 'datetime_immutable')]
    protected \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    protected \DateTimeImmutable $updatedAt;

    #[Assert\Callback]
    public function validateContent(ExecutionContextInterface $context): void
    {
        if (null !== $this->description && mb_strlen($this->description) > 65535) {
            $context
                ->buildViolation('admin.validation.too_large')
                ->atPath('description')
                ->addViolation();
        }
    }

    /**
     * Returns the value of {@link id}.
     *
     * @return ?UuidInterface The UUID of the record
     */
    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    /**
     * Returns the value of {@link title}.
     *
     * @return string|null The title of the record
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Returns the value of {@link description}.
     *
     * @return string|null The description of the record
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Returns the value of {@link filename}.
     *
     * @return string The filename of the record
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * Returns the value of {@link filetype}.
     *
     * @return string|null The filetype of the record
     */
    public function getFiletype(): ?string
    {
        return $this->filetype;
    }

    /**
     * Returns the value of {@link filesize}.
     *
     * @return int The filesize of the record
     */
    public function getFilesize(): int
    {
        return $this->filesize;
    }

    /**
     * Returns the value of {@link checksum}.
     *
     * @return string|null The checksum of the record
     */
    public function getChecksum(): ?string
    {
        return $this->checksum;
    }

    /**
     * Returns the value of {@link author}.
     *
     * @return User|null The UUID of the {@link AbstractFile} author
     */
    public function getAuthor(): ?User
    {
        return $this->author;
    }

    /**
     * Returns the value of {@link createdAt}.
     *
     * @return \DateTimeImmutable The creation date of the file
     */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Returns the value of {@link updatedAt}.
     *
     * @return \DateTimeImmutable The date the file was last modified
     */
    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Sets the value of {@link id}.
     *
     * @param ?UuidInterface $value The id to set
     */
    public function setId(?UuidInterface $value): static
    {
        $this->id = $value;

        return $this;
    }

    /**
     * Sets the value of {@link title}.
     *
     * @param string $value The title to set
     */
    public function setTitle(string $value): static
    {
        $this->title = $value;

        return $this;
    }

    /**
     * Sets the value of {@link description}.
     *
     * @param string|null $value The description to set
     */
    public function setDescription(?string $value): static
    {
        $this->description = $value;

        return $this;
    }

    /**
     * Sets the value of {@link filename}.
     *
     * @param string $value The filename to set
     */
    public function setFilename(string $value): static
    {
        $this->filename = $value;

        return $this;
    }

    /**
     * Sets the value of {@link filetype}.
     *
     * @param string $value The filetype to set
     */
    public function setFiletype(string $value): static
    {
        if (!$this->isValidFiletype($value)) {
            throw new FileException(sprintf('Invalid file type %s', $value));
        }
        $this->filetype = $value;

        return $this;
    }

    public function isValidFiletype(string $value): bool
    {
        return empty(static::ALLOWED_MIME_TYPES) || in_array($value, static::ALLOWED_MIME_TYPES, true);
    }

    /**
     * Sets the value of {@link filesize}.
     *
     * @param int $value The filesize to set
     */
    public function setFilesize(int $value): static
    {
        if ($value < 0) {
            throw new FileException('File size must be a positive integer');
        }
        $this->filesize = $value;

        return $this;
    }

    /**
     * Sets the value of {@link checksum}.
     *
     * @param string $value The checksum to set
     */
    public function setChecksum(string $value): static
    {
        $this->checksum = $value;

        return $this;
    }

    /**
     * Sets the value of {@link author}.
     *
     * @param User|null $value The {@link User} to set as the author
     */
    public function setAuthor(?User $value = null): static
    {
        $this->author = $value;

        return $this;
    }

    /**
     * Sets the value of {@link createdAt}.
     *
     * @param \DateTimeImmutable $value The date to be set
     */
    public function setCreatedAt(\DateTimeImmutable $value): static
    {
        $this->createdAt = $value;

        return $this;
    }

    /**
     * Sets the value of {@link updatedAt}.
     *
     * @param \DateTimeImmutable $value Specifies the mod date for the {@link Page}
     */
    public function setUpdatedAt(\DateTimeImmutable $value): static
    {
        $this->updatedAt = $value;

        return $this;
    }

    /**
     * Verifies the checksum of the file matches the provided one.
     *
     * @param string $checksum The checksum to verify against
     *
     * @return bool The result of testing the checksum
     */
    public function verifyChecksum(string $checksum): bool
    {
        return hash_equals($checksum, $this->checksum);
    }
}
