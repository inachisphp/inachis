<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Controller\Dialog;

use App\Controller\AbstractInachisController;
use App\Controller\ZipStream;
use App\Entity\Page;
use App\Entity\Tag;
use App\Parser\ArrayToMarkdown;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class ExportDialogController extends AbstractInachisController
{
    /**
     * @param Request $request
     * @return Response
     */
    #[Route("/incc/ax/export/get", methods: [ "POST" ])]
    public function export(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        return $this->render('inadmin/dialog/export.html.twig', $this->data);
    }

    /**
     * @param Request $request
     * @param SerializerInterface $serializer
     * @return Response
     * @throws ExceptionInterface
     */
    #[Route("/incc/ax/export/output", methods: [ "POST" ])]
    public function performExport(Request $request, SerializerInterface $serializer): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $posts = [];
        if (empty($request->request->all('postId'))) {
            return new Response(null, Response::HTTP_EXPECTATION_FAILED);
        }
        $posts = $this->entityManager->getRepository(Page::class)->getFilteredIds(
            $request->request->all('postId')
        )->getIterator()->getArrayCopy();
        if (empty($posts)) {
            return new Response(null, Response::HTTP_EXPECTATION_FAILED);
        }

        $normalisedAttributes = [
            'title',
            'subTitle',
            'postDate',
            'content',
            'featureSnippet',
            'featureImage',
        ];
        if (!empty($request->request->get('export_categories'))) {
            $normalisedAttributes[] = 'categories';
        }
        if (!empty($request->request->get('export_tags'))) {
            $normalisedAttributes[] = 'tags';
        }
        $posts = $serializer->normalize(
            $posts,
            null,
            [
                AbstractNormalizer::ATTRIBUTES => $normalisedAttributes,
            ]
        );

        $format = $request->request->get('export_format');
        $response = new Response();
        switch ($format) {
            case 'json':
                $posts = json_encode($posts);
                $response->headers->set('Content-Type', 'application/json');
                break;

            case 'xml':
                $encoder = new XmlEncoder();
                $posts = $encoder->encode($posts, '');
                $response->headers->set('Content-Type', 'text/xml');
                break;

            case 'md':
            default:
                $format = 'md';

                // https://maennchen.dev/ZipStream-PHP/guide/Symfony.html
                $zip = new ZipStream\ZipStream(
                    outputStream: $request->request->get('export_name') ?: date('YmdHis') . '.zip',
                    sendHttpHeaders: true,
                );
                foreach ($posts as $post) {
                    $zip->addFile(
                        filename: $post->getPostDate()->format('Ymd') . '.md',
                        data: ArrayToMarkdown::parse($post),
                    );
                }
                $zip->finish();
        }
        $response->setContent($posts);

        $filename = date('YmdHis');
        if (!empty($request->request->get('export_name'))) {
            $filename = $request->request->get('export_name');
        }
        $filename .= '.' . $format;

        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $filename
            )
        );

        return $response;
    }
}
