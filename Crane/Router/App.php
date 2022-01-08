<?php

namespace Crane\Router;

use Crane\Env;
use Crane\FileSystem\Storage;
use Crane\Crane;
use Crane\Router\Traits\CanHandleErrors;
use Crane\Router\Traits\CanRespondHttp;
use Crane\Router\Traits\HasMiddleware;

class App
{

    use HasMiddleware, CanHandleErrors, CanRespondHttp;

    const IMAGES = ['jpg', 'jpeg', 'png', 'gif'];
    const VIDEOS = ['mp4'];
    const TEXT = ['txt', 'html', 'json'];
    const CSS = ['css'];
    const SCRIPTS = ['js'];

    private $variables = [];

    private $routes = [];
    private $middleware = [];

    private $__currentPath;

    private $request;

    private $statics = [];

    public function __construct(array $request, array $server)
    {
        Env::load(Storage::root() . '.env');
        header_remove('X-Powered-By');
        ini_set('expose_php', 'off');
        $this->request = Request::create($server, $request);
    }

    public function __get($name)
    {
        return $this->variables[$name] ?? null;
    }

    public function __set($name, $value)
    {
        $this->variables[$name] = $value;
    }

    public function startSession($name = null)
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_name($name);
            session_start();
        } else {
            session_regenerate_id();
        }
    }

    public function timezone(string $tz = null)
    {
        if ($tz) {
            date_default_timezone_set($tz);
        } else {
            return date_default_timezone_get();
        }
    }

    public function static(...$args)
    {
        if (count($args) === 1) {
            $this->statics[] = [$args[0]];
        } else if (count($args) === 2) {
            $this->statics[] = [$args[0], $args[1]];
        }
    }

    public function routes()
    {
        return $this->routes;
    }

    private function routeExists($path)
    {
        foreach ($this->routes as $route) {
            if ($route->path === ($path . Route::OPTIONAL_REGEX)) {
                return true;
            }
        }
    }

    private function matchRoute(string $path)
    {
        foreach ($this->routes as $route) {
            if (preg_match('#^' . $route->path . '$#', $path, $matches)) {
                $route->matches = $matches;
                return $route;
            }
        }
        return false;
    }

    private function pushResponse($response, $inMiddleware = false)
    {
        if (!$inMiddleware && !is_a($response, Response::class)) {
            throw new \Error("Empty response from controller");
        } else if ($inMiddleware && !is_a($response, Response::class)) {
            //If we are in a middleware and response object is null, just return
            return;
        }

        foreach ($response->headers as $k => $v) {
            header("$k: $v");
        }
        echo $response->body;
    }

    private function runGlobalMiddleware($request, $response)
    {
        $m = array_filter($this->middleware, function ($mw) {
            return $mw->path === null;
        });

        foreach ($m as $index => $mw) {
            $this->resolveMethod($mw->handler, $request, $response, true);
        }
    }

    private function runLocalMiddleware($request, $response)
    {
        $m = array_filter($this->middleware, function ($mw) use ($request) {
            return (($mw->path != null) && (preg_match('#^' . $mw->path . '$#', $request->http->path)));
        });

        foreach ($m as $index => $mw) {
            $this->resolveMethod($mw->handler, $request, $response, true);
        }
    }

    private function stream($filename, $retbytes = false)
    {
        $buffer = '';
        $cnt    = 0;
        $handle = fopen($filename, 'rb');

        if ($handle === false) {
            return false;
        }

        while (!feof($handle)) {
            $buffer = fread($handle, 1024 * 1024);
            echo $buffer;
            ob_flush();
            flush();

            if ($retbytes) {
                $cnt += strlen($buffer);
            }
        }

        $status = fclose($handle);

        if ($retbytes && $status) {
            // return num. bytes delivered like readfile() does.
            return $cnt;
        }

        //return $status;
        die;
    }

    private function decideStaticHeader($extension)
    {
        $contentType = 'text/html';
        if (in_array($extension, self::CSS)) {
            $contentType = 'text/css';
        } else if (in_array($extension, self::TEXT)) {
            $extension = 'text/' . $extension;
        } else if (in_array($extension, self::IMAGES)) {
            $contentType = 'image/' . $extension;
        } else if (in_array($extension, self::VIDEOS)) {
            $extension = 'video/' . $extension;
        } else if (in_array($extension, self::SCRIPTS)) {
            $contentType = 'text/javascript';
        }
        header('X-FRAME-OPTIONS: DENY');
        header('Content-Type: ' . $contentType);
    }

    private function streamStatic($request, $response)
    {
        $extensions = array_merge(self::CSS, self::TEXT, self::IMAGES, self::VIDEOS, self::SCRIPTS);
        $pathExt = strtolower(pathinfo($request->http->url, PATHINFO_EXTENSION));

        if (in_array($pathExt, $extensions)) {
            $path = ltrim($request->http->path, '/');

            foreach ($this->statics as $pair) {
                if (count($pair) === 2) {
                    if (preg_match("#^{$pair[0]}#", $path)) {
                        $actualPath = str_replace($pair[0], $pair[1], $path);
                        if (file_exists($actualPath)) {
                            $this->decideStaticHeader($pathExt);
                            $this->stream($actualPath);
                        }
                    }
                } else if (count($pair) === 1) {
                    if (preg_match("#^{$pair[0]}#", $path)) {
                        if (file_exists($path)) {
                            $this->decideStaticHeader($pathExt);
                            $this->stream($path);
                        }
                    }
                }
            }
            die($this->doesNotExist($request, $response));
        }
    }

    private function doesNotExist($request, $response)
    {
        $this->pushResponse((function ($req, $resp) {
            return $resp->render("errors/404.php");
        })($request, $response));
        die;
    }

    private function resolveMethod($function, $request, $response, $inMiddleware = false)
    {
        if (is_array($function)) {
            $newFunction  = function ($req, $resp) use ($function) {
                $f  = new $function[0];
                return $f->{$function[1]}($req, $resp);
            };
            $this->pushResponse($newFunction($request, $response), $inMiddleware);
        } else {
            $this->pushResponse($function($request, $response), $inMiddleware);
        }
    }

    public function run(string $path)
    {

        $args = [];

        $route = $this->matchRoute($path);

        $request = new Request;
        foreach ($this->request as $k => $v) {
            if (!preg_match("#^/#", $k)) {
                if (is_array($v)) {
                    $v = new Crane($v);
                }
                $request->$k = $v;
            }
        }
        $request->params = new Crane([]);


        $this->request = $request;


        $response = new Response;
        $this->response = $response;


        $this->streamStatic($this->request, $response);

        if ($route) {

            foreach ($route->matches as $p => $q) {
                (preg_match("#^[a-zA-Z_]+[\w]+$#", $p)) && ($args[$p] = $q);
            }
            foreach ($args as $k => $v) {
                $request->params->$k = $v;
            }


            $this->runGlobalMiddleware($request, $response);
            $this->runLocalMiddleware($request, $response);

            $method = Route::GET;
            $method = ($request->http->method === Route::POST) ? Route::POST : $method;

            foreach ($route->handlers as $rh) {
                if ($rh->method === Route::ALL) {
                    $function  = $rh->function;
                    $this->resolveMethod($function, $request, $response);
                    die;
                }
            }

            foreach ($route->handlers as $rh) {
                if ($method === $rh->method) {
                    $function  = $rh->function;
                    $this->resolveMethod($function, $request, $response);
                    die;
                }
            }

            $this->doesNotExist($request, $response);
        } else {
            $this->doesNotExist($request, $response);
        }
    }
}
