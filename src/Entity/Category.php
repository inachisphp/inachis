<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;

/**
 * Object for handling categories on a site.
 */
#[ORM\Entity(repositoryClass: 'App\Repository\CategoryRepository', readOnly: false)]
#[ORM\Index(name: 'search_idx', columns: ['title'])]
class Category
{
    /**
     * @var \Ramsey\Uuid\UuidInterface The unique id of the category
     */
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    protected $id;

    /**
     * @var string The name of the category
     */
    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    protected $title;

    /**
     * @var string Description of the category
     */
    #[ORM\Column(type: 'text')]
    protected $description;

    /**
     * @var string The UUID of the image, or the image path
     */
    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    protected $image;

    /**
     * @var string The UUID of the image, or the image path
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected $icon;

    /**
     * @var Category The parent category, if self is not a top-level category
     */
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Category', inversedBy: 'children')]
    protected $parent;

    /**
     * @var ArrayCollection|Category[] The array of child categories if applicable
     */
    #[ORM\OneToMany(targetEntity: 'App\Entity\Category', mappedBy: 'parent')]
    #[ORM\OrderBy(['title' => 'ASC'])]
    protected $children;

    /**
     * Default constructor for {@link Category}.
     *
     * @param string $title       The title of the category
     * @param string $description The description for the category
     */
    public function __construct(string $title = '', string $description = '')
    {
        $this->setTitle($title);
        $this->setDescription($description);
        $this->children = new ArrayCollection();
    }

    /**
     * Returns the value of {@link id}.
     *
     * @return string The UUID of the {@link Category}
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Returns the value of {@link title}.
     *
     * @return string The title of the {@link Category} - cannot be empty
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Returns the value of {@link description}.
     *
     * @return string The description of the {@link Category}
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Returns the value of {@link image}.
     *
     * @return string The image for {@link Category}
     */
    public function getImage(): ?string
    {
        return $this->image;
    }

    /**
     * Returns the value of {@link icon}.
     *
     * @return string The 'icon' for the {@link Category}
     */
    public function getIcon(): ?string
    {
        return $this->icon;
    }

    /**
     * Returns the value of {@link parent}.
     *
     * @return Category The parent {@link Category} if applicable
     */
    public function getParent(): ?Category
    {
        return $this->parent;
    }

    /**
     * Returns all child categories for the current {@link Category}.
     *
     * @return ArrayCollection|Category[]
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    /**
     * Sets the value of {@link id}.
     *
     * @param string $value The UUID of the {@link Category}
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
     * @param string $value The title of the {@link Category}
     * @return $this
     */
    public function setTitle(string $value): self
    {
        $this->title = $value;

        return $this;
    }

    /**
     * Sets the value of {@link description}.
     *
     * @param string $value The description of the {@link Category}
     * @return $this
     */
    public function setDescription(?string $value): self
    {
        $this->description = $value;

        return $this;
    }

    /**
     * Sets the value of {@link image}.
     *
     * @param string $value The UUID or URL of the image for {@link Category}
     * @return $this
     */
    public function setImage(?string $value): self
    {
        $this->image = $value;

        return $this;
    }

    /**
     * Sets the value of {@link icon}.
     *
     * @param string $value The UUID or URL of the image for {@link Category}
     * @return $this
     */
    public function setIcon(?string $value): self
    {
        $this->icon = $value;

        return $this;
    }

    /**
     * Sets the value of {@link parent}.
     *
     * @param Category $parent The parent of the current category
     * @return $this
     */
    public function setParent(?Category $parent = null): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Adds a child category to the current {@link Category}.
     *
     * @param Category $category The {@link Category} to add
     */
    public function addChild(Category $category): void
    {
        $this->children[] = $category;
    }

    /**
     * Returns the result of testing if current category is a root category.
     *
     * @return bool Result of testing if {@link Category} is a root category
     */
    public function isRootCategory(): bool
    {
        return empty($this->getParent());
    }

    /**
     * Returns the result of testing if the current category is a child category.
     *
     * @return bool Result of testing if {@link Category} is a child category
     */
    public function isChildCategory(): bool
    {
        return !empty($this->getParent());
    }

    /**
     * Returns the result of testing if the category has an image to use.
     *
     * @return bool Result of testing if {@link image} is empty
     */
    public function hasImage(): bool
    {
        return !empty($this->getImage());
    }

    /**
     * Returns the result of testing if the category has an icon to use.
     *
     * @return bool Result of testing if {@link icon} is empty
     */
    public function hasIcon(): bool
    {
        return !empty($this->getIcon());
    }

    /**
     * Returns the full path for the category.
     *
     * @return string The path of the category
     */
    public function getFullPath(): string
    {
        if (!$this->isChildCategory()) {
            return $this->getTitle();
        }

        return $this->getParent()->getFullPath() . '/' . $this->getTitle();
    }
}
