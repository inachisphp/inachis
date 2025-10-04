<?php

namespace App\Controller;

use App\Entity\Tag;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TagsController extends AbstractInachisController
{
    /**
     * @param Request         $request
     * @param LoggerInterface $logger
     * @return Response
     */
    #[Route("incc/ax/tagList/get", methods: [ "POST" ])]
    public function getTagManagerListContent(Request $request, LoggerInterface $logger): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $tags = $this->entityManager->getRepository(Tag::class)->findByTitleLike($request->request->get('q'));
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
