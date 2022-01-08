<?php

namespace Crane\Router;

class Middleware
{
    public $path;
    public $handler;

    public function __construct(string $path = null, $handler)
    {
        if(!is_array($handler) && !is_callable($handler)){
            throw new \Error("Middleware handler must be a callable.");
        }
        $this->path = $path;
        $this->handler = $handler;
    }
}
