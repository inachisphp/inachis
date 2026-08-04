<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\Page\Post;

use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\Content\Page;
use Inachis\Entity\Content\Url;
use Inachis\Entity\Media\Image;
use Inachis\Enum\EditorialStatus;
use Inachis\Enum\Security\PermissionAction;
use Inachis\Enum\Security\PermissionResource;
use Inachis\Form\PostType;
use Inachis\Model\Page\ViewStateDefaults;
use Inachis\Repository\Content\CategoryRepository;
use Inachis\Repository\Content\PageRepository;
use Inachis\Repository\Content\ReviewThreadRepository;
use Inachis\Repository\Content\RevisionRepository;
use Inachis\Repository\Media\ImageRepository;
use Inachis\Security\Attribute\RequiresPermission;
use Inachis\Service\Content\ContentRevisionCompare;
use Inachis\Service\Content\Page\CategoryManager;
use Inachis\Service\Content\Page\PageBulkActionService;
use Inachis\Service\Content\Page\ReviewRebaseService;
use Inachis\Service\Content\Page\TagManager;
use Inachis\Service\Content\Page\UrlManager;
use Inachis\Service\Content\ReadingTime;
use Inachis\Service\Content\ViewStateManager;
use Symfony\Component\Form\ClickableInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Page controller.
 */
class PageController extends AbstractInachisController
{
    public const ITEMS_TO_SHOW = 20;

