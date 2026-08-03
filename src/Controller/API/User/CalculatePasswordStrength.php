<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\API\User;

use Inachis\Controller\AbstractInachisController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\PasswordStrengthValidator;

/**
 * Controller used for changing password for an administrator {@link User}
 */
class CalculatePasswordStrength extends AbstractInachisController
{
    /**
     * Returns a JSON object containing the result of calculating the password strength entropy
     * @param Request $request
     * @return JsonResponse
     */
    #[Route("/incp/api/calculate-password-strength", name:"incp_api_calculate-password-strength", methods: [ "POST" ])]
    public function calculatePasswordStrength(Request $request): JsonResponse
    {
        $password = $request->request->getString('password', '');
        return new JsonResponse(PasswordStrengthValidator::estimateStrength($password));
    }
}
