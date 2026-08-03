<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\Page\Setting\Discovery;

use Inachis\Controller\AbstractController;
use Inachis\Service\Discovery\Generator\SecurityTxtGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SecurityTxtWebController extends AbstractController
{
    #[Route(
        '/.well-known/security.txt',
        name: 'web_security_txt'
    )]
    public function index(
        SecurityTxtGenerator $generator
    ): Response {
        return new Response(
            $generator->generate(),
            Response::HTTP_OK,
            [
                'Content-Type' =>
                    'text/plain; charset=UTF-8',
            ]
        );
    }
}