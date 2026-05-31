<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Setting;

use Inachis\Controller\AbstractController;
use Inachis\Service\RobotsTxtGenerator;
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
