<?php

namespace Crane\Router;

use Crane\Html;
use Crane\Crane;

class Request
{
    private static function isHttps(array $server): bool
    {
        return (
            (($server['HTTPS'] ?? false) && $server['HTTPS'] != false && $server['HTTPS'] !== 'off') ||
            (($server['REQUEST_SCHEME'] ?? false) && $server['REQUEST_SCHEME'] == 'https') ||
            (($server['SERVER_PORT'] ?? false) && $server['SERVER_PORT'] == 443) ||
            (($server['HTTP_X_FORWARDED_PROTO'] ?? false) && $server['HTTP_X_FORWARDED_PROTO'] === 'https' && $server['HTTP_X_FORWARDED_SSL'] == 'on')
        );
    }

    private static function urlQuery(string $url): array
    {
        $parsed = parse_url($url)['query'] ?? '';
        parse_str($parsed, $output);
        $assoc = $output ?? [];
        return Html::clean($assoc);
    }


    public static function create(array $serverGlobal, array $requestGlobal)
    {

        $method = strtoupper($serverGlobal['REQUEST_METHOD']) ?? 'GET';

        $scheme = self::isHttps($serverGlobal) ? 'https' : 'http';

        $url = $scheme . '://' . $serverGlobal['HTTP_HOST'] . $serverGlobal['REQUEST_URI'];

        $request = array_merge(
            $requestGlobal,
            self::urlQuery($_SERVER["QUERY_STRING"] ?? []),
            [
                'http' => [
                    'method' => $method,
                    'scheme' => $scheme,
                    'host' => $serverGlobal['HTTP_HOST'],
                    'url' => $url,
                    'path' => $serverGlobal['REQUEST_URI'],
                    'user_agent' => $serverGlobal['HTTP_USER_AGENT'] ?? '',
                    'ip' => $serverGlobal['REMOTE_ADDR'],
                    'referer' => $serverGlobal['HTTP_REFERER'] ?? ''
                ]
            ]
        );

        return $request;
    }


    /**
     * Get data sent via $_POST as a generic object.
     * 
     * @return array
     */
    public function posted(): Crane
    {
        return new Crane(Html::clean($_POST));
    }

    /**
     * Get data sent via $_COOKIE as a generic object.
     * @return array
     */
    public function cookies(): Crane
    {
        return new Crane(Html::clean($_COOKIE));
    }

    /**
     * Get data sent via $_SESSION as a generic object.
     * @return array
     */
    public function session(): Crane
    {
        return new Crane(Html::clean($_SESSION));
    }

    /**
     * Get data sent via $_FILES as a generic object.
     * 
     * @return array
     */
    public function files(): Crane
    {
        return new Crane($_FILES);
    }
}
