<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Controller\Dialog;

use App\Controller\AbstractInachisController;
use App\Entity\Category;
use App\Entity\Page;
use App\Entity\Series;
use App\Entity\Tag;
use App\Entity\Url;
use App\Util\UrlNormaliser;
use DateInterval;
use DateMalformedPeriodStringException;
use DateMalformedStringException;
use DatePeriod;
use DateTimeImmutable;
use Exception;
use Ramsey\Uuid\Uuid;
use ReCaptcha\RequestMethod\Post;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use DateTime;

class BulkCreateController extends AbstractInachisController
{
    protected array $errors = [];
    protected array $data = [];

    /**
     * @param Request $request
     * @return Response
     */
    #[Route("/incc/ax/bulkCreate/get", methods: [ "POST" ])]
    public function contentList(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        return $this->render('inadmin/dialog/bulk-create.html.twig', $this->data);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws DateMalformedPeriodStringException
     * @throws DateMalformedStringException
     * @throws Exception
     */
    #[Route("/incc/ax/bulkCreate/save", methods: [ "POST" ])]
    public function saveContent(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $series = $this->entityManager->getRepository(Series::class)->findOneById($request->request->get('seriesId'));
        if ($series !== null && !empty($request->request->all('form')['title'])
            && !empty($request->request->all('form')['startDate']) && !empty($request->request->all('form')['endDate'])
        ) {
            $startDate = DateTime::createFromFormat('d/m/Y', $request->request->all('form')['startDate']);
            $endDate = DateTime::createFromFormat('d/m/Y', $request->request->all('form')['endDate']);

            $period = new DatePeriod($startDate, new DateInterval('P1D'), $endDate->modify('+1 day'));
            $counter = 0;
            foreach ($period as $date) {
                ++$counter;
                $title = $request->request->all('form')['title'] . (!empty($request->request->all('form')['addDay']) ? ' Day ' . $counter : '');
                $post = new Page($title);
                $post->setPostDate($date);
                $post->addUrl(new Url($post, $post->getPostDateAsLink() . '/' . UrlNormaliser::toUri($title)));
                $post->setAuthor($this->getUser());
                if (!empty($request->request->all('form')['tags'])) {
                    foreach($request->request->all('form')['tags'] as $newTag) {
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
                if(!empty($request->request->all('form')['categories'])) {
                    foreach($request->request->all('form')['categories'] as $newCategory) {
                        $category = null;
                        if (Uuid::isValid($newCategory)) {
                            $category = $this->entityManager->getRepository(Category::class)->findOneById($newCategory);
                        }
                        if (!empty($category)) {
                            $post->getCategories()->add($category);
                        }
                    }
                }
                $post->setModDate(new DateTime('now'));
                $series->addItem($post);
                $this->entityManager->persist($post);
            }
            if ($counter > 0) {
                $this->entityManager->persist($series);
                $this->entityManager->flush();
                return new Response('Saved', Response::HTTP_CREATED);
            }
        }
        return new Response('No change', Response::HTTP_NO_CONTENT);
    }
}
