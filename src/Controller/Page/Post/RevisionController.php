<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Post;

use Inachis\Controller\AbstractInachisController;
use Inachis\Parser\ArrayToMarkdown;
use Inachis\Repository\PageRepository;
use Inachis\Repository\RevisionRepository;
use DateTime;
use Exception;
use Jfcherng\Diff\DiffHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[IsGranted('ROLE_ADMIN')]
class RevisionController extends AbstractInachisController
{
    /**
     * @param Request $request
     * @param PageRepository $pageRepository
     * @param RevisionRepository $revisionRepository
     * @return Response
     */
    #[Route("/incc/page/diff/{id}", methods: [ "GET" ])]
    public function diff(
        Request $request,
        PageRepository $pageRepository,
        RevisionRepository $revisionRepository
    ): Response {
        [$revision, $page] = $this->loadPageWithRevision($request, $revisionRepository, $pageRepository);
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
        $this->data['page']['tab'] = 'post';
        $this->data['title'] = json_decode(
            DiffHelper::calculate(
                $revision->getTitle() ?? '',
                $page->getTitle() ?? '',
                'Json',
                [],
                [
                    'detailLevel' => 'word',
                    'outputTagAsString' => true,
                ]
            )
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
        $this->data['revision_id'] = $revision->getId();
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

    /**
     * @throws Exception
     */
    #[Route("/incc/page/diff/{id}", methods: [ "POST" ])]
    public function doRevert(
        Request $request,
        PageRepository $pageRepository,
        RevisionRepository $revisionRepository,
    ): Response {
        [$revision, $page] = $this->loadPageWithRevision($request, $revisionRepository, $pageRepository);
        $page->setTitle($revision->getTitle())
            ->setSubTitle($revision->getSubTitle())
            ->setContent($revision->getContent())
            ->setModDate(new DateTime('now'))
            ->setAuthor($this->getUser());

        $newRevision = $revisionRepository->hydrateNewRevisionFromPage($page);
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
     * @param RevisionRepository $revisionRepository
     * @param SerializerInterface $serializer
     * @return Response
     */
    #[Route("/incc/page/download/{id}", name: "incc_post_download", methods: [ "GET" ])]
    public function download(
        Request $request,
        RevisionRepository $revisionRepository,
        SerializerInterface $serializer
    ): Response {
        $revision = $revisionRepository->findOneBy([
            'id' => $request->attributes->get('id')
        ]);
        if (empty($revision) || empty($revision->getPageId())) {
            throw new NotFoundHttpException(
                sprintf('Version history could not be found for %s', $request->attributes->get('id'))
            );
        }
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

    /**
     * @param Request $request
     * @param RevisionRepository $revisionRepository
     * @param PageRepository $pageRepository
     * @return array
     */
    private function loadPageWithRevision(Request $request, RevisionRepository $revisionRepository, PageRepository $pageRepository): array
    {
        $revision = $revisionRepository->findOneBy([
            'id' => $request->attributes->get('id')
        ]);
        if (empty($revision) || empty($revision->getPageId())) {
            throw new NotFoundHttpException(
                sprintf('Version history could not be found for %s', $request->attributes->get('id'))
            );
        }
        $page = $pageRepository->findOneBy(['id' => $revision->getPageId()]);
        if (empty($page) || empty($page->getId())) {
            throw new NotFoundHttpException(
                sprintf('Page could not be found for revision %s', $request->attributes->get('id'))
            );
        }

        return [$revision, $page];
    }
}
