<?php

$projectDir = dirname(__DIR__);
$lockFile   = $projectDir.'/var/maintenance.lock';
$configFile = $projectDir.'/var/maintenance.json';

if (file_exists($lockFile)) {

    $allowedIps = ['127.0.0.1', '::1'];
    $retryAfter = 3600;

    if (file_exists($configFile)) {
        $config = json_decode(file_get_contents($configFile), true);

        if (is_array($config)) {
            $allowedIps = array_merge($allowedIps, $config['allowed_ips'] ?? []);
            $retryAfter = (int)($config['retry_after'] ?? $retryAfter);
        }
    }

    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $clientIp   = $_SERVER['REMOTE_ADDR'] ?? '';

    if (!str_starts_with($requestUri, '/health') && !in_array($clientIp, $allowedIps, true)) {
        http_response_code(503);
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Retry-After: ' . gmdate('D, d M Y H:i:s', time() + $retryAfter) . ' UTC');

        readfile(__DIR__.'/maintenance.html');
        exit;
    }
}

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Inachis\Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
