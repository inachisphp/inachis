<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use App\Exception\InvalidTimezoneException;
//use phpDocumentor\Reflection\Types\Collection;

/**
 * Object for handling pages of a site.
 */
#[ORM\Entity(repositoryClass: 'App\Repository\PageRepository', readOnly: false)]
#[ORM\Index(name: 'search_idx', columns: ['title', 'author_id', 'image_id'])]
class Page
{
    /**
     * @const string Indicates a Page is currently in draft
     */
    const DRAFT = 'draft';

    /**
     * @const string Indicates a Page has been published
     */
    const PUBLISHED = 'published';

    /**
     * @const string Indicates a Page is public
     */
    const VIS_PUBLIC = true;

    /**
     * @const string Indicates a Page is private
     */
    const VIS_PRIVATE = false;

    /**
     * @const string Indicates a Page is standalone
     */
    const TYPE_PAGE = 'page';

    /**
     * @const string Indicates a Page is a blog post
     */
    const TYPE_POST = 'post';

    /**
     * @var \Ramsey\Uuid\UuidInterface
     */
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    protected $id;

    /**
     * @var string The title of the {@link Page}
     */
    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    protected $title;

    /**
     * @var string An optional sub-title for the {@link Page}
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected $subTitle = null;

    /**
     * @var string The contents of the {@link Page}
     */
    #[ORM\Column(type: 'text', nullable: true)]
    protected $content = null;

    /**
     * @var string The UUID of the author for the {@link Page}
     */
    #[ORM\ManyToOne(targetEntity: 'App\Entity\User', cascade: [ 'detach' ])]
    #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id')]
    protected $author;

    /**
     * @var Image The featured {@link Image} for the {@link Page}
     */
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Image', cascade: [ 'detach' ])]
    #[ORM\JoinColumn(name: 'image_id', referencedColumnName: 'id')]
    protected $featureImage;

    /**
     * @var string A short excerpt describing the contents of the {@link Page}
     */
    #[ORM\Column(type: 'text', nullable: true)]
    protected $featureSnippet;

    /**
     * @var string Current status of the {@link Page}, defaults to {@link DRAFT}
     */
    #[ORM\Column(type: 'string', length:20)]
    protected $status = self::DRAFT;

    /**
     * @var string Determining if a {@link Page} is visible to the public
     */
    #[ORM\Column(type: 'boolean', length: 20)]
    protected $visibility = self::VIS_PUBLIC;

    /**
     * @var string The date the {@link Page} was created
     */
    #[ORM\Column(type: 'datetime')]
    protected $createDate;

    /**
     * @var string The date the {@link Page} was published; a future date
     *             indicates the content is scheduled
     */
    #[ORM\Column(type: 'datetime')]
    protected $postDate;

    /**
     * @var string The date the {@link Page} was last modified
     */
    #[ORM\Column(type: 'datetime')]
    protected $modDate;

    /**
     * @var string The timezone for the publication date; defaults to UTC
     */
    #[ORM\Column(type: 'string', length: 50)]
    protected $timezone = 'UTC';

    /**
     * @var string A password to protect the {@link Page} with if required
     */
    #[ORM\Column(type: 'string', length:255, nullable: true)]
    protected $password;

    /**
     * @var bool Flag determining if the {@link Page} allows comments
     */
    #[ORM\Column(type: 'boolean', nullable: false)]
    protected $allowComments = false;

    /**
     * @var string The type of page. Default: {@link self::TYPE_POST}
     */
    #[ORM\Column(type: 'string', nullable: false)]
    protected $type = self::TYPE_POST;

    /**
     * @var string A location for geo-context aware content
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected $latlong;

    /**
     * @var string A short 140 character message to use when sharing content
     */
    #[ORM\Column(type: 'string', length: 140, nullable: true)]
    protected $sharingMessage;

