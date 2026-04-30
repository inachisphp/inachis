<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\EventSubscriber;

use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Writes all page visits to a file which is then processed by the inachis:analytics:aggregate command
 */
class AnalyticsSubscriber implements EventSubscriberInterface
{
	/**
	 * @param Connection $db
	 */
	public function __construct(private Connection $db) {}

	/**
	 * @return array<string, string>
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'kernel.response' => 'onResponse',
		];
	}

	/**
	 * Captures page views and writes them to a file
	 *
	 * @param ResponseEvent $event
	 */
    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
			return;
		}

        $request = $event->getRequest();
		$response = $event->getResponse();

		if ($response->getStatusCode() !== 200) {
			return;
		}

        if ($request->getMethod() !== 'GET') return;

        $path = strtok($request->getRequestUri(), '?');

        if (str_starts_with($path, '/incc')) return;
        if (str_starts_with($path, '/_profiler')) return;
		if (str_starts_with($path, '/_wdt')) return;
        if (str_starts_with($path, '/assets')) return;
		if (str_ends_with($path, '.xml')) return;

		$userAgent = $request->headers->get('User-Agent', '');
		$ip = $request->getClientIp();
		$visitorId = hash('sha256', $ip . '|' . $userAgent);

		if (preg_match('/bot|crawl|spider|slurp|wget|curl/i', $userAgent)) {
			return;
		}

        $date = date('Y-m-d');

        $dir = __DIR__ . '/../../var/analytics';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $file = sprintf('%s/analytics-%s.log', $dir, $date);

		$line = json_encode([
			'path' => $path,
			'date' => $date,
			'visitor' => $visitorId,
			'ts' => time(),
		], JSON_UNESCAPED_SLASHES);

        file_put_contents($file, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
