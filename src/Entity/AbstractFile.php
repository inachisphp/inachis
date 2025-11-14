<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

/**
 * File entity properties common to different upload types
 * such as {@link Image} and {@link Download}.
 */
abstract class AbstractFile
{
    /**
     * @var UuidInterface|null The unique id of the category
     */
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    protected ?UuidInterface $id = null;

    /**
     * @var string The title of the {@link Image}
     */
    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    protected string $title = '';

    /**
     * @var ?string
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $description = '';

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', nullable: false)]
    protected string $filename = '';

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', nullable: false)]
    protected string $filetype = '';

    /**
     * @var int
     */
    #[ORM\Column(type: 'integer')]
    protected int $filesize = 0;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string')]
    protected string $checksum;

    /**
     * @var User|null The UUID of the {@link User} that uploaded the file
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
     * Returns the value of {@link id}.
     *
     * @return string The UUID of the record
     */
    public function getId(): string
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
     * @return string|null The filename of the record
     */
    public function getFilename(): ?string
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
     * Returns the value of {@link createDate}.
     *
     * @return DateTime The creation date of the file
     */
    public function getCreateDate(): DateTime
    {
        return $this->createDate;
    }

    /**
     * Returns the value of {@link modDate}.
     *
     * @return DateTime The date the file was last modified
     */
    public function getModDate(): DateTime
    {
        return $this->modDate;
    }

    /**
     * Sets the value of {@link id}.
     *
     * @param UuidInterface $value The id to set
     * @return $this
     */
    public function setId(UuidInterface $value): self
    {
        $this->id = $value;

        return $this;
    }

    /**
     * Sets the value of {@link title}.
     *
     * @param string|null $value The title to set
     * @return $this
     */
    public function setTitle(?string $value): self
    {
        $this->title = $value;

        return $this;
    }

    /**
     * Sets the value of {@link description}.
     *
     * @param string|null $value The description to set
     * @return $this
     */
    public function setDescription(?string $value): self
    {
        $this->description = $value;

        return $this;
    }

    /**
     * Sets the value of {@link filename}.
     *
     * @param string|null $value The filename to set
     * @return $this
     */
    public function setFilename(?string $value): self
    {
        $this->filename = $value;

        return $this;
    }

    /**
     * Sets the value of {@link filetype}.
     *
     * @param string $value The filetype to set
     * @return $this
     */
    public function setFiletype(string $value): self
    {
        if (!$this->isValidFiletype($value)) {
            throw new FileException(sprintf('Invalid file type %s', $value));
        }
        $this->filetype = $value;

        return $this;
    }

    /**
     * @param string $value
     * @return bool
     */
    public function isValidFiletype(string $value): bool
    {
        if (defined('static::ALLOWED_MIME_TYPES')) {
            return preg_match('/' . static::ALLOWED_MIME_TYPES . '/', $value) === 1;
        }
        return true;
    }

    /**
     * Sets the value of {@link filesize}.
     *
     * @param int $value The filesize to set
     * @return $this
     */
    public function setFilesize(int $value): self
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
     * @return $this
     */
    public function setChecksum(string $value): self
    {
        $this->checksum = $value;

        return $this;
    }

    /**
     * Sets the value of {@link author}.
     *
     * @param User|null $value The {@link User} to set as the author
     * @return AbstractFile
     */
    public function setAuthor(?User $value = null): self
    {
        $this->author = $value;
        return $this;
    }

    /**
     * Sets the value of {@link createDate}.
     *
     * @param DateTime|null $value The date to be set
     * @return $this
     */
    public function setCreateDate(DateTime $value = null): self
    {
        $this->createDate = $value;

        return $this;
    }

    /**
     * Sets the value of {@link modDate}.
     *
     * @param DateTime|null $value Specifies the mod date for the {@link Page}
     * @return $this
     */
    public function setModDate(DateTime $value = null): self
    {
        $this->modDate = $value;

        return $this;
    }

    /**
     * Verifies the checksum of the file matches the provided one.
     *
     * @param string $checksum The checksum to verify against
     * @return bool The result of testing the checksum
     */
    public function verifyChecksum(string $checksum): bool
    {
        return hash_equals($checksum, $this->checksum);
    }
}
