<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Post;

use DateTime;
use Exception;
use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\Category;
use Inachis\Entity\Image;
use Inachis\Entity\Page;
use Inachis\Entity\Revision;
use Inachis\Entity\Tag;
use Inachis\Entity\Url;
use Inachis\Form\PostType;
use Inachis\Model\ContentQueryParameters;
use Inachis\Repository\PageRepository;
use Inachis\Repository\RevisionRepository;
use Inachis\Service\Page\PageBulkActionService;
use Inachis\Util\ContentRevisionCompare;
use Inachis\Util\ReadingTime;
use Inachis\Util\UrlNormaliser;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class PageController extends AbstractInachisController
{
    public const ITEMS_TO_SHOW = 20;

    /**
     * @param Request $request
     * @param ContentQueryParameters $contentQueryParameters
     * @param PageBulkActionService $pageBulkActionService
     * @param PageRepository $pageRepository
     * @param string $type
     * @return Response
     * @throws Exception
     */
    #[Route(
        "/incc/{type}/list/{offset}/{limit}",
        name: "incc_post_list",
        requirements: [
            "type" => "post|page",
            "offset" => "\d+",
            "limit" => "\d+"
        ],
        defaults: [ "offset" => 0, "limit" => 10 ],
        methods: [ "GET", "POST" ]
    )]
    public function list(
        Request $request,
        ContentQueryParameters $contentQueryParameters,
        PageBulkActionService $pageBulkActionService,
        PageRepository $pageRepository,
        string $type = 'post',

    ): Response {
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && !empty($request->request->all('items'))) {
            $items = $request->request->all('items') ?? [];
            $action = $request->request->has('delete')  ? 'delete' :
                ($request->request->has('private') ? 'private' :
                ($request->request->has('public') ? 'public' : null));

            if ($action !== null && !empty($items)) {
                $count = $pageBulkActionService->apply($action, $items);
                $this->addFlash('success', "Action '$action' applied to $count $type.");
            }
            return $this->redirectToRoute(
                'incc_post_list',
                [ 'type' => $type ]
            );
        }

        $contentQuery = $contentQueryParameters->process(
            $request,
            $pageRepository,
            'post',
            'postDate desc',
        );
        $this->data['form'] = $form->createView();
        $this->data['posts'] = $pageRepository->getFilteredOfTypeByPostDate(
            $contentQuery['filters'],
            $type,
            $contentQuery['offset'],
            $contentQuery['limit'],
            $contentQuery['sort'],
        );
        $this->data['query'] = $contentQuery;
        $this->data['page']['tab'] = $type;
        $this->data['page']['title'] = ucfirst($type) . 's';
        return $this->render('inadmin/page/post/list.html.twig', $this->data);
    }

    /**
     * @param Request $request
     * @param ContentRevisionCompare $contentRevisionCompare
     * @param string $type
     * @param string|null $title
     * @return Response
     * @throws Exception
     */
    #[Route(
        "/incc/{type}/{title}",
        name: "incc_post_edit",
        requirements: [ "type" => "page|post"],
        defaults: [ "type" => "post" ],
        methods: [ "GET", "POST" ],
        priority: -10,
    )]
    #[Route(
        "/incc/{type}/{year}/{month}/{day}/{title}",
        name: "incc_post_edit_1",
        requirements: [
            "type" => "post",
            "year" => "\d+",
            "month" => "\d+",
            "day" => "\d+"
        ],
        methods: [ "GET", "POST" ]
    )]
    public function edit(
        Request $request,
        ContentRevisionCompare $contentRevisionCompare,
        PageRepository $pageRepository,
        RevisionRepository $revisionRepository,
        string $type = 'post',
        ?string $title = null
    ): Response {
        $url = preg_replace('/\/?incc\/(page|post)\/?/', '', $request->getRequestUri());
        $url = $this->entityManager->getRepository(Url::class)->findBy(['link' => $url]);
        $title = $title === 'new' ? null : $title;
        // If content with this URL doesn't exist, then redirect
        if (empty($url) && null !== $title) {
            return $this->redirectToRoute(
                'incc_post_list',
                ['type' => $type]
            );
        }
        $post = null !== $title ?
            $pageRepository->findOneBy(['id' => $url[0]->getContent()->getId()]) :
            $post = new Page();
        if ($post->getId() === null) {
            $post->setType($type);
        }
        if (!empty($post->getId())) {
            $revision = $revisionRepository->hydrateNewRevisionFromPage($post);
            $revision = $revision->setAction(RevisionRepository::UPDATED);
        }
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {//} && $form->isValid()) {
            if ($form->has('delete') && $form->get('delete')->isClicked()) {
                $revisionRepository->deleteAndRecordByPage($post);
                $pageRepository->remove($post);
                return $this->redirectToRoute(
                    'incc_dashboard',
                    [],
                    Response::HTTP_PERMANENTLY_REDIRECT
                );
            }
            $post->setAuthor($this->getUser());
            if (null !== $request->request->get('publish')) {
                $post->setStatus(Page::PUBLISHED);
                if (isset($revision)) {
                    if ($contentRevisionCompare->doesPageMatchRevision($post, $revision)) {
                        $revision->setContent('');
                    }
                    $revision->setAction(RevisionRepository::PUBLISHED);
                }
            }
            if (!empty($request->request->all('post')['url'])) {
                $newUrl = $request->request->all('post')['url'];
                $urlFound = false;
                if (!empty($post->getUrls())) {
                    foreach ($post->getUrls() as $url) {
                        if ($url->getLink() !== $newUrl) {
                            $url->setDefault(false);
                        } else {
                            $urlFound = true;
                        }
                    }
                }
                if (!$urlFound) {
                    new Url($post, $newUrl);
                }
            }
            $post = $post->removeCategories()->removeTags();
            if (!empty($request->request->all('post')['categories'])) {
                $newCategories = $request->request->all('post')['categories'];
                if (!empty($newCategories)) {
                    foreach ($newCategories as $newCategory) {
                        $category = null;
                        if (Uuid::isValid($newCategory)) {
                            $category = $this->entityManager->getRepository(Category::class)->findOneBy(['id' => $newCategory]);
                        }
                        if (!empty($category)) {
                            $post->getCategories()->add($category);
                        }
                    }
                }
            }
            if (!empty($request->request->all('post')['tags'])) {
                $newTags = $request->request->all('post')['tags'];
                if (!empty($newTags)) {
                    foreach ($newTags as $newTag) {
                        $tag = null;
                        if (Uuid::isValid($newTag)) {
                            $tag = $this->entityManager->getRepository(Tag::class)->findOneBy(['id' => $newTag]);
                        }
                        if (empty($tag)) {
                            $tag = new Tag($newTag);
                        }
                        $post->getTags()->add($tag);
                    }
                }
            }
            if (!empty($request->request->all('post')['featureImage'])) {
                $post->setFeatureImage(
                    $this->entityManager->getRepository(Image::class)->findOneBy([
                        'id' => $request->request->all('post')['featureImage']
                    ])
                );
            }

            if ($form->has('publish') && $form->get('publish')->isClicked()) {
                $post->setStatus(Page::PUBLISHED);
                if (isset($revision)) {
                    $revision->setAction(RevisionRepository::PUBLISHED);
                }
            }

            $post->setModDate(new DateTime('now'));
            if (!empty($post->getId())) {
                $this->entityManager->persist($revision);
            }
            $this->entityManager->persist($post);
            $this->entityManager->flush();

            $this->addFlash('success', 'Content saved.');
            return $this->redirect(
                '/incc/' .
                $post->getType() . '/' .
                $post->getUrls()[0]->getLink()
            );
        }

        $this->data['form'] = $form->createView();
        $this->data['page']['tab'] = $post->getType();
        $this->data['page']['title'] = $post->getId() !== null ?
            'Editing "' . $post->getTitle() . '"' :
            'New ' . $post->getType();
        $this->data['includeEditor'] = true;
        $this->data['includeEditorId'] = $post->getId();
        $this->data['post'] = $post;
        $this->data['revisions'] = $revisionRepository->getAll(
            0,
            25,
            [
                'q.page_id = :pageId', [
                    'pageId' => $post->getId(),
                ]
            ], [
                [ 'q.versionNumber', 'DESC']
            ]
        );
        if ($post->getId() !== null) {
            $this->data['textStats'] = ReadingTime::getWordCountAndReadingTime($this->data['post']->getContent());
        }
        return $this->render('inadmin/page/post/edit.html.twig', $this->data);
    }
}
