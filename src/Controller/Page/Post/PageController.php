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
use Inachis\Entity\Content\Page;
use Inachis\Entity\Content\Url;
use Inachis\Entity\Media\Image;
use Inachis\Enum\EditorialStatus;
use Inachis\Form\PostType;
use Inachis\Model\ContentQueryParameters;
use Inachis\Model\Page\ViewStateDefaults;
use Inachis\Repository\Content\{CategoryRepository, PageRepository, ReviewThreadRepository, RevisionRepository};
use Inachis\Repository\Media\ImageRepository;
use Inachis\Service\Content\Page\CategoryManager;
use Inachis\Service\Content\Page\PageBulkActionService;
use Inachis\Service\Content\Page\ReviewRebaseService;
use Inachis\Service\Content\Page\TagManager;
use Inachis\Service\Content\Page\UrlManager;
use Inachis\Service\Content\{ContentRevisionCompare, ReadingTime, ViewStateManager};
use Symfony\Component\Form\ClickableInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Page controller
 */
#[IsGranted('ROLE_ADMIN')]
class PageController extends AbstractInachisController
{
    public const ITEMS_TO_SHOW = 20;

    /**
     * List posts

     * @param Request $request
     * @param PageBulkActionService $pageBulkActionService
     * @param PageRepository $pageRepository
     * @param string $type
     * @return Response
     * @throws Exception
     */
    #[Route(
        "/incc/{type}/list/{limit}/{offset}",
        name: "incc_post_list",
        requirements: [
            "type" => "post|page",
            "limit" => "\d+",
            "offset" => "\d+",
        ],
        defaults: [ "limit" => 10, "offset" => 0, ],
        methods: [ "GET", "POST" ]
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
            $action = $request->request->has('delete')  ? 'delete' :
                ($request->request->has('private') ? 'private' :
                ($request->request->has('public') ? 'public' : null));

            if ($action !== null) {
                $count = $pageBulkActionService->apply($action, $items);
                $this->addFlash('success', "Action '$action' applied to $count $type.");
            }
            return $this->redirectToRoute(
                'incc_post_list',
                [ 'type' => $type ]
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

            return $this->redirectToRoute('incc_post_list', [
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

        $this->viewModel->page->title = ucfirst($type) . 's';
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
     * Edit post
     *
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
            ($pageRepository->findOneBy(['id' => $url[0]->getContent()->getId()]) ?: new Page()) :
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
        $threads = $reviewThreadRepository->findOpenForPage($post);

        if($form->isSubmitted() && !$form->isValid()) {
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
                    'incc_post_list',
                    [ 'type' => $type ]
                );
            }

            $imageId = $form->get('featureImage')->getData();
            if ($imageId) {
                $image = $imageRepository->find($imageId);
                $post->setFeatureImage($image);
            }

            // Update post
            $post->setAuthor($this->getCurrentUser());
            $post->setUpdatedAt(new DateTimeImmutable());
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
                foreach ($threads as $thread) {
                    $thread->resolve($this->getCurrentUser());
                    $this->entityManager->persist($thread);
                }
            }

            // Update revisions to show published
            if (isset($revision)) {
                if ($contentRevisionCompare->doesPageMatchRevision($post, $revision)) {
                    $revision->setContent('');
                }
                if ($post->getStatus() === EditorialStatus::PUBLISHED) {
                    $revision->setAction(RevisionRepository::PUBLISHED);
                }
            }

            if (!empty($post->getId()) && isset($revision)) {
                $this->entityManager->persist($revision);
            }
            $this->entityManager->persist($post);
            foreach ($threads as $thread) {
                $reviewRebaseService->rebase($thread, $post->getContent() ?: '');
            }
            $this->entityManager->flush();

            $this->addFlash('success', 'Content saved.');
            $firstLink = $post->getUrls()[0];
            return $this->redirect(
                '/incc/' .
                $post->getType() . '/' .
                $firstLink?->getLink()
            );
        }

        $this->viewModel->page->title = $post->getId() !== null ?
            'Editing "' . $post->getTitle() . '"' :
            'New ' . $post->getType();
        $this->viewModel->page->tab = $post->getType();

        return $this->render('inadmin/page/post/edit.html.twig', [
            'viewModel' => $this->viewModel,
            'allowedTypes' => Image::ALLOWED_MIME_TYPES,
            'form' => $form->createView(),
            'includeEditor' => true,
            'includeEditorId' => $post->getId()?->toString() ?: '',
            'post' => $post,
            'revisions' => $revisionRepository->getRevisionsForPage($post),
            'textStats' => $post->getId() !== null ? ReadingTime::getWordCountAndReadingTime($post->getContent()) : [],
        ]);
    }
}
