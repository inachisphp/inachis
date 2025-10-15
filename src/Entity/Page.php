<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Entity;

use DateTime;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use InvalidArgumentException;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use App\Exception\InvalidTimezoneException;
use Ramsey\Uuid\UuidInterface;

/**
 * Object for handling pages of a site.
 */
#[ORM\Entity(repositoryClass: 'App\Repository\PageRepository', readOnly: false)]
#[ORM\Index(columns: ['title', 'author_id', 'image_id'], name: 'search_idx')]
class Page
{
    /**
     * @const string Indicates a Page is currently in draft
     */
    public const DRAFT = 'draft';

    /**
     * @const string Indicates a Page has been published
     */
    public const PUBLISHED = 'published';

    /**
     * @const string Indicates a Page is public
     */
    public const PUBLIC = true;

    /**
     * @const string Indicates a Page is private
     */
    public const PRIVATE = false;

    /**
     * @const string Indicates a Page is standalone
     */
    public const TYPE_PAGE = 'page';

    /**
     * @const string Indicates a Page is a blog post
     */
    public const TYPE_POST = 'post';

    /**
     * @var UuidInterface|null
     */
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    protected ?UuidInterface $id = null;

    /**
     * @var string|null The title of the {@link Page}
     */
    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    protected ?string $title = null;

    /**
     * @var string|null An optional subtitle for the {@link Page}
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $subTitle = null;

    /**
     * @var string|null The contents of the {@link Page}
     */
    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $content = null;

    /**
     * @var User|null The UUID of the author for the {@link Page}
     */
    #[ORM\ManyToOne(targetEntity: 'App\Entity\User', cascade: [ 'detach' ])]
    #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id')]
    protected ?User $author;

    /**
     * @var Image|null The featured {@link Image} for the {@link Page}
     */
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Image', cascade: [ 'detach' ])]
    #[ORM\JoinColumn(name: 'image_id', referencedColumnName: 'id')]
    protected ?Image $featureImage;

    /**
     * @var string|null A short excerpt describing the contents of the {@link Page}
     */
    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $featureSnippet;

    /**
     * @var string|null Current status of the {@link Page}, defaults to {@link DRAFT}
     */
    #[ORM\Column(type: 'string', length:20)]
    protected ?string $status = self::DRAFT;

    /**
     * @var bool Determining if a {@link Page} is visible to the public
     */
    #[ORM\Column(type: 'boolean', length: 20)]
    protected bool $visibility = self::PUBLIC;

    /**
     * @var DateTime|null The date the {@link Page} was created
     */
    #[ORM\Column(type: 'datetime')]
    protected ?DateTime $createDate;

    /**
     * @var DateTime|null The date the {@link Page} was published; a future date
     *             indicates the content is scheduled
     */
    #[ORM\Column(type: 'datetime')]
    protected ?DateTime $postDate;

    /**
     * @var DateTime|null The date the {@link Page} was last modified
     */
    #[ORM\Column(type: 'datetime')]
    protected ?DateTime $modDate;

    /**
     * @var string|null The timezone for the publication date; defaults to UTC
     */
    #[ORM\Column(type: 'string', length: 50)]
    protected ?string $timezone = 'UTC';

    /**
     * @var string|null A password to protect the {@link Page} with if required
     */
    #[ORM\Column(type: 'string', length:255, nullable: true)]
    protected ?string $password;

    /**
     * @var bool Flag determining if the {@link Page} allows comments
     */
    #[ORM\Column(type: 'boolean', nullable: false)]
    protected bool $allowComments = false;

    /**
     * @var string The type of page. Default: {@link self::TYPE_POST}
     */
    #[ORM\Column(type: 'string', nullable: false)]
    protected string $type = self::TYPE_POST;

    /**
     * @var string|null A location for geo-context aware content
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $latlong;

    /**
     * @var string|null A short 140-character message to use when sharing content
     */
    #[ORM\Column(type: 'string', length: 140, nullable: true)]
    protected ?string $sharingMessage;

    /**
     * @var ArrayCollection|null The array of URLs for the content
     */
    #[ORM\OneToMany(mappedBy: 'content', targetEntity: 'App\Entity\Url', cascade: [ 'persist' ])]
    #[ORM\OrderBy(['default' => 'DESC'])]
    protected ?ArrayCollection $urls;

    /**
     * @var ArrayCollection|null The array of categories assigned to the post/page
     */
    #[ORM\ManyToMany(targetEntity: 'App\Entity\Category')]
    #[ORM\JoinTable(name: 'Page_categories')]
    #[ORM\JoinColumn(name: 'page_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'category_id', referencedColumnName: 'id')]
    #[ORM\OrderBy([ 'title' => 'ASC' ])]
    protected ?ArrayCollection $categories;

