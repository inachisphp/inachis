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
use App\Entity\Revision;
use App\Parser\ArrayToMarkdown;
use App\Repository\RevisionRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Jfcherng\Diff\DiffHelper;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class RevisionController extends AbstractInachisController
{
    /**
     * @param Request $request
     * @return Response
     */
    #[Route("/incc/page/diff/{id}", methods: [ "GET" ])]
    public function diff(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $revision = $this->entityManager->getRepository(Revision::class)->findOneBy([
            'id' => $request->attributes->get('id')
        ]);
        if (empty($revision) || empty($revision->getPageId())) {
            throw new NotFoundHttpException(
                sprintf('Version history could not be found for %s', $request->attributes->get('id'))
            );
        }
        $page = $this->entityManager->getRepository(Page::class)->findOneBy(['id' => $revision->getPageId()]);
        if (empty($page) || empty($page->getId())) {
            throw new NotFoundHttpException(
                sprintf('Page could not be found for revision %s', $request->attributes->get('id'))
            );
        }
        $trackChanges = [
            'content' => json_decode(DiffHelper::calculate(
                $revision->getContent() ?? '',
                $page->getContent() ?? '',
                'Json',
                [
                    'context' => 0,
                ],
                [
                    'outputTagAsString' => true,
                ]
            )),
        ];

        $this->data['page']['title'] = 'Compare Revisions';
        $this->data['title'] = json_decode(
            DiffHelper::calculate($revision->getTitle() ?? '', $page->getTitle() ?? '', 'Json', [], [
                'detailLevel' => 'word',
                'outputTagAsString' => true,
            ])
        );
        if (empty($this->data['title'])) {
            $this->data['title'] = $page->getTitle();
        }
        $this->data['subTitle'] = json_decode(
            DiffHelper::calculate($revision->getSubTitle() ?? '', $page->getSubTitle() ?? '', 'Json')
        );
        if (empty($this->data['subTitle'])) {
            $this->data['subTitle'] = $page->getSubTitle();
        }
        $this->data['content'] = mb_split(PHP_EOL, $revision->getContent());
        foreach ($trackChanges['content'] as $changeGroup) {
            foreach ($changeGroup as $change) {
                if (in_array($change->tag, ['rep', 'del'])) {
                    $this->data['content'][$change->old->offset] = $change;
                }
            }
        }
        $this->data['link'] = $page->getUrls()[0]->getLink();

        return $this->render('inadmin/page/post/track_changes.html.twig', $this->data);
    }

    #[Route("/incc/page/diff/{id}", methods: [ "POST" ])]
    public function doRevert(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $revision = $this->entityManager->getRepository(Revision::class)->findOneBy([
            'id' => $request->attributes->get('id')
        ]);
        if (empty($revision) || empty($revision->getPageId())) {
            throw new NotFoundHttpException(
                sprintf('Version history could not be found for %s', $request->attributes->get('id'))
            );
        }
        $page = $this->entityManager->getRepository(Page::class)->findOneBy(['id' => $revision->getPageId()]);
        if (empty($page) || empty($page->getId())) {
            throw new NotFoundHttpException(
                sprintf('Page could not be found for revision %s', $request->attributes->get('id'))
            );
        }
        $page->setTitle($revision->getTitle())
            ->setSubTitle($revision->getSubTitle())
            ->setContent($revision->getContent())
            ->setModDate(new DateTime('now'))
            ->setAuthor($this->getUser())
        ;

        $newRevision = $this->entityManager->getRepository(Revision::class)->hydrateNewRevisionFromPage($page);
        $newRevision->setAction(sprintf(RevisionRepository::REVERTED, $revision->getVersionNumber()));

        $this->entityManager->persist($newRevision);
        $this->entityManager->persist($page);
        $this->entityManager->flush();

        $this->addFlash('notice', sprintf('Content reverted to version %s.', $revision->getVersionNumber()));
        return $this->redirect(
            '/incc/' .
            $page->getType() . '/' .
            $page->getUrls()[0]->getLink()
        );
    }

    /**
     * @param Request $request
     * @return Response
     */
    #[Route("/incc/page/download/{id}", name: "incc_post_download", methods: [ "GET" ])]
    public function download(Request $request, SerializerInterface $serializer): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $revision = $this->entityManager->getRepository(Revision::class)->findOneBy([
            'id' => $request->attributes->get('id')
        ]);
        if (empty($revision) || empty($revision->getPageId())) {
            throw new NotFoundHttpException(
                sprintf('Version history could not be found for %s', $request->attributes->get('id'))
            );
        }
        $normalisedAttributes = [
            'title',
            'subTitle',
            'postDate',
            'content',
            'featureSnippet',
            'featureImage',
        ];
        $post = [
            'title' => $revision->getTitle(),
            'subTitle' => $revision->getSubTitle(),
            'content' => $revision->getContent(),
        ];

        $response = new Response();
        $response->setContent(ArrayToMarkdown::parse($post));
        $filename = date('YmdHis') . '.md';

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
