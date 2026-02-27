<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Diagnostics\Check\Environment;

use Inachis\Diagnostics\CheckInterface;
use Inachis\Diagnostics\CheckResult;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Checks if the SMTP connection is working
 */
final class SmtpConnectionCheck implements CheckInterface
{
	/**
	 * Constructor
	 *
	 * @param TransportInterface $transport The transport to use for the check
	 */
    public function __construct(
        private readonly TransportInterface $transport
    ) {}

	/**
	 * Get the ID of the check
	 *
	 * @return string
	 */
	public function getId(): string { return 'smtp_connection'; }

	/**
	 * Get the label of the check
	 *
	 * @return string
	 */
	public function getLabel(): string { return 'SMTP Connection'; }

	/**
	 * Get the section of the check
	 *
	 * @return string
	 */
	public function getSection(): string { return 'Environment'; }

	/**
	 * Check if the SMTP connection is working
	 *
	 * @return CheckResult
	 */
    public function run(): CheckResult
    {
        try {
            if (method_exists($this->transport, 'ping')) {
				$this->transport->ping();
			}

			return new CheckResult(
				$this->getId(),
				$this->getLabel(),
				'ok',
				'Connected',
				'SMTP transport reachable.',
				null,
				$this->getSection(),
				'high'
			);

		} catch (TransportExceptionInterface $e) {
			return new CheckResult(
				$this->getId(),
				$this->getLabel(),
				'error',
				'Connection failed',
				$e->getMessage(),
				'Verify MAILER_DSN configuration.',
				$this->getSection(),
				'high'
			);
		}
	}
}
