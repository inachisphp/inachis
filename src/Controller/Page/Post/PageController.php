<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Controller\Page\Post;

use App\Controller\AbstractInachisController;
use App\Entity\Category;
use App\Entity\Image;
use App\Entity\Page;
use App\Entity\Revision;
use App\Entity\Tag;
use App\Entity\Url;
use App\Form\PostType;
use App\Model\ContentQueryParameters;
use App\Repository\PageRepository;
use App\Repository\RevisionRepository;
use App\Util\ContentRevisionCompare;
use App\Util\ReadingTime;
use App\Util\UrlNormaliser;
use DateTime;
use Exception;
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
        PageRepository $pageRepository,
        ContentQueryParameters $contentQueryParameters,
        string $type = 'post',

    ): Response {
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && !empty($request->request->all('items'))) {
            foreach ($request->request->all('items') as $item) {
                if ($request->request->has('delete')) {
                    $post = $pageRepository->findOneBy(['id' => $item]);
                    if ($post !== null) {
                        $this->entityManager->getRepository(Revision::class)->deleteAndRecordByPage($post);
                        $pageRepository->remove($post);
                    }
                }
                if ($request->request->has('private') || $request->request->has('public')) {
                    $post = $pageRepository->findOneBy(['id' => $item]);
                    if ($post !== null) {
                        $post->setVisibility(
                            $request->request->has('private') ? Page::PRIVATE : Page::PUBLIC
                        );
                        $post->setModDate(new DateTime('now'));
                        $this->entityManager->persist($post);
                    }
                }
                if ($request->request->has('rebuild')) {
                    $post = $pageRepository->findOneBy(['id' => $item]);
                    if ($post !== null) {
                        if (!empty($post->getUrls())) {
                            foreach ($post->getUrls() as $url) {
                                $this->entityManager->getRepository(Url::class)->remove($url);
                            }
                        }
                        $link = $post->getPostDateAsLink() . '/' . UrlNormaliser::toUri($post->getTitle());
                        if ($post->getSubTitle() !== null) {
                            $link .= '-' . UrlNormaliser::toUri($post->getSubTitle());
                        }
                        $url = new Url($post, $link);
                        $this->entityManager->persist($url);
                        $post->setModDate(new DateTime('now'));
                        $this->entityManager->persist($post);
                        $this->entityManager->flush();
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
        methods: [ "GET", "POST" ]
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
            $revision = $this->entityManager->getRepository(Revision::class)->hydrateNewRevisionFromPage($post);
            $revision = $revision->setAction(RevisionRepository::UPDATED);
        }
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {//} && $form->isValid()) {
            if ($form->has('delete') && $form->get('delete')->isClicked()) {
                $this->entityManager->getRepository(Revision::class)->deleteAndRecordByPage($post);
                $this->entityManager->getRepository(Page::class)->remove($post);
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
        return $this->render('inadmin/page/post/edit.html.twig', $this->data);
    }
}
