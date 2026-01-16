<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Dialog;

use Inachis\Controller\AbstractInachisController;
use Inachis\Controller\ZipStream;
use Inachis\Entity\Page;
use Inachis\Entity\Tag;
use Inachis\Parser\ArrayToMarkdown;
use Inachis\Repository\PageRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

#[IsGranted('ROLE_ADMIN')]
class ExportDialogController extends AbstractInachisController
{
    /**
     * @param Request $request
     * @return Response
     */
    #[Route("/incc/ax/export/get", methods: [ "POST" ])]
    public function export(Request $request): Response
    {
        return $this->render('inadmin/dialog/export.html.twig', $this->data);
    }

    /**
     * @param Request $request
     * @param SerializerInterface $serializer
     * @return Response
     * @throws ExceptionInterface
     */
    #[Route("/incc/ax/export/output", name: "incc_dialog_export_perform", methods: [ "POST" ])]
    public function performExport(
        Request $request,
        SerializerInterface $serializer,
        PageRepository $pageRepository,
    ): Response {
        if (empty($request->request->all('postId'))) {
            return new Response(null, Response::HTTP_EXPECTATION_FAILED);
        }
        $posts = $pageRepository->getFilteredIds(
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
