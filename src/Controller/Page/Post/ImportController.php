<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Controller\Page\Post;

use App\Controller\AbstractInachisController;
use App\Entity\Page;
use App\Entity\Url;
use App\Parser\MarkdownFileParser;
use App\Util\UrlNormaliser;
use DateTimeInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use function App\Controller\gettype;

class ImportController extends AbstractInachisController
{
    #[Route("/incc/import", name: "incc_post_import", methods: [ "GET" ])]
    public function index(): Response
    {
        // @todo change text if handheld device
        return $this->render('inadmin/page/post/import.html.twig', $this->data);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    #[Route("/incc/import", name: "incc_post_process", methods: [ "POST", "PUT" ])]
    public function process(Request $request): JsonResponse
    {
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);
        $lastResponse = $this->json('success', 200);

        foreach ($request->files->get('markdownFiles') as $file) {
            if ($file->getError() != UPLOAD_ERR_OK) {
                return $this->json('error', 400);
            }
            $lastResponse = $this->processFile($file);
            if ($lastResponse->getStatusCode() > 299) {
                break;
            }
        }
        return $lastResponse;
    }

    /**
     * @throws \Exception
     */
    private function processFile($file): string
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
                $parser = new MarkdownFileParser();
                $postObjects = array_merge(
                    [],
                    $parser->parse(
                        $this->entityManager,
                        file_get_contents($file->getRealPath())
                    )
                );
        }

        foreach ($postObjects as $object) {
            $post = $object;
            if (gettype($object) === 'object' && get_class($object) !== 'Page') {
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
                if (
                    !empty(
                        $this->entityManager->getRepository(Url::class)->findOneByLink($newLink)
                    )
                ) {
                    // @todo should it prompt to rename?
                    return $this->json('error', 409);
                }
                $post->setAuthor($this->get('security.token_storage')->getToken()->getUser());
                new Url(
                    $post,
                    $newLink
                );
                $this->entityManager->persist($post);
                $this->entityManager->flush();
            } else {
                return $this->json('error', 400);
            }
        }

        return $this->json('success', 200);
    }
}
