<?php

namespace Crane\Router;

use Crane\FileSystem\Mime;
use Crane\FileSystem\Storage;
use Crane\Template\Template;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
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
    public function generic(string $html): HttpFoundationResponse
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
        return $response = new JsonResponse($data);
    }
}
