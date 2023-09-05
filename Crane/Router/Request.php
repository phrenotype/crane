<?php

namespace Crane\Router;

use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;

class Request extends HttpFoundationRequest
{
    public $params = [];
    
    /**
     * Get a session value.
     * 
     * @param null $key
     * 
     * @return mixed
     */
    public function session($key = null): mixed
    {
        if ($key) {
            return $_SESSION[$key] ?? null;
        } else {
            return $_SESSION;
        }
    }

    /**
     * Get a cookie value.
     * 
     * @param null $key
     * 
     * @return mixed
     */
    public function cookie($key = null): mixed
    {
        if ($key) {
            return $_COOKIE[$key] ?? null;
        } else {
            return $_COOKIE;
        }
    }
}
