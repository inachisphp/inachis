<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Service\Page;

use App\Entity\Page;
use App\Entity\Url;
use App\Parser\MarkdownFileParser;
use App\Repository\UrlRepository;
use App\Util\UrlNormaliser;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class PageFileImportService
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected UrlRepository $urlRepository,
    ) {}

    /**
     * @throws Exception
     */
    public function processFile($file): int
    {
        $postObjects = [];
        switch ($file->getMimeType()) {
            case 'application/json':
                $postObjects = array_merge(
                    [],
                    json_decode(file_get_contents($file->getRealPath()))
                );
                break;

            case 'application/zip':
                // @todo: Implement ZIP parser
                break;

            default:
                // parse just MD file
                $parser = new MarkdownFileParser($this->entityManager);
                $postObjects = array_merge(
                    [],
                    [ $parser->parse($file->getContent()) ],
                );
        }

        foreach ($postObjects as $object) {
            $post = $object;
            if (\gettype($object) === 'object' && get_class($object) !== 'Page') {
                $post = new Page(
                    $object->title ?? '',
                    $object->content ?? '',
                    null,
                    $object->type ?? Page::TYPE_POST
                );
                $post->setSubTitle($object->subTitle ?? '');
                $post->setPostDate(
                    date_create_from_format(
                        DateTimeInterface::ISO8601,
                        $object->postDate
                    ) ?? time()
                );
            }
            if ($post->getTitle() !== '' && $post->getContent() !== '') {
                $newLink = $post->getPostDateAsLink() . '/' .
                    UrlNormaliser::toUri(
                        $post->getTitle() .
                        ($post->getSubTitle() !== '' ? ' ' . $post->getSubTitle() : '')
                    );
                if (!empty($this->urlRepository->findOneBy(['link' => $newLink]))) {
                    // @todo should it prompt to rename?
                    return 409;
                }
                $post->setAuthor($this->get('security.token_storage')->getToken()->getUser());
                new Url(
                    $post,
                    $newLink
                );
                $this->entityManager->persist($post);
                $this->entityManager->flush();
            } else {
                return 400;
            }
        }

        return 200;
    }
}