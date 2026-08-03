<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\Page\Tools;

use Inachis\Controller\AbstractInachisController;
use Inachis\Service\System\Domain\DomainEmailAnalyser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller for displaying email settings
 */
class EmailSettingController extends AbstractInachisController
{
	/**
	 * Display summary of DNS settings for email

	 * @param Request $request
	 * @param DomainEmailAnalyser $domainEmailAnalyser
	 * @return Response
	 */
    #[Route("/incp/tools/email", name: "incp_tools_email")]
    public function index(
		Request $request,
		DomainEmailAnalyser $domainEmailAnalyser,
	): Response {
        $domain = isset($_ENV['APP_DOMAIN']) && is_string($_ENV['APP_DOMAIN']) ? $_ENV['APP_DOMAIN'] : 'example.com';
		$serverIp = !empty($_ENV['SERVER_IP']) && is_string($_ENV['SERVER_IP']) ? $_ENV['SERVER_IP'] : $this->getExternalIp();
		$selector = isset($_ENV['DKIM_SELECTOR']) && is_string($_ENV['DKIM_SELECTOR']) ? $_ENV['DKIM_SELECTOR'] : 'default';
		$report = $domainEmailAnalyser->analyse($domain, $serverIp, $selector);

		$this->viewModel->page->title = 'Email settings';
		$this->viewModel->page->tab = 'tools';
		return $this->render('inadmin/page/tools/email.html.twig', [
			'viewModel' => $this->viewModel,
			'report' => $report,
		]);
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

				if (is_string($ip) && filter_var(trim($ip), FILTER_VALIDATE_IP)) {
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