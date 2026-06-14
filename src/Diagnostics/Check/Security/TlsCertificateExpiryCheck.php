<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Diagnostics\Check\Security;

use Inachis\Diagnostics\CheckInterface;
use Inachis\Diagnostics\CheckResult;
use OpenSSLCertificate;
use Symfony\Component\HttpFoundation\RequestStack;

final class TlsCertificateExpiryCheck implements CheckInterface
{
    /**
     * Constructor the check
     *
     * @param RequestStack $requestStack
     */
    public function __construct(private readonly RequestStack $requestStack) {}

    /**
     * Returns the id of the check
     *
     * @return string
     */
    public function getId(): string { return 'tls_certificate_expiry'; }

    /**
     * Returns a friendly name for the check
     *
     * @return string
     */
    public function getLabel(): string { return 'TLS Certificate Expiry'; }

    /**
     * Returns the section name this check appears under
     *
     * @return string
     */
    public function getSection(): string { return 'Security'; }

    /**
     * Runs the check
     *
     * @return CheckResult
     */
    public function run(): CheckResult
    {
        $request = $this->requestStack->getMainRequest();

        if (!$request) {
            return new CheckResult(
                $this->getId(),
                $this->getLabel(),
                'error',
                null,
                'No active request available.',
                null,
                $this->getSection(),
                'high'
            );
        }

        $host = $request->getHost();

        try {
            $context = stream_context_create([
                'ssl' => [
                    'capture_peer_cert' => true,
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                ],
            ]);

            $client = @stream_socket_client(
                sprintf('ssl://%s:443', $host),
                $errno,
                $errstr,
                10,
                STREAM_CLIENT_CONNECT,
                $context
            );

            if (!$client) {
                throw new \RuntimeException($errstr ?: 'Connection failed');
            }

            $params = stream_context_get_params($client);

            /** @var array<string, mixed> $ssl */
            $ssl = $params['options']['ssl'] ?? [];

            $certificate = $ssl['peer_certificate'] ?? null;

            if (!$certificate || !($certificate instanceof OpenSSLCertificate || is_string($certificate))) {
                throw new \RuntimeException('Certificate not available');
            }

            /** @var array{validTo_time_t: int,...}|false */
            $parsed = openssl_x509_parse($certificate);

            if (!isset($parsed['validTo_time_t'])) {
                throw new \RuntimeException('Unable to determine certificate expiry');
            }

            $expiryTimestamp = (int) $parsed['validTo_time_t'] ?: 0;

            $daysRemaining = (int) floor(
                ($expiryTimestamp - time()) / 86400
            );

            $expiryDate = (new \DateTimeImmutable())
                ->setTimestamp($expiryTimestamp)
                ->format('Y-m-d');

            if ($daysRemaining < 0) {
                return new CheckResult(
                    $this->getId(),
                    $this->getLabel(),
                    'error',
                    sprintf('Expired %d day(s) ago', abs($daysRemaining)),
                    sprintf(
                        'Certificate for %s expired on %s.',
                        $host,
                        $expiryDate
                    ),
                    'Renew the TLS certificate immediately.',
                    $this->getSection(),
                    'high'
                );
            }

            if ($daysRemaining < 14) {
                return new CheckResult(
                    $this->getId(),
                    $this->getLabel(),
                    'error',
                    $daysRemaining . ' days',
                    sprintf(
                        'Certificate for %s expires on %s.',
                        $host,
                        $expiryDate
                    ),
                    'Renew the certificate as soon as possible.',
                    $this->getSection(),
                    'high'
                );
            }

            if ($daysRemaining < 30) {
                return new CheckResult(
                    $this->getId(),
                    $this->getLabel(),
                    'warning',
                    $daysRemaining . ' days',
                    sprintf(
                        'Certificate for %s expires on %s.',
                        $host,
                        $expiryDate
                    ),
                    'Plan certificate renewal soon.',
                    $this->getSection(),
                    'medium'
                );
            }

            return new CheckResult(
                $this->getId(),
                $this->getLabel(),
                'ok',
                $daysRemaining . ' days',
                sprintf(
                    'Certificate for %s expires on %s.',
                    $host,
                    $expiryDate
                ),
                null,
                $this->getSection(),
                'low'
            );
        } catch (\Throwable $e) {
            return new CheckResult(
                $this->getId(),
                $this->getLabel(),
                'error',
                null,
                'Unable to check certificate: ' . $e->getMessage(),
                'Verify TLS connectivity and certificate configuration.',
                $this->getSection(),
                'high'
            );
        }
    }
}
