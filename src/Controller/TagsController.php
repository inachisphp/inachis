<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller;

use Inachis\Entity\Content\Tag;
use Inachis\Model\ContentQueryParameters;
use Inachis\Repository\PageRepository;
use Inachis\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class TagsController extends AbstractInachisController
{
    /**
     * Get tag list content for ajax requests
     *
     * @param Request $request
     * @param TagRepository $tagRepository
     * @return Response
     */
    #[Route("incc/ax/tagList/get", methods: [ "POST" ])]
    public function getTagManagerListContent(Request $request, TagRepository $tagRepository): Response
    {
        $tags = $tagRepository->findByTitleLike($request->request->get('q'));
        $result = [];
        // Below code is used to handle where tags exist with the same name under multiple locations
        if (!empty($tags)) {
            $result['items'] = [];
            foreach ($tags as $tag) {
                $title = $tag->getTitle();
                $result['items'][$title] = (object) [
                    'id'   => $tag->getId(),
                    'text' => $title,
                ];
            }
            $result = array_values($result['items']);
        }

        return new JsonResponse(
            [
                'items'      => $result,
                'totalCount' => count($result),
            ],
            Response::HTTP_OK
        );
    }

    /**
     * List all tags and provide ability to delete or merge
     *
     * @param Request $request
     * @param ContentQueryParameters $contentQueryParameters
     * @param PageRepository $pageRepository
     * @param TagRepository $tagRepository
     * @param int $offset
     * @param int $limit
     * @return Response
     */
    #[Route(
        '/incc/tags/{offset}/{limit}',
        name: 'incc_tags_list',
        requirements: [ "offset" => "\d+", "limit" => "\d+" ],
        defaults: [ "offset" => 0, "limit" => 20 ]
    )]
    public function index(
        Request $request,
        ContentQueryParameters $contentQueryParameters,
        PageRepository $pageRepository,
        TagRepository $tagRepository,
        int $offset = 0,
        int $limit = 25
    ): Response {
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && !empty($request->request->all('items'))) {
            $items = $request->request->all('items') ?? [];
            $action = $request->request->has('delete')  ? 'delete' : null;

            // @todo move the following foreach loop into a TagsBulkActionService and pass it $request
            foreach($items as $item) {
                $tag = $tagRepository->find($item);
                if ($tag === null) {
                    continue;
                }
                $pages = $pageRepository->getFilteredOfTypeByPostDate(['tags' => [$tag->getId()]], '*', 0, 0);
                if ($pages->getIterator()->count() > 0) {
                    $this->addFlash('error', 'Tag still in use - please remove tag from pages before deleting');
                    return $this->redirectToRoute('incc_tags_list');
                }

                $this->entityManager->remove($tag);
            }
            $this->entityManager->flush();

            return $this->redirectToRoute('incc_tags_list');
        }

        $contentQuery = $contentQueryParameters->process(
            $request,
            $tagRepository,
            'tag',
            'title',
        );
        $this->data['dataset'] = array_map(
            fn($row) => (object) [
                'id' => $row[0]->getId(),
                'title' => $row[0]->getTitle(),
                'url' => '/incc/tags/' . $row[0]->getId(),
                'usageCount' => $row['usageCount'],
            ],
            $tagRepository->findAllWithUsageCount($offset, $limit)
        );
        $this->data['form'] = $form->createView();
        $this->data['query'] = $contentQuery;
        $this->data['total'] = $tagRepository->getAllCount();
        $this->data['page']['title'] = 'Tags';
        $this->data['page']['tab'] = 'tag';
        return $this->render('inadmin/page/tag/list.html.twig', $this->data);
    }

    /**
     * Merge two tags, deleting the source tag and updating the target tag with any pages associated with the source tag
     *
     * @param Request $request
     * @param PageRepository $pageRepository
     * @param TagRepository $tagRepository
     * @return Response
     */
    #[Route('/incc/tags/merge', name: 'incc_tags_merge', methods: ['POST'])]
    public function mergeTags(
        Request $request,
        PageRepository $pageRepository,
        TagRepository $tagRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $targetId = $request->request->get('target');
        $sourceIds = $request->request->all('sources');

        if (!$targetId || empty($sourceIds)) {
            return new Response('Invalid request', 400);
        }

        $target = $tagRepository->find($targetId);
        if (!$target) {
            return new Response('Target not found', 404);
        }

        $sources = $tagRepository->findBy(['id' => $sourceIds]);

        foreach ($sources as $source) {
            if ($source->getId() === $target->getId()) {
                continue;
            }

            // Move pages across
            $pages = $pageRepository->getFilteredOfTypeByPostDate(['tags' => [$source->getId()]], '*', 0, 0);
            foreach ($pages as $page) {
                $page->removeTag($source);
                $page->addTag($target);
            }

            $entityManager->remove($source);
        }
        $entityManager->flush();

        return new Response('OK');
    }

    /**
     * Show tag and its pages
     *
     * @param Tag $tag
     * @param PageRepository $pageRepository
     * @return Response
     */
    #[Route('/incc/tags/{id}/{offset}/{limit}', name: 'incc_tag_show', requirements: [ "offset" => "\d+", "limit" => "\d+" ], defaults: [ "offset" => 0, "limit" => 25 ])]
    public function show(
        Tag $tag,
        PageRepository $pageRepository,
        ContentQueryParameters $contentQueryParameters,
        Request $request,
        int $offset = 0,
        int $limit = 25
    ): Response {
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && !empty($request->request->all('items'))) {
            $items = $request->request->all('items') ?? [];
            $action = $request->request->has('delete')  ? 'delete' : null;

            // @todo move the following foreach loop into a TagsBulkActionService and pass it $request
            foreach($items as $item) {
                $page = $pageRepository->findOneBy(['id' => $item]);
                if ($page === null) {
                    continue;
                }
                $page->removeTag($tag);
            }
            $this->entityManager->flush();

            return $this->redirectToRoute('incc_tag_show', ['id' => $tag->getId()]);
        }

        $pages = $pageRepository->getFilteredOfTypeByPostDate(['tags' => [$tag->getId()]], '*', $offset, $limit);

        $this->data['dataset'] = $pages;
        $this->data['form'] = $form->createView();
        $this->data['query'] = $contentQueryParameters->process(
            $request,
            $pageRepository,
            'page',
            'title'
        );
        $this->data['total'] = $pages->getIterator()->count();
        $this->data['page']['title'] = 'Tags';
        $this->data['page']['tab'] = 'settings';
        $this->data['tag'] = $tag;

        return $this->render('inadmin/page/tag/view.html.twig', $this->data);
    }
}
