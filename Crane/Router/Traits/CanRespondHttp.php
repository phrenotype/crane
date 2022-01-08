<?php

namespace Crane\Router\Traits;

use Crane\Router\App;
use Crane\Router\Route;
use Crane\Router\RouteHandler;

trait CanRespondHttp
{
    public function post(...$arguments)
    {
        if (count($arguments) === 1) {
            if ($this->__currentPath) {
                $this->addToRouteHandlers($this->__currentPath, new RouteHandler(Route::POST, $arguments[0]));
            }
        } else if (count($arguments) === 2) {
            $this->__currentPath = null;
            $this->addToRouteHandlers($arguments[0], new RouteHandler(Route::POST, $arguments[1]));
        }
        return $this;
    }

    public function get(...$arguments)
    {
        if (count($arguments) === 1) {
            if ($this->__currentPath) {
                $this->addToRouteHandlers($this->__currentPath, new RouteHandler(Route::GET, $arguments[0]));
            }
        } else if (count($arguments) === 2) {
            $this->__currentPath = null;
            $this->addToRouteHandlers($arguments[0], new RouteHandler(Route::GET, $arguments[1]));
        }
        return $this;
    }

    public function all(...$arguments)
    {
        if (count($arguments) === 1) {
            if ($this->__currentPath) {
                $this->addToRouteHandlers($this->__currentPath, new RouteHandler(Route::ALL, $arguments[0]));
            }
        } else if (count($arguments) === 2) {
            $this->__currentPath = null;
            $this->addToRouteHandlers($arguments[0], new RouteHandler(Route::ALL, $arguments[1]));
        }
        return $this;
    }



    public function route(string $path): App
    {
        $this->__currentPath = $path;
        $this->addToRouteHandlers($path);
        return $this;
    }

    private function addToRouteHandlers($path, RouteHandler $rh = null)
    {
        if ($this->routeExists($path)) {
            foreach ($this->routes as $route) {
                if ($route->path === ($path . Route::OPTIONAL_REGEX)) {
                    $foundHandler = false;
                    foreach ($route->handlers as $h) {
                        if ($h->method === $rh->method && $h->function === $rh->function) {
                            $foundHandler = true;
                        }
                    }
                    if (!$foundHandler) {
                        $route->handlers[] = $rh;
                    }
                }
            }
        } else {
            $route = new Route($path . Route::OPTIONAL_REGEX);
            if ($rh) {
                $route->handlers[] = $rh;
            }
            $this->routes[] = $route;
        }
    }    
}
