<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Entity;

use Doctrine\ORM\Mapping as ORM;
use Inachis\Exception\InvalidTimezoneException;
use Inachis\Validator\DateValidator;
use Ramsey\Uuid\UuidInterface;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: 'Inachis\Repository\UserPreferenceRepository', readOnly: false)]
#[ORM\Index(columns: [ 'user_id' ], name: 'search_idx')]
class UserPreference
{
    /**
     * @var UuidInterface|null The unique identifier for the {@link UserPreference}
     */
    #[ORM\Id]
    #[ORM\Column(type: "uuid", unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    protected ?UuidInterface $id = null;

    /**
     * @var User The {@link User} the preferences are for
     */
    #[ORM\OneToOne(inversedBy: 'preferences')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    /**
     * @var string The theme the {@link User should use}
     */
    #[ORM\Column(length: 20)]
    private string $theme = 'light';

    /**
     * @var boolean Flag indicating if high contrast colours should be used
     */
    #[ORM\Column(type: "boolean")]
    private bool $highContrast = false;

    /**
     * @var string The font size to use (default|larger|largest)
     */
    #[ORM\Column(length: 10)]
    private string $fontSize = 'default';

    /**
     * @var string The font family to use (sans|serif|mono|dyslexic)
     */
    #[ORM\Column(length: 10)]
    private string $fontFamily = 'sans';

    /**
     * @var string The line height to use (default|comfortable|spacious)
     */
    #[ORM\Column(length: 10)]
    private string $lineHeight = 'default';

    /**
     * @var string The language code for the {@link User}'s preferred language
     */
    #[ORM\Column(length: 10)]
    private string $locale = 'en';

    /**
     * @InachisAssert\ValidTimezone()
     * @var string The local timezone for the user
     */
    #[ORM\Column(type: "string", length: 32, options: ["default" => "UTC" ])]
    #[Assert\NotBlank]
    protected string $timezone = 'UTC';

    /**
     * @var string Background colour for the {@link User}'s letter avatar
     */
    #[ORM\Column(type: "string", length: 10, nullable: false)]
    #[Assert\NotBlank]
    protected string $color = '#099bdd';

    /**
     * Constructor for {@link UserPreference} requires the linked {@link User}
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Sets the id for the {@link UserPreference}
     *
     * @param UuidInterface $id
     * @return self
     */
    public function setId(UuidInterface $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Sets the {@link User} for the {@link UserPreference}
     *
     * @param User $user
     * @return self
     */
    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Sets the theme for the {@link UserPreference}
     *
     * @param string $theme
     * @return self
     */
    public function setTheme(string $theme): self
    {
        $this->theme = $theme;
        return $this;
    }

    /**
     * Sets the highContrast flag for the {@link UserPreference}
     *
     * @return string
     */
    public function setHighContrast(bool $highContrast): self
    {
        $this->highContrast = $highContrast;
        return $this;
    }

    /**
     * Sets the font-size descriptor for the {@link UserPreference}
     *
     * @param string $fontSize
     * @return self
     */
    public function setFontSize(string $fontSize): self
    {
        $this->fontSize = $fontSize;
        return $this;
    }

    /**
     * Sets the fontFamily descriptor for the {@link UserPreference}
     *
     * @param string $fontSize
     * @return self
     */
    public function setFontFamily(string $fontFamily): self
    {
        $this->fontFamily = $fontFamily;
        return $this;
    }

    /**
     * Sets the line height descriptor for the {@link UserPreference}
     *
     * @param string $lineHeight
     * @return self
     */
    public function setLineHeight(string $lineHeight): self
    {
        $this->lineHeight = $lineHeight;
        return $this;
    }

    /**
     * sets the language code for the {@link UserPreference}
     *
     * @param string $locale
     * @return self
     */
    public function setLocale(string $locale): self
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * Sets the timezone for the {@link UserPreference}
     * 
     * @param string|null $value
     * @return $this
     * @throws InvalidTimezoneException
     */
    public function setTimezone(?string $value): self
    {
        $this->timezone = DateValidator::validateTimezone($value);
        return $this;
    }

    /**
     * Sets the background colour for the {@link User}'s lettered avatar
     * @param string $color
     * @return $this
     */
    public function setColor(string $color): self
    {
        $this->color = $color;
        return $this;
    }

    /**
     * Returns the id for the {@link UserPreference}
     *
     * @return UuidInterface
     */
    public function getId(): UuidInterface
    {
        return $this->id;
    }

    /**
     * Returns the {@link User} for the {@link UserPreference}
     *
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Returns the theme for the {@link UserPreference}
     *
     * @return string
     */
    public function getTheme(): string
    {
        return $this->theme;
    }

    /**
     * Returns the highContrast flag for the {@link UserPreference}
     *
     * @return bool
     */
    public function getHighContrast(): bool
    {
        return $this->highContrast;
    }

    /**
     * Returns the font size descriptor for the {@link UserPreference}
     *
     * @return string
     */
    public function getFontSize(): string
    {
        return $this->fontSize;
    }

    /**
     * Returns the font family descriptor for the {@link UserPreference}
     *
     * @return string
     */
    public function getFontFamily(): string
    {
        return $this->fontFamily;
    }

    /**
     * Returns the line height descriptor  for the {@link UserPreference}
     *
     * @return string
     */
    public function getLineHeight(): string
    {
        return $this->lineHeight;
    }

    /**
     * Returns the language code for the {@link UserPreference}
     *
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * Returns the {@link timezone} for the {@link User}.
     *
     * @return string The local timezone for the user
     */
    public function getTimezone(): string
    {
        return $this->timezone;
    }

    /**
     * Returns the background colour for the {@link UserPreference}. Used by the lettered avatar
     * @return string
     */
    public function getColor(): string
    {
        return $this->color;
    }
}
