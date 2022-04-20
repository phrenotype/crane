<?php

namespace Crane\Router;

use Crane\FileSystem\Mime;
use Crane\FileSystem\Storage;
use Crane\Template\Template;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class Response extends HttpFoundationResponse
{

    /**
     * Send a generic response.
     * 
     * @param string $html
     * 
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function respond(string $html): HttpFoundationResponse
    {
        $response = new HttpFoundationResponse($html, 200, ['content-type' => 'text/html', 'X-FRAME-OPTIONS' => 'DENY']);
        return $response;
    }

    /**
     * Render a template.
     * 
     * @param mixed $file
     * @param array $context
     * @param  $headers
     * 
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function render($file, $context = []): HttpFoundationResponse
    {
        $fp = Storage::root() . 'views/' . $file;
        $value = (new Template($fp))->template($context);
        $response = new HttpFoundationResponse($value, 200, ['content-type' => 'text/html', 'X-FRAME-OPTIONS' => 'DENY']);

        return $response;
    }

    /**
     * Send a download.
     * 
     * @param mixed $file     
     * 
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function download($file): HttpFoundationResponse
    {
        $response = new BinaryFileResponse($file);
        $response->headers->set('Content-Type', Mime::mime($file));
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($file)
        );
        return $response;
    }


    /**
     * Send a JSON response.
     * 
     * @param mixed $value     
     * 
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function json(array $data): HttpFoundationResponse
    {
        return (new JsonResponse($data));
    }

    /**
     * Redirect
     * 
     * @param string $url
     * 
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function redirect(string $url): HttpFoundationResponse
    {
        return (new RedirectResponse($url));
    }


    /**
     * Add a value to the session
     * 
     * @param string $key
     * @param mixed $value
     * 
     * @return null
     */
    public function session(string $key, mixed $value)
    {
        $_SESSION[$key] =  $value;
    }

    /**
     * Send a cookie.
     * 
     * @param string $name
     * @param string $value=""
     * @param mixed $expires_or_options
     * @param string $path=""
     * @param string $domain=""
     * @param bool $secure
     * @param bool $httponly
     * 
     * @return bool
     */
    public function cookie(string $name, string $value = "", mixed $expires_or_options = 0, string $path = "", string $domain = "", bool $secure = false, bool $httponly = false): bool
    {
        return setcookie($name, $value, $expires_or_options, $path, $domain, $secure, $httponly);
    }
}
