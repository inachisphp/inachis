<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Waste;

use DateTimeImmutable;
use Inachis\Entity\Content\{Category, Page, Series, Tag, Url};
use Inachis\Entity\Media\Image;
use Inachis\Entity\User\User;
use Inachis\Entity\Waste\Waste;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Manages the waste bin for the application
 */
class WasteManagerService
{
    /**
     * Inject the dependencies

     * @param EntityManagerInterface $entityManager
     * @param Security $security
     * @param Filesystem $filesystem
     * @param string $imageDirectory
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private Filesystem $filesystem,
        #[Autowire('%kernel.project_dir%/public/imgs/')]
        private string $imageDirectory
    ) {}

    /**
     * Send an entity to the waste bin
     *
     * @param object $entity
     */
    public function sendToWaste(object $entity): void
    {
        $waste = new Waste();
        $waste->setUser($this->security->getUser());
        $waste->setModDate(new DateTimeImmutable());

        $data = [
            'id' => $entity->getId(),
        ];

        if ($entity instanceof Page) {
            $waste->setSourceType('Page');
            $waste->setSourceName($entity->getTitle());
            $waste->setTitle($entity->getTitle());
            $data['title'] = $entity->getTitle();
            $data['subTitle'] = $entity->getSubTitle();
            $data['content'] = $entity->getContent();
            $data['author'] = $entity->getAuthor()?->getId();
            $data['status'] = $entity->getStatus();
            $data['visibility'] = $entity->getVisibility();
            $data['postDate'] = $entity->getPostDate()?->format('Y-m-d H:i:s');
            $data['timezone'] = $entity->getTimezone();
            $data['password'] = $entity->getPassword();
            $data['allowComments'] = $entity->isAllowComments();
            $data['type'] = $entity->getType();
            $data['featureSnippet'] = $entity->getFeatureSnippet();
            if ($entity->getFeatureImage()) {
                $data['featureImage'] = $entity->getFeatureImage()->getId();
            }
            $data['categories'] = [];
            foreach ($entity->getCategories() as $category) {
                $data['categories'][] = $category->getId();
            }
            $data['tags'] = [];
            foreach ($entity->getTags() as $tag) {
                $data['tags'][] = $tag->getId();
            }
            $data['urls'] = [];
            foreach ($entity->getUrls() as $url) {
                $data['urls'][] = ['link' => $url->getLink(), 'default' => $url->isDefault()];
            }

        } elseif ($entity instanceof Series) {
            $waste->setSourceType('Series');
            $waste->setSourceName($entity->getTitle());
            $waste->setTitle($entity->getTitle());
            $data['title'] = $entity->getTitle();
            $data['subTitle'] = $entity->getSubTitle();
            $data['description'] = $entity->getDescription();
            $data['author'] = $entity->getAuthor()?->getId();
            $data['visibility'] = $entity->getVisibility();
            $data['url'] = $entity->getUrl();
            if ($entity->getImage()) {
                $data['image'] = $entity->getImage()->getId();
            }
            $data['items'] = [];
            foreach ($entity->getItems() as $item) {
                $data['items'][] = $item->getId();
            }

        } elseif ($entity instanceof Image) {
            $waste->setSourceType('Image');
            $waste->setSourceName($entity->getFilename());
            $waste->setTitle($entity->getTitle());
            $data['title'] = $entity->getTitle();
            $data['description'] = $entity->getDescription();
            $data['altText'] = $entity->getAltText();
            $data['filename'] = $entity->getFilename();
            $data['filetype'] = $entity->getFiletype();
            $data['filesize'] = $entity->getFilesize();
            $data['checksum'] = $entity->getChecksum();
            $data['dimensionX'] = $entity->getDimensionX();
            $data['dimensionY'] = $entity->getDimensionY();
            $data['author'] = $entity->getAuthor()?->getId();

            $sourcePath = $this->imageDirectory . $entity->getFilename();
            $wastePath = $this->imageDirectory . '.waste/' . $entity->getFilename();
            if ($this->filesystem->exists($sourcePath)) {
                if (!$this->filesystem->exists($this->imageDirectory . '.waste/')) {
                    $this->filesystem->mkdir($this->imageDirectory . '.waste/');
                }
                $this->filesystem->rename($sourcePath, $wastePath);
            }
        } else {
            throw new \InvalidArgumentException('Unsupported entity type for waste');
        }

        $waste->setContent(json_encode($data));

        $this->entityManager->persist($waste);
        $this->entityManager->flush();
    }

