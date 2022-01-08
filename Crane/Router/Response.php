<?php

namespace Crane\Router;

use Crane\FileSystem\Storage;
use Crane\Html;
use Crane\Template\Template;

class Response
{

    const DEFAULT_HTML_HEADERS = ['Content-Type' => 'text/html', 'X-FRAME-OPTIONS' => 'DENY'];
    const DEFAULT_JSON_HEADERS = ['Content-Type' => 'application/json', 'X-FRAME-OPTIONS' => 'DENY'];
    const DEFAULT_TEXT_HEADERS = ['Content-Type' => 'text/html', 'X-FRAME-OPTIONS' => 'DENY'];

    public $body;
    public $headers;
    public $cookies;

    public function __construct($body = null, $headers = self::DEFAULT_HTML_HEADERS)
    {
        $this->body = $body;
        $this->headers = $headers;
    }

    public function cookie($name, $value = "", $expires_or_options = 0, $path = "", $domain = "", $secure = false, $httponly = false)
    {
        setcookie($name, $value = "", $expires_or_options = 0, $path = "", $domain = "", $secure = false, $httponly = false);
        return $this;
    }


    /**
     * Set a response cookie.
     * 
     * @param mixed $key
     * @param mixed $value
     * 
     * @return Response
     */
    public function session($key, $value): Response
    {
        $_SESSION[$key] = Html::clean($value);
        return $this;
    }

    /**
     * Redirect.
     * 
     * @param mixed $location
     * 
     * @return void
     */
    public function redirect($location): void
    {
        header('Location: ' . $location);
        die;
    }

    /**
     * Return to previous page.
     * 
     * @param string $withReferer
     * 
     * @return void
     */
    public function back($withReferer = null): void
    {
        $withReferer = $withReferer ?? $_SERVER['HTTP_REFERER'];
        header('Location: ' . $withReferer);
        die;
    }

    /**
     * Send a json response.
     * 
     * @param mixed $value
     * @param  $headers
     * 
     * @return Response
     */
    public function json($value, $headers = self::DEFAULT_JSON_HEADERS): Response
    {
        return new Response($value, $headers);
    }

    /**
     * Send a generic response.
     * 
     * @param mixed $value
     * @param  $headers
     * 
     * @return Response
     */
    public function send($value, $headers = self::DEFAULT_HTML_HEADERS): Response
    {
        return new Response($value, $headers);
    }

    /**
     * Render a template.
     * 
     * @param mixed $file
     * @param array $context
     * @param  $headers
     * 
     * @return Response
     */
    public function render($file, $context = [], $headers = self::DEFAULT_HTML_HEADERS): Response
    {
        $fp = Storage::root() . 'views/' . $file;
        $value = (new Template($fp))->template($context);
        return new Response($value, ['Content-Type' => 'text/html', 'X-FRAME-OPTIONS' => 'DENY']);
    }

    /**
     * Send a download.
     * 
     * @param mixed $file
     * @param mixed $headers
     * @param  'X-FRAME-OPTIONS'
     * 
     * @return Response
     */
    public function download($file, $headers = ['Content-Type' => 'application/octet-stream', 'X-FRAME-OPTIONS' => 'DENY']): Response
    {
        $basename = basename($file);
        $headers = array_merge($headers, [
            'Content-Transfer-Encoding' => 'Binary',
            'Content-disposition' => 'attachment; filename="' . $basename . '"'
        ]);
        return new Response(file_get_contents($file), $headers);
    }
}
