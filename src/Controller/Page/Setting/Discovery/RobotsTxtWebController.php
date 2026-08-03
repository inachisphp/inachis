<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\Page\Setting\Discovery;

use Inachis\Controller\AbstractController;
use Inachis\Service\Discovery\Generator\RobotsTxtGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RobotsTxtWebController extends AbstractController
{
	/**
	 * Serve the robots.txt content
	 *
	 * @param RobotsTxtGenerator $generator
	 * @return Response
	 */
    #[Route('/robots.txt', name: 'web_robots_txt')]
    public function index(RobotsTxtGenerator $generator): Response
    {
		return new Response(
			$generator->generate(),
			Response::HTTP_OK,
			[
				'Content-Type' => 'text/plain; charset=UTF-8',
			]
		);
    }
}
