<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Dialog;

use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\Category;
use Inachis\Entity\Page;
use Inachis\Entity\Series;
use Inachis\Entity\Tag;
use Inachis\Entity\Url;
use Inachis\Model\BulkCreateData;
use Inachis\Repository\CategoryRepository;
use Inachis\Repository\SeriesRepository;
use Inachis\Repository\TagRepository;
use Inachis\Service\Page\PageBulkCreateService;
use Inachis\Util\UrlNormaliser;
use DateInterval;
use DateMalformedPeriodStringException;
use DateMalformedStringException;
use DatePeriod;
use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use ReCaptcha\RequestMethod\Post;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use DateTime;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
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
        return $this->render('inadmin/dialog/bulk-create.html.twig', $this->data);
    }

    /**
     * @param Request $request
     * @param PageBulkCreateService $bulkCreatePost
     * @return Response
     * @throws Exception
     */
    #[Route("/incc/ax/bulkCreate/save", methods: [ "POST" ])]
    public function saveContent(
        Request               $request,
        PageBulkCreateService $bulkCreatePost,
    ): Response {
        try {
            $data = BulkCreateData::fromRequest($request);
            $count = $bulkCreatePost->create($data, $this->getUser());

            if ($count === 0) {
                return new Response('No change', Response::HTTP_NO_CONTENT);
            }
            return new Response('Saved', Response::HTTP_CREATED);
        } catch (InvalidArgumentException $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
