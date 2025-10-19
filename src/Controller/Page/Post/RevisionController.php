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
use App\Repository\RevisionRepository;
use DateTime;
use Jfcherng\Diff\DiffHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class RevisionController extends AbstractInachisController
{
    /**
     * @param Request $request
     * @return Response
     */
    #[Route("/incc/page/diff/{id}", methods: [ "GET" ])]
    public function diff(Request $request): Response
    {
        $revision = $this->entityManager->getRepository(Revision::class)->findOneById(
            $request->get('id')
        );
        if (empty($revision) || empty($revision->getPageId())) {
            throw new NotFoundHttpException(
                sprintf('Version history could not be found for %s', $request->get('id'))
            );
        }
        $page = $this->entityManager->getRepository(Page::class)->findOneById($revision->getPageId());
        if (empty($page) || empty($page->getId())) {
            throw new NotFoundHttpException(
                sprintf('Page could not be found for revision %s', $request->get('id'))
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
        $revision = $this->entityManager->getRepository(Revision::class)->findOneById(
            $request->get('id')
        );
        if (empty($revision) || empty($revision->getPageId())) {
            throw new NotFoundHttpException(
                sprintf('Version history could not be found for %s', $request->get('id'))
            );
        }
        $page = $this->entityManager->getRepository(Page::class)->findOneById($revision->getPageId());
        if (empty($page) || empty($page->getId())) {
            throw new NotFoundHttpException(
                sprintf('Page could not be found for revision %s', $request->get('id'))
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
}
