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
        $status = $response->getStatusCode();

        $path = strtok($request->getRequestUri(), '?');
        $path = rtrim($path, '/');

        if ($request->getMethod() !== 'GET') return;
        if (str_starts_with($path, '/incc')) return;
        if (str_starts_with($path, '/_profiler')) return;
        if (str_starts_with($path, '/_wdt')) return;
        if (str_starts_with($path, '/assets')) return;
        if (str_starts_with($path, '/cgi-bin/')) return;
        if (str_starts_with($path, '/.well-known/')) return;
        if (str_starts_with($path, '/.git/')) return;
        if (str_starts_with($path, '/wp-content/')) return;
        if (str_starts_with($path, '/wp-admin/')) return;
        if (str_ends_with($path, '.xml')) return;

        $userAgent = $request->headers->get('User-Agent', '');
        $ip = $request->getClientIp();
        $visitorId = hash('sha256', $ip . '|' . $userAgent);
        if (preg_match('/bot|crawl|spider|slurp|wget|curl/i', $userAgent)) {
            $botFile = sprintf('%s/bot-%s.log', $dir, $date);
            $botLine = json_encode([
                'path' => $path,
                'ua'   => mb_substr($userAgent, 0, 255),
                'date' => $date,
                'ts'   => time(),
            ], JSON_UNESCAPED_SLASHES);
            file_put_contents($botFile, $botLine . PHP_EOL, FILE_APPEND | LOCK_EX);
            return;
        }
        
        $dir = __DIR__ . '/../../var/analytics';
        $this->createAnalyticsDir($dir);
        $date = date('Y-m-d');

        if ($status >= 400) {
            $file = sprintf('%s/error-%s.log', $dir, $date);

            $line = json_encode([
                'path' => $path,
                'date' => $date,
                'code' => $status,
                // 'ref' => $request->headers->get('referer') ?? '',
                'ts'   => time(),
            ], JSON_UNESCAPED_SLASHES);

            file_put_contents($file, $line . PHP_EOL, FILE_APPEND | LOCK_EX);

            return;
        }

        $ref = $request->headers->get('referer');
        $refDomain = null;
        if ($ref) {
            $host = parse_url($ref, PHP_URL_HOST);
            if ($host) {
                $refDomain = preg_replace('/^www\./', '', strtolower($host));
            }
        }
        if ($refDomain && str_contains($refDomain, $request->getHost())) {
            $refDomain = null;
        }
        $refDomain = $refDomain ?? 'Direct';

        $file = sprintf('%s/analytics-%s.log', $dir, $date);
        $line = json_encode([
            'path' => $path,
            'date' => $date,
            'visitor' => $visitorId,
            'ref' => $refDomain,
            'ip' => $ip,
            'ts' => time(),
        ], JSON_UNESCAPED_SLASHES);

        file_put_contents($file, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /**
     * Creates the analytics directory
     *
     * @param string $dir The directory to create
     */
    protected function createAnalyticsDir(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }
}
