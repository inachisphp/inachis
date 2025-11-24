<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Controller;

use App\Entity\Tag;
use App\Repository\TagRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class TagsController extends AbstractInachisController
{
    /**
     * @param Request $request
     * @param TagRepository $tagRepository
     * @return Response
     */
    #[Route("incc/ax/tagList/get", methods: [ "POST" ])]
    public function getTagManagerListContent(Request $request, TagRepository $tagRepository): Response
    {
        $tags = $tagRepository->findByTitleLike($request->request->get('q'));
        $result = [];
        // Below code is used to handle where tags exist with the same name under multiple locations
        if (!empty($tags)) {
            $result['items'] = [];
            foreach ($tags as $tag) {
                $title = $tag->getTitle();
                $result['items'][$title] = (object) [
                    'id'   => $tag->getId(),
                    'text' => $title,
                ];
            }
            $result = array_values($result['items']);
        }

        return new JsonResponse(
            [
                'items'      => $result,
                'totalCount' => count($result),
            ],
            Response::HTTP_OK
        );
    }
}
