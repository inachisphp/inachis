<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Post;

use DateTimeImmutable;
use Exception;
use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\Content\Url;
use Inachis\Service\Parser\ArrayToMarkdown;
use Inachis\Repository\Content\{PageRepository, RevisionRepository};
use Inachis\Service\Content\Page\RevisionDiffRenderer;
use Jfcherng\Diff\DiffHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class RevisionController extends AbstractInachisController
{
    /**
     * Displays the difference between current page and the revision
     * 
     * @param Request $request
     * @param PageRepository $pageRepository
     * @param RevisionRepository $revisionRepository
     * @return Response
     */
    #[Route("/incc/page/diff/{id}", methods: [ "GET" ])]
    public function diff(
        Request $request,
        PageRepository $pageRepository,
        RevisionDiffRenderer $renderer,
        RevisionRepository $revisionRepository
    ): Response {
        [$revision, $page] = $this->loadPageWithRevision($request, $revisionRepository, $pageRepository);

        /** @var string */
        $trackChangesRaw = DiffHelper::calculate(
            $revision->getContent() ?? '',
            $page->getContent() ?? '',
            'Json'
            ,
            [],
            [
                'detailLevel' => 'char',
                'outputTagAsString' => true,
            ],
        );
        if (!empty($trackChangesRaw)) {
            /** @var list<list<
             *     array{
             *         tag: string,
             *         old: array{offset: int, lines: list<string>},
             *         new: array{offset: int, lines: list<string>}
             *     }|array{}
             * >>|array{}|false|null */
            $trackChanges = json_decode($trackChangesRaw, true, 512, JSON_THROW_ON_ERROR);
        }
        if (empty($trackChanges)) {
            $trackChanges = [];
        }
        $url = $page->getUrls()->first();
        if (!$url instanceof Url) {
            throw new \RuntimeException('Page has no URLs');
        }

        $this->viewModel->page->title = 'Compare Revisions';
        $this->viewModel->page->tab = 'post';

        
        $title = json_decode(
            DiffHelper::calculate(
                $revision->getTitle() ?? '',
                $page->getTitle(),
                'Json',
                ['context' => 0],
                [
                    'detailLevel' => 'char',
                    'outputTagAsString' => true,
                ]
            )
        );
        if (empty($title)) {
            $title = $page->getTitle();
        }
        $subTitle = json_decode(
            DiffHelper::calculate($revision->getSubTitle() ?? '', $page->getSubTitle() ?? '', 'Json')
        );
        if (empty($subTitle)) {
            $subTitle = $page->getSubTitle();
        }
        
        $content = mb_split(
            PHP_EOL,
            $revision->getContent() ?? ''
        );

        foreach ($trackChanges as $changeGroup) {
            foreach ($changeGroup as $change) {
                if (isset($change['tag'])
                    && in_array($change['tag'], ['rep', 'del'], true)
                    && isset($content[$change['old']['offset']])
                ) {
                    $content[$change['old']['offset']] = $change;
                }
            }
        }

        return $this->render('inadmin/page/post/track_changes.html.twig', [
            'viewModel' => $this->viewModel,
            'content' => $content ? $renderer->render($content) : '',
            'diffBlockType' => \Inachis\Enum\DiffBlockType::class,
            'page_id' => $page->getId(),
            'revision_id' => $revision->getId(),
            'subTitle' => $subTitle,
            'title' => $title,
            'url' => $url->getLink(),
        ]);
    }

    /**
     * Reverts to the selected version
     * 
     * @param Request $request
     * @param PageRepository $pageRepository
     * @param RevisionRepository $revisionRepository
     * @return Response
     * @throws Exception
     */
    #[Route("/incc/page/diff/{id}", methods: [ "POST" ])]
    public function doRevert(
        Request $request,
        PageRepository $pageRepository,
        RevisionRepository $revisionRepository,
    ): Response {
        [$revision, $page] = $this->loadPageWithRevision($request, $revisionRepository, $pageRepository);
        $page->setTitle($revision->getTitle() ?? '')
            ->setSubTitle($revision->getSubTitle())
            ->setContent($revision->getContent())
            ->setModDate(new DateTimeImmutable())
            ->setAuthor($this->getCurrentUser());

        $newRevision = $revisionRepository->hydrateNewRevisionFromPage($page);
        $newRevision->setAction(sprintf(RevisionRepository::REVERTED, $revision->getVersionNumber()));

        $this->entityManager->persist($newRevision);
        $this->entityManager->persist($page);
        $this->entityManager->flush();

        $url = $page->getUrls()->first();
        if (!$url instanceof Url) {
            throw new \RuntimeException('Page has no URLs');
        }

        $this->addFlash('notice', sprintf('Content reverted to version %s.', $revision->getVersionNumber()));
        return $this->redirect(
            '/incc/' .
            $page->getType() . '/' .
            $url->getLink()
        );
    }

    /**
     * Downloads a copy of the specified version as .md file
     * 
     * @param Request $request
     * @param RevisionRepository $revisionRepository
     * @return Response
     */
    #[Route("/incc/page/download/{id}", name: "incc_post_download", methods: [ "GET" ])]
    public function download(
        Request $request,
        RevisionRepository $revisionRepository,
    ): Response {
        $revision = $revisionRepository->findOneBy([
            'id' => $request->attributes->getString('id')
        ]);
        if (empty($revision) || empty($revision->getPageId())) {
            throw new NotFoundHttpException(
                sprintf('Version history could not be found for %s', $request->attributes->getString('id'))
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
     * Fetches an array containing the revision and page
     * 
     * @param Request $request
     * @param RevisionRepository $revisionRepository
     * @param PageRepository $pageRepository
     * @return list{0: \Inachis\Entity\Content\Revision, 1: \Inachis\Entity\Content\Page}
     */
    private function loadPageWithRevision(
        Request $request,
        RevisionRepository $revisionRepository,
        PageRepository $pageRepository
    ): array {
        $revision = $revisionRepository->findOneBy([
            'id' => $request->attributes->getString('id')
        ]);
        if (empty($revision) || empty($revision->getPageId())) {
            throw new NotFoundHttpException(
                sprintf('Version history could not be found for %s', $request->attributes->getString('id'))
            );
        }
        $page = $pageRepository->findOneBy(['id' => $revision->getPageId()]);
        if (empty($page) || empty($page->getId())) {
            throw new NotFoundHttpException(
                sprintf('Page could not be found for revision %s', $request->attributes->getString('id'))
            );
        }

        return [$revision, $page];
    }
}
