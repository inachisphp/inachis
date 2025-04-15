<?php

namespace App\Controller;

use App\Entity\Page;
use App\Entity\Url;
use App\Parser\MarkdownFileParser;
use App\Utils\UrlNormaliser;
use Doctrine\ORM\EntityManager;
use PHPUnit\Util\Json;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ImportController extends AbstractInachisController
{
    #[Route("/incc/import", methods: [ "GET" ])]
    public function index(): Response
    {
        // @todo change text if handheld device
        return $this->render('inadmin/import__main.html.twig', $this->data);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    #[Route("/incc/import", methods: [ "POST", "PUT" ])]
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
                        \DateTimeInterface::ISO8601,
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
                if (!empty(
                    $this->entityManager->getRepository(Url::class)->findOneByLink($newLink)
                )) {
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
