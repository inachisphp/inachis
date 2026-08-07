<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\System\Domain;

/**
 * Service to retrieve the external IP address of the server.
 */
class ExternalIpAddress
{
    /**
     * Returns the external IP address of the server.
     */
    public function getExternalIp(): string
    {
        $ipServices = [
            'https://api.ipify.org',
            'https://checkip.amazonaws.com',
            'https://ifconfig.me/ip',
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
