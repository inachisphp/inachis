<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\Dialog;

use Exception;
use InvalidArgumentException;
use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\User\User;
use Inachis\Model\BulkCreateData;
use Inachis\Service\Content\Page\PageBulkCreateService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Bulk Create Controller
 */
class BulkCreateController extends AbstractInachisController
{
    /**
     * Get the bulk create dialog
     *
     * @return Response
     */
    #[Route("/incp/ax/bulkCreate/get", methods: [ "POST" ])]
    public function contentList(Request $request): Response
    {
        return $this->render('inadmin/dialog/bulk-create.html.twig', [
            'pageTitle' => $request->request->getString('title', ''),
        ]);
    }

    /**
     * Save the bulk create data
     *
     * @param Request $request
     * @param PageBulkCreateService $bulkCreatePost
     * @return Response
     * @throws Exception
     */
    #[Route("/incp/ax/bulkCreate/save", methods: [ "POST" ])]
    public function saveContent(Request $request, PageBulkCreateService $bulkCreatePost): Response {
        /** @var User|null $user */
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
