<?php

namespace App\Controller;

use App\Controller\AbstractInachisController;
use App\Entity\Page;
use App\Entity\Url;
use App\Form\PostType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ZZPageController extends AbstractInachisController
{
//     * @Route("/{slug}", methods={"GET"})
//    public function getPage($slug)
//    {
//        return new Response('<html><body>Page controller</body></html>');
//        //throw $this->createNotFoundException('This page does not exist');
//    }

    /**
     * @Route(
     *     "/{year}/{month}/{day}/{title}",
     *     methods={"GET"},
     *     requirements={
     *          "year": "\d+",
     *          "month": "\d+",
     *          "day": "\d+"
     *     }
     * )
     */
    public function getPost($year, $month, $day, $title)
    {
//        $urlManager = new UrlManager(Application::getInstance()->getService('em'));
//        $url = $urlManager->getByUrl($request->server()->get('REQUEST_URI'));
//        if (empty($url)) {
//            return $response->code(404);
//        }
//        if ($url->getContent()->isScheduledPage() || $url->getContent()->isDraft()) {
//            return $response->redirect('/');
//        }
//        if (!$url->getDefault()) {
//            $url = $urlManager->getDefaultUrl($url->getContent());
//            if (!empty($url)) {
//                return $response->redirect($url->getLink(), 301);
//            }
//        }
//        $userManager = new UserManager(Application::getInstance()->getService('em'));
//        $userManager->getByUsername($url->getContent()->getAuthor()->getUsername());
        $data = [];/*array(
            'post' => $url->getContent(),
            'url' => $url->getLink()
        );*/
        return $this->render('post.html.twig', $data);

    }

    /**
     * @Route(
     *     "/incc/{type}/new",
     *     methods={"GET", "POST"},
     *     defaults={"type": "post"},
     *     requirements={
     *          "type": "post|page"
     *     }
     * )
     * @Route(
     *     "/incc/{type}/{title}",
     *     methods={"GET", "POST"},
     *     defaults={"type": "post"},
     *     requirements={
     *          "type": "post|page",
     *          "title": "!new"
     *     }
     * )
     * @Route(
     *     "/incc/{year}/{month}/{day}/{title}",
     *     methods={"GET", "POST"},
     *     requirements={
     *          "year": "\d+",
     *          "month": "\d+",
     *          "day": "\d+"
     *     }
     * )
     * @param Request $request
     * @param string $type
     * @param string $title
     * @return mixed
     * @throws \Exception
     */
    public function getPostAdmin(Request $request, $type, $title = null)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $entityManager = $this->getDoctrine()->getManager();
        $url = $entityManager->getRepository(Url::class)->findByLink($title);
        // If content with this URL doesn't exist, then redirect
        if (empty($url) && null !== $title) {
            return $response->redirect(sprintf(
                '/inadmin/%s/new',
                $type
            ))->send();
        }
        $post = null !== $title ? $entityManager->getById($url->getContent()->getId()) : $post = new Page();
        if ($post->getId() === null) {
            $post->setType($type);
        }
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {//} && $form->isValid()) {
            $post->setAuthor($this->get('security.token_storage')->getToken()->getUser());
            if (null !== $request->get('publish')) {
                $post->setStatus(Page::PUBLISHED);
            }
//            $post->setVisibility(Page::VIS_PRIVATE);
//            if ($request->paramsPost()->get('visibility') === 'on') {
//                $post->setVisibility(Page::VIS_PUBLIC);
//            }
            dump($form);
            dump($post);
            dump($request);

//            $entityManager->persist($post);
//            $entityManager->flush();

            exit;

//            return $this->redirect(
//                '/incc/' .
//                ( $post->getType() == Page::TYPE_PAGE ? 'page/' : '' ) .
//                $post->getUrls()[0]->getLink()
//            );
        }


//            if (null !== $request->paramsPost()->get('delete') && !empty($post->getId())) {
//                foreach ($post->getUrls() as $postUrl) {
//                    $urlManager->remove($postUrl);
//                }
//                $pageManager->remove($post);
//                return $response->redirect('/inadmin/');
//            }


//            $categoryManager = new CategoryManager(Application::getInstance()->getService('em'));
//            $tagManager = new TagManager(Application::getInstance()->getService('em'));
//            $categories = $request->paramsPost()->get('categories');
//            $assignedCategories = $post->getCategories()->getValues();
//            if (!empty($categories)) {
//                foreach ($categories as $categoryId) {
//                    $category = $categoryManager->getById($categoryId);
//                    if (in_array($category, $assignedCategories)) {
//                        continue;
//                    }
//                    $post->addCategory($category);
//                }
//            }
//            $tags = $request->paramsPost()->get('tags');
//            $assignedTags = $post->getTags()->getValues();
//            if (!empty($tags)) {
//                foreach ($tags as $tagTitle) {
//                    $tag = $tagManager->getByTitle($tagTitle);
//                    if (in_array($tag, $assignedTags)) {
//                        continue;
//                    }
//                    if (null === $tag) {
//                        $tag = $tagManager->create(array('title' => $tagTitle));
//                    }
//                    $post->addTag($tag);
//                }
//            }
//            $newUrl = $request->paramsPost()->get('url');
//            $urlFound = false;
//            $urls = $post->getUrls();
//            if (!empty($urls)) {
//                foreach ($urls as $url) {
//                    if ($url->getLink() !== $newUrl) {
//                        $url->setDefault(false);
//                    } else {
//                        $urlFound = true;
//                    }
//                }
//            }
//            if (!$urlFound) {
//                $post->addUrl($urlManager->create(array(
//                    'content' => $post,
//                    'default' => true,
//                    'link' => $newUrl
//                )));
//            }


        $this->data['form'] = $form->createView();
        $this->data['page']['tab'] = $post->getType();
        $this->data['page']['title'] = $post->getId() !== null ?
            'Editing "' . $post->getTitle() . '"' :
            'New ' . $post->getType();
        $this->data['includeEditor'] = true;
        $this->data['post'] = $post;
        return $this->render('inadmin/post__edit.html.twig', $this->data);
    }

    /**
     * @Route(
     *     "/incc/{type}/list",
     *     methods={"GET", "POST"},
     *     requirements={
     *          "type": "post|page"
     *     }
     * )
     * @param string $type
     * @return null
     */
    public function getPostListAdmin($type = 'post')
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $entityManager = $this->getDoctrine()->getManager();
        // @todo sort offset: (int) $request->paramsGet()->get('offset', 0);
        $offset = 0;
        $this->data['posts'] = $entityManager->getRepository(Page::class)->getAll(
            $offset,
            10,
            [
                'q.type = :type',
                [
                    'type' => $type,
                ]
            ],
            [
                [ 'q.postDate', 'DESC' ],
                [ 'q.modDate', 'DESC' ]
            ],
            'q.postDate ASC, q.modDate'
        );

        $this->data['page']['tab'] = $type;
        $this->data['page']['title'] = ucfirst($type) . 's';
        return $this->render('inadmin/post__list.html.twig', $this->data);
    }

    /**
     * @Route("/incc/search/results", methods={"GET", "POST"})
     */
    public function getSearchResults()
    {
//        self::redirectIfNotAuthenticated($request, $response);
        return new Response('<html><body>Show search results</body></html>');
    }

    /**
     * Returns `page` or `post` depending on the current URL
     * @return string The result of testing the current URL
     */
    private function getContentType()
    {
        return 1 === preg_match(
            '/\/inadmin\/([0-9]{4}\/[0-9]{2}\/[0-9]{2}\/.*|post)/',
            $request->server()->get('REQUEST_URI')
        ) ? Page::TYPE_POST : Page::TYPE_PAGE;
    }
}
