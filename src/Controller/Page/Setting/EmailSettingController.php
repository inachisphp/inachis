<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Setting;

use Inachis\Controller\AbstractInachisController;
use Inachis\Service\System\Domain\DNSFetcherService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class EmailSettingController extends AbstractInachisController
{
    #[Route("/incc/settings/email", name: "incc_settings_email")]
    public function index(
		Request $request,
		DNSFetcherService $dnsFetcherService,
	): Response {
        $domain = $_ENV['APP_DOMAIN'] ?? 'example.com';

		$this->data['page']['title'] = 'Email settings';
		$this->data['domain'] = $domain;
		$this->data['mx_records'] = $dnsFetcherService->fetchMXRecords($domain);
        $this->data['dmarc'] = $dnsFetcherService->fetchDMARCRecords($domain);
		$this->data['spf'] = $dnsFetcherService->fetchSPFRecords($domain);
		$this->data['dmarc_errors'] = $dnsFetcherService->validateDmarc();
		$this->data['spf_errors'] = $dnsFetcherService->validateSPFRecord();

		return $this->render('inadmin/page/setting/email.html.twig', $this->data);
	}
}