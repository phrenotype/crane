<?php

namespace Crane\Router;

class RouteHandler
{
    public $method;
    public $function;

    public function __construct($method, $function)
    {
        $this->method = $method;
        $this->function = $function;
    }
}
