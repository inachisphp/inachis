<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Controller\API\User;

use Inachis\Controller\AbstractInachisController;
use Inachis\Repository\User\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller used for changing password for an administrator {@link User}.
 */
class UsersController extends AbstractInachisController
{
    /**
     * Returns a JSON object containing the result of calculating the password strength entropy.
     */
    #[Route('/incp/api/users/get', name: 'incp_api_user_get', methods: ['POST'])]
    public function fetchUsers(
        Request $request,
        UserRepository $userRepository,
    ): JsonResponse {
        $query = trim($request->request->getString('q', ''));

        $users = !empty($query) ? $userRepository->searchUsers($query) : [];
        $items = [];

        foreach ($users as $user) {
            $title = $user->getDisplayName();
            $items[$title] = (object) [
                'id' => $user->getId(),
                'text' => $title,
            ];
        }

        $result = array_values($items);

        return new JsonResponse(
            [
                'items' => $result,
                'totalCount' => count($result),
            ],
            Response::HTTP_OK,
        );
    }
}
