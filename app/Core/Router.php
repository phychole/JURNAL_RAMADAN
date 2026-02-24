<?php
namespace App\Core;

class Router {
  private array $routes = ['GET'=>[], 'POST'=>[]];

  public function get(string $path, string $handler): void { $this->routes['GET'][$path] = $handler; }
  public function post(string $path, string $handler): void { $this->routes['POST'][$path] = $handler; }

  public function dispatch(string $method, string $path): void {
    $method = strtoupper($method);
    $handler = $this->routes[$method][$path] ?? null;
    if (!$handler) {
      http_response_code(404);
      echo "404 Not Found";
      return;
    }
    [$class, $action] = explode('@', $handler);
    $controller = new $class();
    $controller->$action();
  }
}
