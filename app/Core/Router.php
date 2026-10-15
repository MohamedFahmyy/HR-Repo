<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private Request $request;
    private Response $response;
    private array $routes = [];
    private array $groupStack = [];

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function get(string $path, array|callable $handler, array $middlewares = []): self
    {
        return $this->addRoute('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, array|callable $handler, array $middlewares = []): self
    {
        return $this->addRoute('POST', $path, $handler, $middlewares);
    }

    public function put(string $path, array|callable $handler, array $middlewares = []): self
    {
        return $this->addRoute('PUT', $path, $handler, $middlewares);
    }

    public function delete(string $path, array|callable $handler, array $middlewares = []): self
    {
        return $this->addRoute('DELETE', $path, $handler, $middlewares);
    }

    public function group(array $attributes, callable $callback): void
    {
        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }

    private function addRoute(string $method, string $path, array|callable $handler, array $middlewares = []): self
    {
        $prefix = '';
        $groupMiddlewares = [];

        foreach ($this->groupStack as $group) {
            if (isset($group['prefix'])) {
                $prefix .= '/' . trim($group['prefix'], '/');
            }
            if (isset($group['middleware'])) {
                $m = is_array($group['middleware']) ? $group['middleware'] : [$group['middleware']];
                $groupMiddlewares = array_merge($groupMiddlewares, $m);
            }
        }

        $fullPath = '/' . trim($prefix . '/' . trim($path, '/'), '/');
        if ($fullPath === '//') {
            $fullPath = '/';
        }

        $allMiddlewares = array_merge($groupMiddlewares, $middlewares);

        // Convert {param} to regex pattern
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $fullPath);
        $pattern = '#^' . $pattern . '$#';

        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $fullPath,
            'pattern' => $pattern,
            'handler' => $handler,
            'middlewares' => $allMiddlewares,
        ];

        return $this;
    }

    public function dispatch(): Response
    {
        $requestMethod = $this->request->getMethod();
        $requestUri = $this->request->getUri();
        $matchedRoute = null;
        $params = [];
        $allowedMethods = [];

        foreach ($this->routes as $route) {
            if (preg_match($route['pattern'], $requestUri, $matches)) {
                $allowedMethods[] = $route['method'];
                if ($route['method'] === $requestMethod) {
                    $matchedRoute = $route;
                    foreach ($matches as $key => $value) {
                        if (is_string($key)) {
                            $params[$key] = urldecode($value);
                        }
                    }
                    break;
                }
            }
        }

        if ($matchedRoute === null) {
            if (!empty($allowedMethods)) {
                return $this->response->setStatusCode(405)->setContent('Method Not Allowed');
            }
            return View::render('errors/404', ['title' => 'Page Not Found'], 'layouts/error')->setStatusCode(404);
        }

        // Run Middlewares Pipeline
        $middlewares = $matchedRoute['middlewares'];
        $handler = $matchedRoute['handler'];

        $pipeline = function ($request) use ($handler, $params) {
            if (is_callable($handler)) {
                $res = call_user_func_array($handler, array_merge([$request], $params));
            } elseif (is_array($handler) && count($handler) === 2) {
                [$controllerClass, $action] = $handler;
                if (!class_exists($controllerClass)) {
                    throw new \RuntimeException("Controller {$controllerClass} does not exist.");
                }
                $controller = new $controllerClass();
                if (!method_exists($controller, $action)) {
                    throw new \RuntimeException("Action {$action} not found on controller {$controllerClass}.");
                }
                $res = call_user_func_array([$controller, $action], array_merge([$request], $params));
            } else {
                throw new \RuntimeException("Invalid route handler specified.");
            }

            if ($res instanceof Response) {
                return $res;
            }
            if (is_string($res)) {
                return new Response($res);
            }
            if (is_array($res)) {
                return Response::json($res);
            }
            return new Response();
        };

        // Wrap middlewares in reverse order
        while ($middlewareClass = array_pop($middlewares)) {
            $next = $pipeline;
            $pipeline = function ($request) use ($middlewareClass, $next) {
                if (is_string($middlewareClass)) {
                    $instance = new $middlewareClass();
                    return $instance->handle($request, $next);
                }
                if (is_callable($middlewareClass)) {
                    return $middlewareClass($request, $next);
                }
                return $next($request);
            };
        }

        return $pipeline($this->request);
    }
}