    /**
     * @var ArrayCollection|Url[] The array of URLs for the content
     */
    #[ORM\OneToMany(targetEntity: 'App\Entity\Url', mappedBy: 'content', cascade: [ 'persist' ])]
    #[ORM\OrderBy(['default' => 'DESC'])]
    protected $urls;

    /**
     * @var ArrayCollection|Category[] The array of categories assigned to the post/page
     */
    #[ORM\ManyToMany(targetEntity: 'App\Entity\Category')]
    #[ORM\JoinTable(name: 'Page_categories')]
    #[ORM\JoinColumn(name: 'page_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'category_id', referencedColumnName: 'id')]
    #[ORM\OrderBy([ 'title' => 'ASC' ])]
    protected $categories;

    /**
     * @var ArrayCollection|Tag[] The array of tags assigned to the post/page
     */
    #[ORM\ManyToMany(targetEntity: 'App\Entity\Tag', cascade: [ 'persist' ])]
    #[ORM\JoinTable(name: 'Page_tags')]
    #[ORM\JoinColumn(name: 'page_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'tag_id', referencedColumnName: 'id')]
    #[ORM\OrderBy([ 'title' => 'ASC' ])]
    protected $tags;
    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Series", inversedBy="items")
     * @var Collection|Series[]  The array of Series that contains this page
     */
    protected $series;

    /**
     * @var Collection|Series[]  The array of Series that contains this page
     */
    #[ORM\ManyToMany(targetEntity: 'App\Entity\Series', inversedBy: 'items')]
    protected $series;

    /**
     * Default constructor for {@link Page}.
     *
     * @param string    $title   The title for the {@link Page}
     * @param string    $content The content for the {@link Page}
     * @param User|null $author  The {@link User} that authored the {@link Page}
     * @param string    $type    The type of {@link Page} - post or page
     * @throws \Exception
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
        $currentTime = new \DateTime('now');
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
     * @return string The UUID of the {@link Page}
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Returns the value of {@link title}.
     *
     * @return string The title of the {@link Page} - cannot be empty
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Returns the value of {@link subTitle}.
     *
     * @return string The Sub-title of the {@link Page}
     */
    public function getSubTitle(): ?string
    {
        return $this->subTitle;
    }

    /**
     * Returns the value of {@link content}.
     *
     * @return string The contents of the {@link Page}
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * Returns the value of {@link author}.
     *
     * @return string The UUID of the {@link Page} author
     */
    public function getAuthor(): ?User
    {
        return $this->author;
    }

    /**
     * Returns the value of {@link featureImage}.
     *
     * @return Image The feature image object
     */
    public function getFeatureImage(): ?Image
    {
        return $this->featureImage;
    }

    /**
     * Returns the value of {@link featureSnippet}.
     *
     * @return string The short excerpt to used as the feature
     */
    public function getFeatureSnippet(): ?string
    {
        return $this->featureSnippet;
    }

    /**
     * Returns the value of {@link status}.
     *
     * @return string The current publishing status of the {@link Page}
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
     * @return string The creation date of the {@link Page}
     */
    public function getCreateDate(): \DateTime
    {
        return $this->createDate;
    }

    /**
     * Returns the value of {@link postDate}.
     *
     * @return string The publication date of the {@link Page}
     */
    public function getPostDate(): \DateTime
    {
        return $this->postDate;
    }

    /**
     * Returns the value of {@link modDate}.
     *
     * @return string The date the {@link Page} was last modified
     */
    public function getModDate(): \DateTime
    {
        return $this->modDate;
    }

    /**
     * Returns the value of {@link timezone}.
     *
     * @return string The timezone for {@link post_date}
     */
    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    /**
     * Returns the value of {@link password}.
     *
     * @return string The hash of the password protecting the {@link Page}
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
     * @return string The current type
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Returns the latlong for the current {@link Page} entity.
     *
     * @return string The latitude and longitude of the content
     */
    public function getLatlong(): ?string
    {
        return $this->latlong;
    }

