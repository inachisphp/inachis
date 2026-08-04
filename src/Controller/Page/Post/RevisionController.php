<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\Page\Post;

use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\Content\Url;
use Inachis\Enum\Security\PermissionAction;
use Inachis\Enum\Security\PermissionResource;
use Inachis\Repository\Content\PageRepository;
use Inachis\Repository\Content\RevisionRepository;
use Inachis\Security\Attribute\RequiresPermission;
use Inachis\Service\Content\Page\RevisionDiffRenderer;
use Inachis\Service\Parser\ArrayToMarkdown;
use Jfcherng\Diff\DiffHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class RevisionController extends AbstractInachisController
{
    /**
     * Displays the difference between current page and the revision.
     */
    #[Route('/incp/page/diff/{id}', methods: ['GET'])]
    public function diff(
        Request $request,
        PageRepository $pageRepository,
        RevisionDiffRenderer $renderer,
        RevisionRepository $revisionRepository,
    ): Response {
        [$revision, $page] = $this->loadPageWithRevision($request, $revisionRepository, $pageRepository);

        /** @var string */
        $trackChangesRaw = DiffHelper::calculate(
            $revision->getContent() ?? '',
            $page->getContent() ?? '',
            'Json',
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
                ],
            ),
        );
        if (empty($title)) {
            $title = $page->getTitle();
        }
        $subTitle = json_decode(
            DiffHelper::calculate($revision->getSubTitle() ?? '', $page->getSubTitle() ?? '', 'Json'),
        );
        if (empty($subTitle)) {
            $subTitle = $page->getSubTitle();
        }

        $content = mb_split(
            PHP_EOL,
            $revision->getContent() ?? '',
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
     * Reverts to the selected version.
     *
     * @throws \Exception
     */
    #[Route('/incp/page/diff/{id}', methods: ['POST'])]
    #[RequiresPermission(
        resource: PermissionResource::PAGE,
        action: PermissionAction::EDIT,
    )]
    public function doRevert(
        Request $request,
        PageRepository $pageRepository,
        RevisionRepository $revisionRepository,
    ): Response {
        [$revision, $page] = $this->loadPageWithRevision($request, $revisionRepository, $pageRepository);
        $page->setTitle($revision->getTitle() ?? '')
            ->setSubTitle($revision->getSubTitle())
            ->setContent($revision->getContent())
            ->setUpdatedAt(new \DateTimeImmutable())
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
            '/incp/'.
            $page->getType().'/'.
            $url->getLink(),
        );
    }

    /**
     * Downloads a copy of the specified version as .md file.
     */
    #[Route('/incp/page/download/{id}', name: 'incp_post_download', methods: ['GET'])]
    public function download(
        Request $request,
        RevisionRepository $revisionRepository,
    ): Response {
        $revision = $revisionRepository->findOneBy([
            'id' => $request->attributes->getString('id'),
        ]);
        if (empty($revision) || empty($revision->getPage())) {
            throw new NotFoundHttpException(sprintf('Version history could not be found for %s', $request->attributes->getString('id')));
        }
        $post = [
            'title' => $revision->getTitle(),
            'subTitle' => $revision->getSubTitle(),
            'content' => $revision->getContent(),
        ];

        $response = new Response();
        $response->setContent(ArrayToMarkdown::parse($post));
        $filename = date('YmdHis').'.md';

        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $filename,
            ),
        );

        return $response;
    }

    /**
     * Fetches an array containing the revision and page.
     *
     * @return list{0: \Inachis\Entity\Content\Revision, 1: \Inachis\Entity\Content\Page}
     */
    private function loadPageWithRevision(
        Request $request,
        RevisionRepository $revisionRepository,
    ): array {
        $revision = $revisionRepository->findOneBy([
            'id' => $request->attributes->getString('id'),
        ]);
        if (empty($revision) || empty($revision->getPage())) {
            throw new NotFoundHttpException(sprintf('Version history could not be found for %s', $request->attributes->getString('id')));
        }
        $page = $revision->getPage();
        if (empty($page) || empty($page->getId())) {
            throw new NotFoundHttpException(sprintf('Page could not be found for revision %s', $request->attributes->getString('id')));
        }

        return [$revision, $page];
    }
}
