<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Tools;

use Inachis\Controller\AbstractInachisController;
use Inachis\Service\System\Domain\DomainEmailAnalyser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for displaying email settings
 */
#[IsGranted('ROLE_ADMIN')]
class EmailSettingController extends AbstractInachisController
{
	/**
	 * Display summary of DNS settings for email
	 
	 * @param Request $request
	 * @param DomainEmailAnalyser $domainEmailAnalyser
	 * @return Response
	 */
    #[Route("/incc/tools/email", name: "incc_tools_email")]
    public function index(
		Request $request,
		DomainEmailAnalyser $domainEmailAnalyser,
	): Response {
        $domain = $_ENV['APP_DOMAIN'] ?? 'example.com';
		$serverIp = !empty($_ENV['SERVER_IP']) ? $_ENV['SERVER_IP'] : $this->getExternalIp();
		$selector = $_ENV['DKIM_SELECTOR'] ?? 'default';
		$report = $domainEmailAnalyser->analyse($domain, $serverIp, $selector);

		$this->data['page']['title'] = 'Email settings';
		$this->data['page']['tab'] = 'tools';
		$this->data['report'] = $report;

		return $this->render('inadmin/page/tools/email.html.twig', $this->data);
	}

	/**
	 * Returns the external IP address of the server
	 *
	 * @return string
	 */
	private function getExternalIp(): string {
		$ipServices = [
			'https://api.ipify.org',
			'https://checkip.amazonaws.com',
			'https://ifconfig.me/ip'
		];

		if (function_exists('curl_init')) {
			foreach ($ipServices as $service) {
				$ch = curl_init($service);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
				curl_setopt($ch, CURLOPT_TIMEOUT, 5);
				curl_setopt($ch, CURLOPT_USERAGENT, 'PHP External IP Checker');
				$ip = curl_exec($ch);
				$error = curl_error($ch);
				curl_close($ch);

				if ($ip && filter_var(trim($ip), FILTER_VALIDATE_IP)) {
					return trim($ip);
				}
			}
		}

		$ip = gethostbyname('myip.opendns.com');
		if ($ip && filter_var(trim($ip), FILTER_VALIDATE_IP)) {
			return trim($ip);
		}

		return '';
	}
}