    /**
     * Returns the sharingMessage for the current {@link Page} entity.
     *
     * @return string The latitude and longitude of the content
     */
    public function getSharingMessage(): ?string
    {
        return $this->sharingMessage;
    }

    /**
     * Returns an array of URLs assigned to the page. The default URL will
     * always be first.
     *
     * @return Url[] The array of {$link Url} entities for the {@link Page}
     */
    public function getUrls(): ?Collection
    {
        return $this->urls;
    }

    /**
     * Returns an array of {@link Category)s assigned to the page.
     *
     * @return Category[] The array of {$link Category} entities for the {@link Page}
     */
    public function getCategories(): ?Collection
    {
        return $this->categories;
    }

    /**
     * Returns an array of {@link Tag)s assigned to the page.
     *
     * @return Tag[] The array of {$link Category} entities for the {@link Page}
     */
    public function getTags(): ?Collection
    {
        return $this->tags;
    }

    /**
     * Returns the Url with a specific index within the array.
     *
     * @param mixed $key The index of the item to return
     * @return Url The requested {@link Url} entry
     */
    public function getUrl($key = null): ?Collection
    {
        if ($key && !isset($this->urls[$key])) {
            throw new \InvalidArgumentException(sprintf('Url `%s` does not exist', $key));
        }

        return $this->urls[$key];
    }

    /**
     * @return Collection
     */
    public function getSeries(): ?Collection
    {
        return $this->series;
    }

    /**
     * Sets the value of {@link id}.
     *
     * @param string $value The UUID of the {@link Page}
     * @return $this
     */
    public function setId(string $value): self
    {
        $this->id = $value;

        return $this;
    }

    /**
     * Sets the value of {@link title}.
     *
     * @param string $value The title of the {@link Page}
     * @return $this
     */
    public function setTitle(string $value): self
    {
        $this->title = $value;

        return $this;
    }

    /**
     * Sets the value of {@link subTitle}.
     *
     * @param string|null $value The optional sub-title of the {@link Page}
     * @return $this
     */
    public function setSubTitle(?string $value = null): self
    {
        $this->subTitle = $value;

        return $this;
    }

    /**
     * Sets the value of {@link content}.
     *
     * @param string $value The contents of the {@link Page}
     * @return $this
     */
    public function setContent(?string $value): self
    {
        $this->content = $value;

        return $this;
    }

    /**
     * Sets the value of {@link author}.
     *
     * @param User $value The UUID of the {@link Page} author
     * @return $this
     */
    public function setAuthor(User $value = null): self
    {
        $this->author = $value;

        return $this;
    }

    /**
     * Sets the value of {@link featureImage}.
     *
     * @param Image $value The UUID or URL to use for the {@link feature_image}
     * @return $this
     */
    public function setFeatureImage(?string $value): self
    {
        $this->featureImage = $value;

        return $this;
    }

    /**
     * Sets the value of {@link featureSnippet}.
     *
     * @param string $value Short excerpt to use with the {@link feature_image}
     * @return $this
     */
    public function setFeatureSnippet(?string $value): self
    {
        $this->featureSnippet = $value;

        return $this;
    }

    /**
     * Sets the value of {@link status}.
     *
     * @param string $value The new publishing status of the {@link Page}
     * @return $this
     */
    public function setStatus(string $value): self
    {
        $this->status = $this->isValidStatus($value) ? $value : self::DRAFT;

        return $this;
    }

    /**
     * Sets the value of {@link visibility}.
     *
     * @param bool $value The visibility of the {@link Page}
     * @return $this
     */
    public function setVisibility(bool $value): self
    {
        $this->visibility = (bool) $value;

        return $this;
    }

    /**
     * Sets the value of {@link createDate}.
     *
     * @param \DateTime $value The date to be set
     * @return $this
     */
    public function setCreateDate(\DateTime $value = null): self
    {
        $this->createDate = $value;

        return $this;
    }