    /**
     * Restore an entity from the waste bin
     *
     * @param Waste $waste
     */
    public function restore(Waste $waste): void
    {
        $data = json_decode($waste->getContent(), true);
        if (!$data) {
            throw new \RuntimeException('Failed to decode waste content');
        }

        switch ($waste->getSourceType()) {
            case 'Page':
                $page = $this->entityManager->getRepository(Page::class)->findOneBy(['id' => $data['id']]);
                if (!$page) {
                    $page = new Page();
                    $page->setId(Uuid::fromString($data['id']));
                }
                $page->setTitle($data['title']);
                $page->setSubTitle($data['subTitle'] ?? null);
                $page->setContent($data['content'] ?? null);
                $page->setStatus($data['status'] ?? EditorialStatus::DRAFT);
                $page->setVisibility($data['visibility'] ?? Page::PRIVATE);
                if (!empty($data['postDate'])) {
                    $page->setPostDate(new DateTimeImmutable($data['postDate']));
                }
                $page->setTimezone($data['timezone'] ?? 'UTC');
                $page->setPassword($data['password'] ?? null);
                if (isset($data['allowComments'])) {
                    $page->setAllowComments($data['allowComments']);
                }
                $page->setType($data['type'] ?? 'post');
                $page->setFeatureSnippet($data['featureSnippet'] ?? null);

                if (!empty($data['author'])) {
                    $author = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $data['author']]);
                    if ($author) {
                        $page->setAuthor($author);
                    }
                }
                if (!empty($data['featureImage'])) {
                    $image = $this->entityManager->getRepository(Image::class)->findOneBy(['id' => $data['featureImage']]);
                    if ($image) {
                        $page->setFeatureImage($image);
                    }
                }
                if (!empty($data['categories'])) {
                    foreach ($data['categories'] as $catId) {
                        $cat = $this->entityManager->getRepository(Category::class)->findOneBy(['id' => $catId]);
                        if ($cat) $page->getCategories()->add($cat);
                    }
                }
                if (!empty($data['tags'])) {
                    foreach ($data['tags'] as $tagId) {
                        $tag = $this->entityManager->getRepository(Tag::class)->findOneBy(['id' => $tagId]);
                        if ($tag) $page->getTags()->add($tag);
                    }
                }
                if (!empty($data['urls'])) {
                    foreach ($data['urls'] as $urlData) {
                        $urlExists = $this->entityManager->getRepository(Url::class)->findOneBy(['link' => $urlData['link']]);
                        if (!$urlExists) {
                            $url = new Url($page, $urlData['link']);
                            $url->setDefault($urlData['default']);
                            $this->entityManager->persist($url);
                        }
                    }
                }
                $this->entityManager->persist($page);
                break;

            case 'Series':
                $series = $this->entityManager->getRepository(Series::class)->findOneBy(['id' => $data['id']]);
                if (!$series) {
                    $series = new Series();
                    $series->setId(Uuid::fromString($data['id']));
                }
                $series->setTitle($data['title']);
                $series->setSubTitle($data['subTitle'] ?? null);
                $series->setDescription($data['description'] ?? null);
                $series->setVisibility($data['visibility'] ?? Series::PRIVATE);
                $series->setUrl($data['url'] ?? null);

                if (!empty($data['author'])) {
                    $author = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $data['author']]);
                    if ($author) {
                        $series->setAuthor($author);
                    }
                }
                if (!empty($data['image'])) {
                    $image = $this->entityManager->getRepository(Image::class)->findOneBy(['id' => $data['image']]);
                    if ($image) {
                        $series->setImage($image);
                    }
                }
                if (!empty($data['items'])) {
                    foreach ($data['items'] as $itemId) {
                        $item = $this->entityManager->getRepository(Page::class)->findOneBy(['id' => $itemId]);
                        if ($item) $series->getItems()->add($item);
                    }
                }
                $this->entityManager->persist($series);
                break;

            case 'Image':
                $image = $this->entityManager->getRepository(Image::class)->findOneBy(['id' => $data['id']]);
                if (!$image) {
                    $image = new Image();
                    $image->setId(Uuid::fromString($data['id']));
                }
                $image->setTitle($data['title']);
                $image->setDescription($data['description'] ?? null);
                $image->setAltText($data['altText'] ?? null);
                $image->setFilename($data['filename']);
                $image->setFiletype($data['filetype'] ?? 'image/jpeg');
                $image->setFilesize($data['filesize'] ?? 0);
                $image->setChecksum($data['checksum'] ?? '');
                $image->setDimensionX($data['dimensionX'] ?? 0);
                $image->setDimensionY($data['dimensionY'] ?? 0);

                if (!empty($data['author'])) {
                    $author = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $data['author']]);
                    if ($author) {
                        $image->setAuthor($author);
                    }
                }

                $wastePath = $this->imageDirectory . '.waste/' . $image->getFilename();
                $targetPath = $this->imageDirectory . $image->getFilename();
                if ($this->filesystem->exists($wastePath)) {
                    $this->filesystem->rename($wastePath, $targetPath);
                }

                $this->entityManager->persist($image);
                break;

            default:
                throw new \InvalidArgumentException('Unknown source type in waste for restore');
        }

        $this->entityManager->remove($waste);
        $this->entityManager->flush();
    }

    /**
     * Delete an entity from the waste bin
     *
     * @param Waste $waste
     */
    public function deleteWaste(Waste $waste): void
    {
        if ($waste->getSourceType() === 'Image') {
            $data = json_decode($waste->getContent(), true);
            if ($data && !empty($data['filename'])) {
                $wastePath = $this->imageDirectory . '.waste/' . $data['filename'];
                if ($this->filesystem->exists($wastePath)) {
                    $this->filesystem->remove($wastePath);
                }
            }
        }
        $this->entityManager->remove($waste);
        $this->entityManager->flush();
    }
}
