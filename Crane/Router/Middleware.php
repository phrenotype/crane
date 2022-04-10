<?php

namespace Crane\Router;

class Middleware
{
    public $path;
    public $handler;

    public function __construct(string $path = null, $handler)
    {

        if (!is_array($handler) && !is_callable($handler) && (!class_exists($handler) && !is_callable(new $handler))) {
            throw new \Error("Middleware handler must be a callable or invokable object.");
        }

        $this->path = $path;
        $this->handler = $handler;
    }
}