    /**
     * @var ArrayCollection|null The array of tags assigned to the post/page
     */
    #[ORM\ManyToMany(targetEntity: 'App\Entity\Tag', cascade: [ 'persist' ])]
    #[ORM\JoinTable(name: 'Page_tags')]
    #[ORM\JoinColumn(name: 'page_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'tag_id', referencedColumnName: 'id')]
    #[ORM\OrderBy([ 'title' => 'ASC' ])]
    protected ?ArrayCollection $tags;

    /**
     * @var ArrayCollection|null  The array of Series that contains this page
     */
    #[ORM\ManyToMany(targetEntity: 'App\Entity\Series', inversedBy: 'items')]
    protected ?ArrayCollection $series;

    /**
     * @var string|null The two character language code this content uses, empty means unknown
     */
    #[ORM\Column(type: 'string', length: 15, nullable: true)]
    protected ?string $language;

    /**
     * Default constructor for {@link Page}.
     *
     * @param string    $title   The title for the {@link Page}
     * @param string    $content The content for the {@link Page}
     * @param User|null $author  The {@link User} that authored the {@link Page}
     * @param string    $type    The type of {@link Page} - post or page
     * @throws Exception
     */
    public function __construct(
        string $title = '',
        string $content = '',
        ?User $author = null,
        string $type = self::TYPE_POST
    ) {
        $this->setTitle($title);
        $this->setContent($content);
        $this->setAuthor($author);
        $currentTime = new DateTime('now');
        $this->setCreateDate($currentTime);
        $this->setPostDate($currentTime);
        $this->setModDate($currentTime);
        $this->type = $type;
        $this->urls = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->series = new ArrayCollection();
    }

    /**
     * Returns the value of {@link id}.
     *
     * @return UuidInterface|null The UUID of the {@link Page}
     */
    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    /**
     * Returns the value of {@link title}.
     *
     * @return string|null The title of the {@link Page} - cannot be empty
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Returns the value of {@link subTitle}.
     *
     * @return string|null The Sub-title of the {@link Page}
     */
    public function getSubTitle(): ?string
    {
        return $this->subTitle;
    }

    /**
     * Returns the value of {@link content}.
     *
     * @return string|null The contents of the {@link Page}
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * Returns the value of {@link author}.
     *
     * @return User|null The UUID of the {@link Page} author
     */
    public function getAuthor(): ?User
    {
        return $this->author;
    }

    /**
     * Returns the value of {@link featureImage}.
     *
     * @return Image|null The feature image object
     */
    public function getFeatureImage(): ?Image
    {
        return $this->featureImage;
    }

    /**
     * Returns the value of {@link featureSnippet}.
     *
     * @return string|null The short excerpt to used as the feature
     */
    public function getFeatureSnippet(): ?string
    {
        return $this->featureSnippet;
    }

    /**
     * Returns the value of {@link status}.
     *
     * @return string|null The current publishing status of the {@link Page}
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * Returns the value of {@link visibility}.
     *
     * @return bool The current visibility of the {@link Page}
     */
    public function getVisibility(): bool
    {
        return $this->visibility;
    }

    /**
     * Returns the value of {@link createDate}.
     *
     * @return DateTime The creation date of the {@link Page}
     */
    public function getCreateDate(): DateTime
    {
        return $this->createDate;
    }

    /**
     * Returns the value of {@link postDate}.
     *
     * @return DateTime|null The publication date of the {@link Page}
     */
    public function getPostDate(): ?DateTime
    {
        return $this->postDate;
    }

    /**
     * Returns the value of {@link modDate}.
     *
     * @return DateTime The date the {@link Page} was last modified
     */
    public function getModDate(): DateTime
    {
        return $this->modDate;
    }

    /**
     * Returns the value of {@link timezone}.
     *
     * @return string|null The timezone for {@link Page::post_date}
     */
    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    /**
     * Returns the value of {@link password}.
     *
     * @return string|null The hash of the password protecting the {@link Page}
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Returns the value of {@link allowComments}.
     *
     * @return bool Flag indicating if the {@link Page} allows comments
     */
    public function isAllowComments(): bool
    {
        return $this->allowComments;
    }

    /**
     * Returns the type of the current {@link Page} entity.
     *
     * @return string|null The current type
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Returns the latlong for the current {@link Page} entity.
     *
     * @return string|null The latitude and longitude of the content
     */
    public function getLatlong(): ?string
    {
        return $this->latlong;
    }

    /**
     * Returns the sharingMessage for the current {@link Page} entity.
     *
     * @return string|null The latitude and longitude of the content
     */
    public function getSharingMessage(): ?string
    {
        return $this->sharingMessage;
    }

    /**
     * Returns an array of URLs assigned to the page. The default URL will
     * always be first.
     *
     * @return Collection|null The array of {$link Url} entities for the {@link Page}
     */
    public function getUrls(): ?Collection
    {
        return $this->urls;
    }