    /**
     * List posts.
     *
     * @throws \Exception
     */
    #[Route(
        '/incp/{type}/list/{limit}/{offset}',
        name: 'incp_post_list',
        requirements: [
            'type' => 'post|page',
            'limit' => "\d+",
            'offset' => "\d+",
        ],
        defaults: ['limit' => 10, 'offset' => 0],
        methods: ['GET', 'POST'],
    )]
    #[RequiresPermission(
        resource: PermissionResource::PAGE,
        action: PermissionAction::VIEW,
    )]
    public function list(
        Request $request,
        CategoryRepository $categoryRepository,
        PageBulkActionService $pageBulkActionService,
        PageRepository $pageRepository,
        ViewStateManager $viewStateManager,
        string $type = 'post',
    ): Response {
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && !empty($request->request->all('items'))) {
            /** @var array<string,string>|array{} */
            $items = $request->request->all('items');
            $action = $request->request->has('delete') ? 'delete' :
                ($request->request->has('private') ? 'private' :
                ($request->request->has('public') ? 'public' : null));

            if (null !== $action) {
                $count = $pageBulkActionService->apply($action, $items);
                $this->addFlash('success', "Action '$action' applied to $count $type.");
            }

            return $this->redirectToRoute(
                'incp_post_list',
                ['type' => $type],
            );
        }

        if ($request->query->has('issue')) {
            $request->request->set('filter', [
                'issues' => $request->query->getString('issue', ''),
            ]);
        }

        $params = $viewStateManager->load(
            $request,
            $type,
            new ViewStateDefaults(
                sort: 'postDate desc',
                view: 'list',
            ),
        );

        if ($request->isMethod(Request::METHOD_POST)) {
            $viewStateManager->update(
                $request,
                $type,
                $params,
                $categoryRepository,
            );

            return $this->redirectToRoute('incp_post_list', [
                'type' => $request->attributes->getString('type'),
                'limit' => $request->attributes->getInt('limit'),
                'offset' => 0,
            ]);
        }

        $posts = $pageRepository->getFilteredOfTypeByPostDate(
            $params->getFilters(),
            $type,
            $params->getLimit(),
            $params->getOffset(),
            $params->getSort(),
        );

        $this->viewModel->page->title = ucfirst($type).'s';
        $this->viewModel->page->tab = $type;

        return $this->render('inadmin/page/post/list.html.twig', [
            'viewModel' => $this->viewModel,
            'form' => $form->createView(),
            'posts' => $posts,
            'query' => $params,
            'queryString' => $queryString ?? '',
        ]);
    }

    /**
     * Edit post.
     *
     * @throws \Exception
     */
    #[Route(
        '/incp/{type}/{title}',
        name: 'incp_post_edit',
        requirements: ['type' => 'page|post'],
        defaults: ['type' => 'post'],
        methods: ['GET', 'POST'],
        priority: -10,
    )]
    #[Route(
        '/incp/{type}/{year}/{month}/{day}/{title}',
        name: 'incp_post_edit_1',
        requirements: [
            'type' => 'post',
            'year' => "\d+",
            'month' => "\d+",
            'day' => "\d+",
        ],
        methods: ['GET', 'POST'],
    )]
    #[RequiresPermission(
        resource: PermissionResource::PAGE,
        action: PermissionAction::VIEW,
    )]
    public function edit(
        Request $request,
        CategoryManager $categoryManager,
        ContentRevisionCompare $contentRevisionCompare,
        ImageRepository $imageRepository,
        PageBulkActionService $pageBulkActionService,
        PageRepository $pageRepository,
        RevisionRepository $revisionRepository,
        ReviewThreadRepository $reviewThreadRepository,
        ReviewRebaseService $reviewRebaseService,
        TagManager $tagManager,
        UrlManager $urlManager,
        string $type = 'post',
        ?string $title = null,
    ): Response {
        $url = preg_replace('/\/?incp\/(page|post)\/?/', '', $request->getRequestUri());
        $url = $this->entityManager->getRepository(Url::class)->findBy(['link' => $url]);
        $title = 'new' === $title ? null : $title;
        // If content with this URL doesn't exist, then redirect
        if (empty($url) && null !== $title) {
            return $this->redirectToRoute(
                'incp_post_list',
                ['type' => $type],
            );
        }
        $post = null !== $title ?
            ($pageRepository->findOneBy(['id' => $url[0]->getContent()->getId()]) ?: new Page()) :
            $post = new Page();
        if (null === $post->getId()) {
            $post->setType($type);
        }
        if (!empty($post->getId())) {
            $revision = $revisionRepository->hydrateNewRevisionFromPage($post);
            $revision = $revision->setAction(RevisionRepository::UPDATED);
        }
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);
        if ($post->getId()) {
            $threads = $reviewThreadRepository->findOpenForPage($post);
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            foreach ($form->getErrors(true) as $error) {
                dump($error->getOrigin()->getName(), $error->getMessage());
            }
        }
        if ($form->isSubmitted() && $form->isValid()) {
            $delete = $form->has('delete') ? $form->get('delete') : null;
            $review = $form->has('review') ? $form->get('review') : null;
            $publish = $form->has('publish') ? $form->get('publish') : null;

            // Handle delete action
            if ($delete instanceof ClickableInterface && $delete->isClicked()) {
                $pageBulkActionService->delete($post);

                return $this->redirectToRoute(
                    'incp_post_list',
                    ['type' => $type],
                );
            }

            $imageId = $form->get('featureImage')->getData();
            if ($imageId) {
                $image = $imageRepository->find($imageId);
                $post->setFeatureImage($image);
            }

            // Update post
            $post->setAuthor($this->getCurrentUser());
            $post->setUpdatedAt(new \DateTimeImmutable());
            $data = $request->request->all('post');
            $urlManager->apply($post, is_string($data['url']) ? $data['url'] : '');
            $categoryManager->apply($post, is_string($data['categories']) ? $data['categories'] : '');
            $tagManager->apply($post, is_string($data['tags']) ? $data['tags'] : '');

            // Send the {@link Page} for review
            if ($review instanceof ClickableInterface && $review->isClicked()) {
                $post->setStatus(EditorialStatus::REVIEW);
            }

            // Publish the {@link Page}
            if ($publish instanceof ClickableInterface && $publish->isClicked()) {
                $post->setStatus(EditorialStatus::PUBLISHED);

                // Auto-resolve or close any remaining open review threads
                if ($post->getId()) {
                    foreach ($threads as $thread) {
                        $thread->resolve($this->getCurrentUser());
                        $this->entityManager->persist($thread);
                    }
                }
            }

            // Update revisions to show published
            if (isset($revision)) {
                if ($contentRevisionCompare->doesPageMatchRevision($post, $revision)) {
                    $revision->setContent('');
                }
                if (EditorialStatus::PUBLISHED === $post->getStatus()) {
                    $revision->setAction(RevisionRepository::PUBLISHED);
                }
            }

            if (!empty($post->getId()) && isset($revision)) {
                $this->entityManager->persist($revision);
            }
            $this->entityManager->persist($post);
            if (isset($threads)) {
                foreach ($threads as $thread) {
                    $reviewRebaseService->rebase($thread, $post->getContent() ?: '');
                }
            }
            $this->entityManager->flush();

            $this->addFlash('success', 'Content saved.');
            $firstLink = $post->getUrls()[0];

            return $this->redirect(
                '/incp/'.
                $post->getType().'/'.
                $firstLink?->getLink(),
            );
        }

        $this->viewModel->page->title = null !== $post->getId() ?
            'Editing "'.$post->getTitle().'"' :
            'New '.$post->getType();
        $this->viewModel->page->tab = $post->getType();

        return $this->render('inadmin/page/post/edit.html.twig', [
            'viewModel' => $this->viewModel,
            'allowedTypes' => Image::ALLOWED_MIME_TYPES,
            'form' => $form->createView(),
            'includeEditor' => true,
            'includeEditorId' => $post->getId()?->toString() ?: '',
            'post' => $post,
            'revisions' => $revisionRepository->getRevisionsForPage($post),
            'textStats' => null !== $post->getId() ? ReadingTime::getWordCountAndReadingTime($post->getContent()) : [],
        ]);
    }
}
