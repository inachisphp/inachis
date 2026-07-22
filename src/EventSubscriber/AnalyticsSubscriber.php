<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Writes all page visits to a file which is then processed by the inachis:analytics:aggregate command
 */
class AnalyticsSubscriber implements EventSubscriberInterface
{
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
        $path = $path ? rtrim($path, '/') : '';

        // if ($request->getMethod() !== 'GET') return;
        if (str_starts_with($path, '/incp')) return;
        if (str_starts_with($path, '/_profiler')) return;
        if (str_starts_with($path, '/_wdt')) return;
        if (str_starts_with($path, '/assets')) return;
        if (str_starts_with($path, '/api/csp/report')) return;
        // if (str_starts_with($path, '/cgi-bin/')) return;
        if (str_starts_with($path, '/.well-known/')) return;
        // if (str_starts_with($path, '/.git/')) return;
        // if (str_starts_with($path, '/wp-content/')) return;
        // if (str_starts_with($path, '/wp-admin/')) return;
        if (str_starts_with($path, '/llms.txt')) return;
        if (str_starts_with($path, '/robots.txt')) return;
        if (str_ends_with($path, '.xml')) return;

        $dir = __DIR__ . '/../../var/analytics';
        $this->createAnalyticsDir($dir);
        $date = date('Y-m-d');

        $userAgent = $request->headers->get('User-Agent', '');
        $ip = $request->getClientIp();
        $visitorId = hash('sha256', $ip . '|' . $userAgent);
        $securityEvent = $this->detectSecurityEvent($path, $userAgent);

        if ($securityEvent !== null) {
            $this->appendLog(sprintf('%s/security-%s.log', $dir, $date), [
                'date' => $date,
                'ip' => $ip,
                'path' => $path,
                'type' => $securityEvent['type'],
                'severity' => $securityEvent['severity'],
                'ua' => mb_substr($userAgent, 0, 255),
                'ts' => time(),
            ]);
            return;
        }
        if (preg_match('/bot|crawl|spider|slurp|wget|curl/i', $userAgent)) {
            $this->appendLog(sprintf('%s/bot-%s.log', $dir, $date), [
                'path' => $path,
                'ua'   => mb_substr($userAgent, 0, 255),
                'date' => $date,
                'ts'   => time(),
            ]);
            return;
        }

        if ($status >= 400) {
            $this->appendLog(sprintf('%s/error-%s.log', $dir, $date), [
                'path' => $path,
                'date' => $date,
                'code' => $status,
                // 'ref' => $request->headers->get('referer') ?? '',
                'ts'   => time(),
            ]);

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
        $this->appendLog($file, [
            'path' => $path,
            'date' => $date,
            'visitor' => $visitorId,
            'ref' => $refDomain,
            'ip' => $ip,
            'ts' => time(),
        ]);
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

    /**
     * Append data to specified log file
     *
     * @param string $filename
     * @param array $data
     */
    private function appendLog(string $filename, array $data): void
    {
        file_put_contents(
            $filename,
            json_encode($data, JSON_UNESCAPED_SLASHES) . PHP_EOL,
            FILE_APPEND | LOCK_EX,
        );
    }

    /**
     * Detect suspicious request patterns.
     *
     * @param string $path
     * @param string $userAgent
     * @return array{type:string,severity:int}|null
     */
    private function detectSecurityEvent(
        string $path,
        string $userAgent
    ): ?array {

        $patterns = [
            '#(?:^|/)wp-admin(?:/|$)#i' => [
                'type' => 'wp_probe',
                'severity' => 3,
            ],

            '#(?:^|/)wp-login(?:\.php)?(?:/|$)#i' => [
                'type' => 'wp_probe',
                'severity' => 3,
            ],

            '#\.env(?:$|/)#i' => [
                'type' => 'env_probe',
                'severity' => 10,
            ],

            '#(?:^|/)\.git(?:/|$)#i' => [
                'type' => 'git_probe',
                'severity' => 10,
            ],

            '#phpinfo(?:\.php)?#i' => [
                'type' => 'php_probe',
                'severity' => 7,
            ],

            '#vendor/phpunit#i' => [
                'type' => 'phpunit_probe',
                'severity' => 10,
            ],

            '#server-status#i' => [
                'type' => 'apache_probe',
                'severity' => 7,
            ],

            '#(?:\.\./|%2e%2e)#i' => [
                'type' => 'path_traversal',
                'severity' => 10,
            ],
        ];

        foreach ($patterns as $pattern => $event) {
            if (preg_match($pattern, $path)) {
                return $event;
            }
        }

        if (preg_match('/sqlmap|nikto|nmap|masscan|acunetix/i', $userAgent)) {
            return [
                'type' => 'scanner_user_agent',
                'severity' => 8,
            ];
        }

        return null;
    }
}