    /**
     * Returns an array of {@link Category)s assigned to the page.
     *
     * @return Collection|null The array of {$link Category} entities for the {@link Page}
     */
    public function getCategories(): ?Collection
    {
        return $this->categories;
    }

    /**
     * Returns an array of {@link Tag)s assigned to the page.
     *
     * @return Collection|null The array of {$link Category} entities for the {@link Page}
     */
    public function getTags(): ?Collection
    {
        return $this->tags;
    }

    /**
     * Returns the Url with a specific index within the array. Default returns the first
     *
     * @param int $key The index of the item to return
     * @return Url|null The requested {@link Url} entry
     */
    public function getUrl(int $key = 0): ?Url
    {
        if ($key && !isset($this->urls[$key])) {
            throw new InvalidArgumentException(sprintf('Url `%s` does not exist', $key));
        }
        return $this->urls[$key];
    }

    /**
     * @return ArrayCollection|null
     */
    public function getSeries(): ?ArrayCollection
    {
        return $this->series;
    }

    /**
     * @return string|null The language used by this content
     */
    public function getLanguage(): ?string
    {
        return $this->language;
    }

    /**
     * Sets the value of {@link id}.
     *
     * @param UuidInterface $value The UUID of the {@link Page}
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
     * @param string|null $value The title of the {@link Page}
     * @return Page
     */
    public function setTitle(?string $value): self
    {
        $this->title = $value;
        return $this;
    }

    /**
     * Sets the value of {@link subTitle}.
     *
     * @param string|null $value The optional subtitle of the {@link Page}
     * @return Page
     */
    public function setSubTitle(?string $value = null): self
    {
        $this->subTitle = $value;
        return $this;
    }

    /**
     * Sets the value of {@link content}.
     *
     * @param string|null $value The contents of the {@link Page}
     * @return Page
     */
    public function setContent(?string $value): self
    {
        $this->content = $value;
        return $this;
    }

    /**
     * Sets the value of {@link author}.
     *
     * @param User|null $value The {@link User} to set as the {@link Page} author
     * @return Page
     */
    public function setAuthor(?User $value = null): self
    {
        $this->author = $value;
        return $this;
    }

    /**
     * Sets the value of {@link featureImage}.
     *
     * @param Image|null $value The UUID or URL to use for the {@link feature_image}
     * @return Page
     */
    public function setFeatureImage(?Image $value): self
    {
        $this->featureImage = $value;
        return $this;
    }

    /**
     * Sets the value of {@link featureSnippet}.
     *
     * @param string|null $value Short excerpt to use with the {@link feature_image}
     * @return Page
     */
    public function setFeatureSnippet(?string $value): self
    {
        $this->featureSnippet = $value;
        return $this;
    }

    /**
     * Sets the value of {@link status}.
     *
     * @param string|null $value The new publishing status of the {@link Page}
     * @return Page
     */
    public function setStatus(?string $value = self::DRAFT): self
    {
        $this->status = $this->isValidStatus($value) ? $value : self::DRAFT;
        return $this;
    }

    /**
     * Sets the value of {@link visibility}. Default 'Private'
     *
     * @param bool $value The visibility of the {@link Page}
     * @return Page
     */
    public function setVisibility(bool $value = self::PRIVATE): self
    {
        $this->visibility = $value;
        return $this;
    }

    /**
     * Sets the value of {@link createDate}.
     *
     * @param DateTime|null $value The date to be set
     * @return Page
     */
    public function setCreateDate(?DateTime $value = null): self
    {
        $this->createDate = $value;
        return $this;
    }

    /**
     * Sets the value of {@link postDate}.
     *
     * @param DateTime|null $value The date to be set
     * @return Page
     */
    public function setPostDate(?DateTime $value = null): self
    {
        $this->postDate = $value;
        return $this;
    }

    /**
     * Sets the value of {@link modDate}.
     *
     * @param DateTime|null $value The date to set
     * @return Page
     */
    public function setModDate(?DateTime $value = null): self
    {
        $this->modDate = $value;
        return $this;
    }

    /**
     * Sets the value of {@link timezone}.
     *
     * @param string|null $value The timezone for the post_date
     * @return Page
     * @throws InvalidTimezoneException
     */
    public function setTimezone(?string $value): self
    {
        if (!$this->isValidTimezone($value)) {
            throw new InvalidTimezoneException(
                sprintf('Did not recognise timezone %s', $value)
            );
        }
        $this->timezone = $value;
        return $this;
    }

    /**
     * Sets the value of {@link password}.
     *
     * @param string|null $value The password to protect the {@link Page} with
     * @return Page
     */
    public function setPassword(?string $value): self
    {
        $this->password = $value;
        return $this;
    }

