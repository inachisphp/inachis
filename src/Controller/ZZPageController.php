<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Controller;

use App\Controller\AbstractInachisController;
use App\Entity\Category;
use App\Entity\Image;
use App\Entity\Page;
use App\Entity\Revision;
use App\Entity\Series;
use App\Entity\Tag;
use App\Entity\Url;
use App\Form\PostType;
use App\Repository\RevisionRepository;
use App\Util\ContentRevisionCompare;
use App\Util\ReadingTime;
use Doctrine\ORM\EntityManager;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use DateTime;

class ZZPageController extends AbstractInachisController
{
    public const ITEMS_TO_SHOW = 20;

    /**
     * @param Request $request
     * @param int $year
     * @param int $month
     * @param int $day
     * @param string $title
     * @return Response
     * @throws NotFoundHttpException
     */
    #[Route(
        "/{year}/{month}/{day}/{title}",
        requirements: [
            "year" => "\d+",
            "month" => "\d+",
            "day" => "\d+"
        ],
        methods: ["GET" ]
    )]
    public function getPost(Request $request, int $year, int $month, int $day, string $title): Response
    {
        $url = $this->entityManager->getRepository(Url::class)->findOneByLink(
            ltrim(strtok($request->getRequestUri(), '?'), '/')
        );
        if (empty($url)) {
            throw new NotFoundHttpException(
                sprintf(
                    '%s does not exist',
                    ltrim($request->getRequestUri(), '/')
                )
            );
        }
        if (
            ($url->getContent()->isScheduledPage() || $url->getContent()->isDraft())
            && !$this->security->isGranted('IS_AUTHENTICATED_FULLY')
        ) {
            return $this->redirectToRoute(
                'app_default_homepage',
                []
            );
        }
        if (!$url->isDefault()) {
            $url = $this->entityManager->getRepository(Url::class)->getDefaultUrl($url->getContent());
            if (!empty($url) && $url->isDefault()) {
                return new RedirectResponse('/' . $url->getLink(), Response::HTTP_PERMANENTLY_REDIRECT);
            }
        }
        $this->data['post'] = $url->getContent();
        $this->data['url'] = $url->getLink();
        $this->data['textStats'] = ReadingTime::getWordCountAndReadingTime($this->data['post']->getContent());

        $series = $this->entityManager->getRepository(Series::class)->getPublishedSeriesByPost($this->data['post']);
        if (!empty($series)) {
            $postIndex = $series->getItems()->indexOf($this->data['post']);
            $this->data['series'] = [
                'title' => $series->getTitle(),
                'subTitle' => $series->getSubTitle()
            ];
            if (!empty($series->getItems())) {
                if ($postIndex - 1 >= 0) {
                    $this->data['series']['previous'] = $series->getItems()->get($postIndex - 1);
                }
                if ($postIndex + 1 < $series->getItems()->count()) {
                    $this->data['series']['next'] = $series->getItems()->get($postIndex + 1);
                }
            }
        }
        $crawlerDetect = new CrawlerDetect();
        if (!$crawlerDetect->isCrawler()) {
            // @todo record page hit by day
        }
        return $this->render('web/post.html.twig', $this->data);
    }

    /**
     * @param Request $request
     * @param string $type
     * @return Response
     * @throws \Exception
     */
    #[Route(
        "/incc/{type}/list/{offset}/{limit}",
        requirements: [
            "type" => "post|page",
            "offset" => "\d+",
            "limit" => "\d+"
        ],
        defaults: [ "offset" => 0, "limit" => 10 ],
        methods: [ "GET", "POST" ]
    )]
    public function getPostListAdmin(Request $request, string $type = 'post'): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && !empty($request->get('items'))) {
            foreach ($request->get('items') as $item) {
                if ($request->request->has('delete')) {
                    $post = $this->entityManager->getRepository(Page::class)->findOneById($item);
                    if ($post !== null) {
                        $this->entityManager->getRepository(Revision::class)->deleteAndRecordByPage($post);
                        $this->entityManager->getRepository(Page::class)->remove($post);
                    }
                }
                if ($request->request->has('private') || $request->request->has('public')) {
                    $post = $this->entityManager->getRepository(Page::class)->findOneById($item);
                    if ($post !== null) {
                        $post->setVisibility(
                            $request->request->has('private') ? Page::PRIVATE : Page::PUBLIC
                        );
                        $post->setModDate(new DateTime('now'));
                        $this->entityManager->persist($post);
                    }
                }
//                if ($request->request->has('export')) {
//                    echo 'export';
//                    die;
//                }
            }
            if ($request->request->has('private') || $request->request->has('public')) {
                $revision = $this->entityManager->getRepository(Revision::class)->hydrateNewRevisionFromPage($post);
                $revision = $revision
                    ->setContent('')
                    ->setAction(sprintf(RevisionRepository::VISIBILITY_CHANGE, $post->getVisibility()));
                $this->entityManager->persist($revision);
                $this->entityManager->flush();
            }
            return $this->redirectToRoute(
                'app_zzpage_getpostlistadmin',
                [ 'type' => $type ]
            );
        }
        $filters = array_filter($request->get('filter', []));
        $sort = $request->get('sort', 'postDate desc');
        if ($request->isMethod('post')) {
            $_SESSION['post_filters'] = $filters;
            $_SESSION['sort'] = $sort;
        } elseif (isset($_SESSION['post_filters'])) {
            $filters = $_SESSION['post_filters'];
            $sort = $_SESSION['sort'];
        }

        $offset = (int) $request->get('offset', 0);
        $limit = $this->entityManager->getRepository(Page::class)->getMaxItemsToShow();
        $this->data['form'] = $form->createView();
        $this->data['posts'] = $this->entityManager->getRepository(Page::class)->getFilteredOfTypeByPostDate(
            $filters,
            $type,
            $offset,
            $limit,
            $sort
        );
        $this->data['filters'] = $filters;
        $this->data['page']['offset'] = $offset;
        $this->data['page']['limit'] = $limit;
        $this->data['page']['sort'] = $sort;
        $this->data['page']['tab'] = $type;
        $this->data['page']['title'] = ucfirst($type) . 's';
        return $this->render('inadmin/post__list.html.twig', $this->data);
    }

    /**
     * @param Request $request
     * @param ContentRevisionCompare $contentRevisionCompare
     * @param string $type
     * @param string $title
     * @return Response
     * @throws \Exception
     *
     * @return mixed
     */
    #[Route(
        "/incc/{type}/{title}",
        requirements: [
            "type" => "post|page"
        ],
        defaults: [ "type" => "post" ],
        methods: [ "GET", "POST" ]
    )]
    #[Route(
        "/incc/{type}/{year}/{month}/{day}/{title}",
        requirements: [
            "type" => "post",
            "year" => "\d+",
            "month" => "\d+",
            "day" => "\d+"
        ],
        methods: [ "GET", "POST" ]
    )]
    public function getPostAdmin(
        Request $request,
        ContentRevisionCompare $contentRevisionCompare,
        string $type = 'post',
        string $title = null
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $url = preg_replace('/\/?incc\/(page|post)\/?/', '', $request->getRequestUri());
        $url = $this->entityManager->getRepository(Url::class)->findByLink($url);
        $title = $title === 'new' ? null : $title;
        // If content with this URL doesn't exist, then redirect
        if (empty($url) && null !== $title) {
            return $this->redirectToRoute(
                'app_zzpage_getpostadmin',
                ['type' => $type]
            );
        }
        $post = null !== $title ?
            $this->entityManager->getRepository(Page::class)->findOneById($url[0]->getContent()->getId()) :
            $post = new Page();
        if ($post->getId() === null) {
            $post->setType($type);
        }
        if (!empty($post->getId())) {
            $revision = $this->entityManager->getRepository(Revision::class)->hydrateNewRevisionFromPage($post);
            $revision = $revision->setAction(RevisionRepository::UPDATED);
        }
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {//} && $form->isValid()) {
            if ($form->get('delete')->isClicked()) {
                $this->entityManager->getRepository(Page::class)->remove($post);
                $this->entityManager->getRepository(Revision::class)->deleteAndRecordByPage($post);
                return $this->redirectToRoute(
                    'app_dashboard_default',
                    [],
                    Response::HTTP_PERMANENTLY_REDIRECT
                );
            }
            $post->setAuthor($this->getUser());
            if (null !== $request->get('publish')) {
                $post->setStatus(Page::PUBLISHED);
                if (isset($revision)) {
                    if ($contentRevisionCompare->doesPageMatchRevision($post, $revision)) {
                        $revision->setContent('');
                    }
                    $revision->setAction(RevisionRepository::PUBLISHED);
                }
            }
            if (!empty($request->get('post')['url'])) {
                $newUrl = $request->get('post')['url'];
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
            if (!empty($request->get('post')['categories'])) {
                $newCategories = $request->get('post')['categories'];
                if (!empty($newCategories)) {
                    foreach ($newCategories as $newCategory) {
                        $category = null;
                        if (Uuid::isValid($newCategory)) {
                            $category = $this->entityManager->getRepository(Category::class)->findOneById($newCategory);
                        }
                        if (!empty($category)) {
                            $post->getCategories()->add($category);
                        }
                    }
                }
            }
            if (!empty($request->get('post')['tags'])) {
                $newTags = $request->get('post')['tags'];
                if (!empty($newTags)) {
                    foreach ($newTags as $newTag) {
                        $tag = null;
                        if (Uuid::isValid($newTag)) {
                            $tag = $this->entityManager->getRepository(Tag::class)->findOneById($newTag);
                        }
                        if (empty($tag)) {
                            $tag = new Tag($newTag);
                        }
                        $post->getTags()->add($tag);
                    }
                }
            }
            if (!empty($request->get('post')['featureImage'])) {
                $post->setFeatureImage(
                    $this->entityManager->getRepository(Image::class)->findOneById(
                        $request->get('post')['featureImage']
                    )
                );
            }

            if ($form->get('publish')->isClicked()) {
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
        $this->data['includeDatePicker'] = true;
        $this->data['post'] = $post;
        $this->data['revisions'] = $this->entityManager->getRepository(Revision::class)
            ->getAll(0, 25, [
                'q.page_id = :pageId', [
                    'pageId' => $post->getId(),
                ]
            ], [
                [ 'q.versionNumber', 'DESC']
            ]);
        if ($post->getId() !== null) {
            $this->data['textStats'] = ReadingTime::getWordCountAndReadingTime($this->data['post']->getContent());
        }
        return $this->render('inadmin/post__edit.html.twig', $this->data);
    }

    /**
     * @param Request $request
     * @return Response
     */
    #[Route("/{page}", methods: [ "GET" ])]
    public function getPage(Request $request): Response
    {
        return $this->getPost($request, 0, 0, 0, '');
    }

    /**
     * Returns `page` or `post` depending on the current URL
     * @param Request $request
     * @return string The result of testing the current URL
     */
    private function getContentType(Request $request): string
    {
        return 1 === preg_match(
            '/\/incc\/([0-9]{4}\/[0-9]{2}\/[0-9]{2}\/.*|post)/',
            $request->server()->get('REQUEST_URI')
        ) ? Page::TYPE_POST : Page::TYPE_PAGE;
    }

    /**
     * @param Request $request
     * @param string $tagName
     * @return Response
     */
    #[Route("/tag/{tagName}", methods: [ "GET" ])]
    public function getPostsByTag(Request $request, string $tagName): Response
    {
        $tag = $this->entityManager->getRepository(Tag::class)->findOneByTitle($tagName);

        if (!$tag instanceof Tag) {
            throw new NotFoundHttpException(
                sprintf(
                    '%s does not exist',
                    $tagName
                )
            );
        }
        $this->data['filterName'] = 'tag';
        $this->data['filterValue'] = $tagName;
        $this->data['content'] = $this->entityManager->getRepository(Page::class)->getPagesWithTag($tag);

        return $this->render('web/homepage.html.twig', $this->data);
    }

    /**
     * @param Request $request
     * @param string $categoryName
     * @return Response
     */
    #[Route("/category/{categoryName}", methods: [ "GET" ])]
    public function getPostsByCategory(Request $request, string $categoryName): Response
    {
        $category = $this->entityManager->getRepository(Category::class)->findOneByTitle($categoryName);
        if (!$category instanceof Category) {
            throw new NotFoundHttpException(
                sprintf(
                    '%s does not exist',
                    $categoryName
                )
            );
        }
        $this->data['filterName'] = 'category';
        $this->data['filterValue'] = $categoryName;
        $this->data['content'] = $this->entityManager->getRepository(Page::class)->getPagesWithCategory($category);

        return $this->render('web/homepage.html.twig', $this->data);
    }
}
