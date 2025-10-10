<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;

/**
 * Object for handling categories on a site.
 */
#[ORM\Entity(repositoryClass: 'App\Repository\CategoryRepository', readOnly: false)]
#[ORM\Index(columns: ['title'], name: 'search_idx')]
class Category
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
     * @var string|null The name of the category
     */
    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    protected ?string $title = '';

    /**
     * @var string|null Description of the category
     */
    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $description = '';

    /**
     * @var Image|null The UUID of the image
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?Image $image = null;

    /**
     * @var Image|null The UUID of the image
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?Image $icon = null;

    /**
     * @var bool Whether this category should be visible
     */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    protected bool $visible = true;

    /**
     * @var Category|null The parent category, if self is not a top-level category
     */
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Category', inversedBy: 'children')]
    protected ?Category $parent = null;

    /**
     * @var Collection|null The array of child categories if applicable
     */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: 'App\Entity\Category')]
    #[ORM\OrderBy(['title' => 'ASC'])]
    protected ?Collection $children;

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
     * @return string|null The title of the {@link Category} - cannot be empty
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Returns the value of {@link description}.
     *
     * @return ?string The description of the {@link Category}
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Returns the value of {@link image}.
     *
     * @return Image|null The image for {@link Category}
     */
    public function getImage(): ?Image
    {
        return $this->image;
    }

    /**
     * Returns the value of {@link icon}.
     *
     * @return Image|null The 'icon' for the {@link Category}
     */
    public function getIcon(): ?Image
    {
        return $this->icon;
    }

    /**
     * @return bool
     */
    public function isVisible(): bool
    {
        return $this->visible;
    }

    /**
     * Returns the value of {@link parent}.
     *
     * @return Category|null The parent {@link Category} if applicable
     */
    public function getParent(): ?Category
    {
        return $this->parent;
    }

    /**
     * Returns all child categories for the current {@link Category}.
     *
     * @return Collection
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    /**
     * Sets the value of {@link id}.
     *
     * @param UuidInterface $value The UUID of the {@link Category}
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
     * @param string|null $value The title of the {@link Category}
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
     * @param string|null $value The description of the {@link Category}
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
     * @param Image|null $value The UUID or URL of the image for {@link Category}
     * @return $this
     */
    public function setImage(?Image $value): self
    {
        $this->image = $value;

        return $this;
    }

    /**
     * Sets the value of {@link icon}.
     *
     * @param Image|null $value The UUID or URL of the image for {@link Category}
     * @return $this
     */
    public function setIcon(?Image $value): self
    {
        $this->icon = $value;

        return $this;
    }

    /**
     * @param bool $value
     * @return self
     */
    public function setVisible(bool $value): self
    {
        $this->visible = $value;

        return $this;
    }

    /**
     * Sets the value of {@link parent}.
     *
     * @param Category|null $parent The parent of the current category
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