    /**
     * Sets the value of {@link allowComments}.
     *
     * @param bool $value Flag specifying if comments allowed on {@link Page}
     * @return Page
     */
    public function setAllowComments(?bool $value = true): self
    {
        $this->allowComments = (bool) $value;
        return $this;
    }

    /**
     * Sets the current type of {@link Page} entity.
     *
     * @param string $type The type of page
     * @return $this
     * @throws Exception
     */
    public function setType(string $type): self
    {
        if (!in_array($type, [self::TYPE_POST, self::TYPE_PAGE])) {
            throw new Exception(sprintf('`%s` is not a valid page type', $type));
        }
        $this->type = $type;

        return $this;
    }

    /**
     * Sets the current latitude and longitude of {@link Page} entity.
     *
     * @param string|null $value The latitude and longitude of the content
     * @return Page
     */
    public function setLatlong(?string $value): self
    {
        $this->latlong = $value;
        return $this;
    }

    /**
     * Sets the current sharingMessage of {@link Page} entity.
     *
     * @param string|null $value The sharingMessage of the content
     * @return Page
     */
    public function setSharingMessage(?string $value): self
    {
        $this->sharingMessage = $value;
        return $this;
    }

    /**
     * @param ArrayCollection|null $series
     * @return Page
     */
    public function setSeries(?ArrayCollection $series): self
    {
        $this->series = $series;
        return $this;
    }

    /**
     * @param string $language
     * @return Page
     */
    public function setLanguage(string $language): self
    {
        $this->language = $language;
        return $this;
    }

    /**
     * Adds a {@link Url} to the {@link Page}.
     *
     * @param Url $url The new {@link Url} to add to the {@link Page}
     * @return Page
     */
    public function addUrl(Url $url): self
    {
        $this->urls[] = $url;
        return $this;
    }

    /**
     * Adds a {@link Category} to the {@link Page}.
     *
     * @param Category $category The new {@link Category} to add to the {@link Page}
     */
    public function addCategory(Category $category): self
    {
        $this->categories[] = $category;
        return $this;
    }

    /**
     * @return $this
     */
    public function removeCategories(): self
    {
        $this->categories->clear();
        return $this;
    }

    /**
     * Adds a {@link Tag} to the {@link Page}.
     *
     * @param Tag $tag The new {@link Tag} to add to the {@link Page}
     */
    public function addTag(Tag $tag): self
    {
        $this->tags[] = $tag;
        return $this;
    }

    /**
     * @return $this
     */
    public function removeTags(): self
    {
        $this->tags->clear();
        return $this;
    }

    /**
     * Returns the current posts date as a YYYY/mm/dd URL.
     *
     * @return string The date part of the post's URL
     */
    public function getPostDateAsLink(): string
    {
        if (empty($this->postDate)) {
            return '';
        }
        return $this->postDate->format('Y') .
            '/' . $this->postDate->format('m') .
            '/' . $this->postDate->format('d');
    }

    /**
     * Confirms the status being set to the {@link Page} is valid.
     *
     * @param string|null $value The string to test as being a valid status
     * @return bool Result of testing if string is draft or published
     */
    public function isValidStatus(?string $value): bool
    {
        return $value === self::DRAFT || $value === self::PUBLISHED;
    }

    /**
     * Determines of a provided string is a valid Timezone defined in PHP (>5.2).
     *
     * @param string $timezone The string to test
     * @return bool The result of testing if string is a valid Timezone
     */
    public function isValidTimezone(string $timezone): bool
    {
        return in_array($timezone, DateTimeZone::listIdentifiers());
    }

    /**
     * Determines if current page is scheduled for publishing.
     *
     * @return bool Result of testing if {@link post_date} is in the future
     * @throws Exception
     */
    public function isScheduledPage(): bool
    {
        $today = new DateTime('now', new DateTimeZone($this->getTimezone()));
        $postDate = new DateTime(
            $this->getPostDate()->format('Y-m-d H:i:s'),
            new DateTimeZone($this->getTimezone())
        );

        return $this->getStatus() == Page::PUBLISHED && $postDate->format('YmdHis') > $today->format('YmdHis');
    }

    /**
     * Determines if the current page/post is not yet published.
     *
     * @return bool The result of testing if the page is a draft
     */
    public function isDraft(): bool
    {
        return $this->status === self::DRAFT;
    }

    /**
     * @return bool Check if {@link Page::$content} contains external images
     */
    public function hasHotlinkedImages(): bool
    {
        preg_match('/!\[[^]]*]\(https?:/', $this->getContent(), $matches);
        return !empty($matches);
    }

    /**
     * @return bool Flag indicating if this content type can be exported
     */
    public static function isExportable(): bool
    {
        return true;
    }

    /**
     * @return string The name to use for this content type when referred to by export, etc.
     */
    public static function getName(): string
    {
        return 'Pages and Posts';
    }
}
