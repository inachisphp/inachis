<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Dialog;

use DateInterval;
use DateMalformedPeriodStringException;
use DateMalformedStringException;
use DatePeriod;
use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use Inachis\Controller\AbstractInachisController;
use Inachis\Model\BulkCreateData;
use Inachis\Service\Page\PageBulkCreateService;
use Inachis\Util\UrlNormaliser;
use Ramsey\Uuid\Uuid;
use ReCaptcha\RequestMethod\Post;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Bulk Create Controller
 */
#[IsGranted('ROLE_ADMIN')]
class BulkCreateController extends AbstractInachisController
{
    /**
     * @var array<string, string>
     */
    protected array $errors = [];

    /**
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * Get the bulk create dialog
     *
     * @param Request $request
     * @return Response
     */
    #[Route("/incc/ax/bulkCreate/get", methods: [ "POST" ])]
    public function contentList(Request $request): Response
    {
        return $this->render('inadmin/dialog/bulk-create.html.twig', $this->data);
    }

    /**
     * Save the bulk create data
     *
     * @param Request $request
     * @param PageBulkCreateService $bulkCreatePost
     * @return Response
     * @throws Exception
     */
    #[Route("/incc/ax/bulkCreate/save", methods: [ "POST" ])]
    public function saveContent(Request $request, PageBulkCreateService $bulkCreatePost): Response {
        /** @var \Inachis\Entity\User|null $user */
        $user = $this->getUser();
        if ($user === null) {
            return new Response('Unauthorized', Response::HTTP_UNAUTHORIZED);
        }
        try {
            $data = BulkCreateData::fromRequest($request);
            $count = $bulkCreatePost->create($data, $user);

            if ($count === 0) {
                return new Response('No change', Response::HTTP_NO_CONTENT);
            }
            return new Response('Saved', Response::HTTP_CREATED);
        } catch (InvalidArgumentException $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
