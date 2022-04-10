<?php

namespace Crane\Router;

use Crane\Env;
use Crane\FileSystem\Mime;
use Crane\FileSystem\Storage;
use Crane\Router\Traits\CanHandleErrors;
use Crane\Router\Traits\CanRespondHttp;
use Crane\Router\Traits\HasMiddleware;
use Crane\Template\Template;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

class App
{

    use HasMiddleware, CanHandleErrors, CanRespondHttp;

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
        $this->request = Request::createFromGlobals();
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

    private function resolveMethod($function, $request, $response, $inMiddleware = false)
    {
        if (is_array($function)) {
            $newFunction  = function ($req, $resp) use ($function) {
                $f  = new $function[0];
                return $f->{$function[1]}($req, $resp);
            };
            $this->pushResponse($newFunction($request, $response), $inMiddleware);
        } else if (class_exists($function)) {
            $obj = new $function;
            $this->pushResponse($obj($request, $response), $inMiddleware);
        } else if (is_callable($function)) {
            $this->pushResponse($function($request, $response), $inMiddleware);
        }
    }

    private function pushResponse($response, $inMiddleware = false)
    {
        if (!$inMiddleware && !is_a($response, \Symfony\Component\HttpFoundation\Response::class)) {
            throw new \Error("Empty response from controller");
        } else if ($inMiddleware && !is_a($response, \Symfony\Component\HttpFoundation\Response::class)) {
            //If we are in a middleware and response object is null, just return
            return;
        }
        $response->send();
        die;
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
            return (($mw->path != null) && (preg_match('#^' . $mw->path . '$#', $request->server->get('REQUEST_URI'))));
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
    }

    private function outputStream(string $filepath)
    {
        $mime = Mime::mime($filepath);
        $response = new StreamedResponse(function () use ($filepath) {
            ob_start();
            $this->stream($filepath);
            ob_end_flush();
        }, 200, ['content-type' => $mime, 'X-FRAME-OPTIONS' => 'DENY']);

        $response->send();
        die;
    }

    private function staticFileExists($request, $response)
    {

        $path = ltrim($request->server->get('REQUEST_URI'), '/');

        foreach ($this->statics as $pair) {
            if (count($pair) === 2) {
                if (preg_match("#^{$pair[0]}#", $path)) {
                    $actualPath = str_replace($pair[0], $pair[1], $path);
                    if (file_exists($actualPath)) {
                        return $actualPath;
                    }
                }
            } else if (count($pair) === 1) {
                if (preg_match("#^{$pair[0]}#", $path)) {
                    if (file_exists($path)) {
                        return $path;
                    }
                }
            }
        }
        return false;
    }

    private function streamStatic($request, $response)
    {
        $path = ltrim($request->server->get('REQUEST_URI'), '/');

        // Anything with an extension is automatically eligible
        if (preg_match("#\.\w+$#", $path)) {

            if ($filepath = $this->staticFileExists($request, $response)) {
                $this->outputStream($filepath);
                die;
            }

            die($this->doesNotExist($request, $response));
        }
    }

    private function doesNotExist(Request $request, Response $response)
    {
        $value = (new Template('views/errors/404.php'))->template([]);
        $response = new Response($value, 404, ['content-type' => 'text/html', 'X-FRAME-OPTIONS' => 'DENY']);
        $response->send();
        die;
    }

    public function run(string $path)
    {



        $route = $this->matchRoute($path);

        $this->response = new Response();

        if ($route || ($this->staticFileExists($this->request, $this->response))) {

            $args = [];

            if ($route) {
                foreach ($route->matches as $p => $q) {
                    (preg_match("#^[a-zA-Z_]+[\w]+$#", $p)) && ($args[$p] = $q);
                }
            }

            $this->request->params = new ParameterBag([]);

            foreach ($args as $k => $v) {
                $this->request->params->set($k, $v);
            }

            $this->runGlobalMiddleware($this->request, $this->response);
            $this->runLocalMiddleware($this->request, $this->response);


            $this->streamStatic($this->request, $this->response);

            $method = Route::GET;
            $method = ($this->request->server->get('REQUEST_METHOD') === Route::POST) ? Route::POST : $method;

            foreach ($route->handlers as $rh) {
                if ($rh->method === Route::ALL) {
                    $function  = $rh->function;
                    $this->resolveMethod($function, $this->request, $this->response);
                    die;
                }
            }

            foreach ($route->handlers as $rh) {
                if ($method === $rh->method) {
                    $function  = $rh->function;
                    $this->resolveMethod($function, $this->request, $this->response);
                    die;
                }
            }

            $this->doesNotExist($this->request, $this->response);
        } else {
            $this->doesNotExist($this->request, $this->response);
        }
    }
}
