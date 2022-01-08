<?php

namespace Crane\Router\Traits;

use Crane\Router\Middleware;
use Crane\Router\Route;

trait HasMiddleware
{
    private function addToMiddleware(Middleware $middleware)
    {
        $exists = false;
        if ($middleware->path) {
            $middleware->path = $middleware->path . Route::OPTIONAL_REGEX;
        }

        foreach ($this->middleware as $m) {
            if (($m->path) === ($middleware->path) && $m->handler === $middleware->handler) {
                $exists = true;
            }
        }
        if (!$exists) {
            $this->middleware[] = $middleware;
        }
    }

    public function middleware(...$arguments)
    {
        if (count($arguments) === 1) {
            //call the function for every route
            $this->addToMiddleware(new Middleware(null, $arguments[0]));
        } else if (count($arguments) === 2) {
            //restrict call to only that particular route
            $this->addToMiddleware(new Middleware($arguments[0], $arguments[1]));
        }
        return $this;
    }
}
