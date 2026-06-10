<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
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
    #[Route("/incc/api/calculate-password-strength", name:"incc_api_calculate-password-strength", methods: [ "POST" ])]
    public function calculatePasswordStrength(Request $request): JsonResponse
    {
        $password = $request->request->get('password') ?: '';
        if (!is_string($password)) {
            $password = '';
        }
        return new JsonResponse(PasswordStrengthValidator::estimateStrength($password));
    }
}