    /**
     * Sets the value of {@link postDate}.
     *
     * @param \DateTime $value The date to be set
     * @return $this
     */
    public function setPostDate(\DateTime $value = null): self
    {
        $this->postDate = $value;

        return $this;
    }

    /**
     * Sets the value of {@link modDate}.
     *
     * @param \DateTime $value The date to set
     * @return $this
     */
    public function setModDate(\DateTime $value = null): self
    {
        $this->modDate = $value;

        return $this;
    }

    /**
     * Sets the value of {@link timezone}.
     *
     * @param string $value The timezone for the post_date
     * @return $this
     * @throws InvalidTimezoneException
     */
    public function setTimezone(string $value): self
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
     * @param string $value The password to protect the {@link Page} with
     * @return $this
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
     * @return $this
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
     * @throws \Exception
     */
    public function setType(string $type): self
    {
        if (!in_array($type, [self::TYPE_POST, self::TYPE_PAGE])) {
            throw new \Exception(sprintf('`%s` is not a valid page type', $type));
        }
        $this->type = $type;

        return $this;
    }

    /**
     * Sets the current latitude and longitude of {@link Page} entity.
     *
     * @param string $value The latitude and longitude of the content
     * @return $this
     */
    public function setLatlong(?string $value): self
    {
        $this->latlong = $value;

        return $this;
    }

    /**
     * Sets the current sharingMessage of {@link Page} entity.
     *
     * @param string $value The sharingMessage of the content
     * @return $this
     */
    public function setSharingMessage(?string $value): self
    {
        $this->sharingMessage = $value;

        return $this;
    }

    /**
     * @param array $series
     */
    public function setSeries(array $series): void
    {
        $this->series = $series;
    }

    /**
     * Adds a {@link Url} to the {@link Page}.
     *
     * @param Url $url The new {@link Url} to add to the {@link Page}
     */
    public function addUrl(Url $url): void
    {
        $this->urls[] = $url;
    }

    /**
     * Adds a {@link Category} to the {@link Page}.
     *
     * @param Category $category The new {@link Category} to add to the {@link Page}
     */
    public function addCategory(Category $category): void
    {
        $this->categories[] = $category;
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
    public function addTag(Tag $tag): void
    {
        $this->tags[] = $tag;
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
        return $this->postDate->format('Y').
            '/'.$this->postDate->format('m').
            '/'.$this->postDate->format('d');
    }

    /**
     * Confirms the status being set to the {@link Page} is valid.
     *
     * @param string $value The string to test as being a valid status
     * @return bool Result of testing if string is draft or published
     */
    public function isValidStatus($value): bool
    {
        return $value === self::DRAFT || $value === self::PUBLISHED;
    }

    /**
     * Confirms the visibility being set to the {@link Page} is valid.
     *
     * @param string $value The visibility to test as being valid
     * @return bool Result of testing of the visibility is public or private
     */
    public function isValidVisibility($value): bool
    {
        return $value === self::VIS_PRIVATE || $value === self::VIS_PUBLIC;
    }

    /**
     * Determines of a provided string is a valid Timezone defined in PHP (>5.2).
     *
     * @param string $timezone The string to test
     * @return bool The result of testing if string is a valid Timezone
     */
    public function isValidTimezone($timezone): bool
    {
        return in_array($timezone, \DateTimeZone::listIdentifiers());
    }

    /**
     * Determines if current page is scheduled for publishing.
     *
     * @return bool Result of testing if {@link post_date} is in the future
     * @throws \Exception
     */
    public function isScheduledPage(): bool
    {
        $today = new \DateTime('now', new \DateTimeZone($this->getTimezone()));
        $postDate = new \DateTime(
            $this->getPostDate()->format('Y-m-d H:i:s'),
            new \DateTimeZone($this->getTimezone())
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

    public static function isExportable(): bool
    {
        return true;
    }

    public static function getName(): string
    {
        return 'Pages and Posts';
    }
}
