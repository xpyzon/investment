<?php
declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];

    public function add(string $method, string $path, callable|array $handler): void
    {
        $this->routes[] = [strtoupper($method), $this->compile($path), $handler];
    }

    public function get(string $path, callable|array $handler): void { $this->add('GET', $path, $handler); }
    public function post(string $path, callable|array $handler): void { $this->add('POST', $path, $handler); }
    public function put(string $path, callable|array $handler): void { $this->add('PUT', $path, $handler); }
    public function patch(string $path, callable|array $handler): void { $this->add('PATCH', $path, $handler); }
    public function delete(string $path, callable|array $handler): void { $this->add('DELETE', $path, $handler); }

    private function compile(string $path): array
    {
        $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $path);
        $regex = '#^' . $pattern . '$#';
        return [$path, $regex];
    }

    public function dispatch(): void
    {
        header('Content-Type: application/json');
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        foreach ($this->routes as [$m, $compiled, $handler]) {
            if ($m !== strtoupper($method)) continue;
            [$routePath, $regex] = $compiled;
            if (preg_match($regex, $uri, $matches)) {
                $params = [];
                foreach ($matches as $k => $v) {
                    if (!is_int($k)) $params[$k] = $v;
                }
                $request = new Request($params);
                $response = new Response();

                if (is_array($handler)) {
                    [$class, $methodName] = $handler;
                    $instance = new $class($request, $response);
                    $instance->$methodName();
                } else {
                    $handler($request, $response);
                }
                return;
            }
        }

        http_response_code(404);
        echo json_encode(['error' => 'Not Found']);
    }
}