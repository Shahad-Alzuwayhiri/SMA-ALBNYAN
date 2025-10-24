<?php

declare(strict_types=1);

namespace App;

/**
 * Simple Router Class
 * فئة التوجيه البسيطة
 */
class Router
{
    private $routes = [];
    private $basePath;
    
    public function __construct(string $basePath = '')
    {
        $this->basePath = rtrim($basePath, '/');
    }
    
    public function get(string $path, $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }
    
    public function post(string $path, $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }
    
    private function addRoute(string $method, string $path, $handler): void
    {
        $path = $this->basePath . '/' . ltrim($path, '/');
        $this->routes[$method][$path] = $handler;
    }
    
    public function dispatch()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Clean path - remove trailing slash and normalize
        $path = rtrim($path, '/');
        if (empty($path)) {
            $path = '/';
        }
        
        // Check for exact match first
        if (isset($this->routes[$method][$path])) {
            $handler = $this->routes[$method][$path];
            return $this->callHandler($handler);
        }
        
        // Try to match with parameters
        foreach ($this->routes[$method] ?? [] as $route => $handler) {
            if ($this->matchRoute($route, $path)) {
                return $this->callHandler($handler, $this->extractParams($route, $path));
            }
        }
        
        // 404 Not Found
        http_response_code(404);
        $this->show404($path);
    }
    
    private function show404($path)
    {
        echo "<!DOCTYPE html>
<html lang='ar' dir='rtl'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>الصفحة غير موجودة - 404</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>
</head>
<body class='bg-light'>
    <div class='container-fluid d-flex align-items-center justify-content-center min-vh-100'>
        <div class='row justify-content-center w-100'>
            <div class='col-lg-6 col-md-8'>
                <div class='text-center'>
                    <h1 class='display-1 text-primary'><i class='fas fa-exclamation-triangle'></i></h1>
                    <h2 class='mb-4'>404 - الصفحة غير موجودة</h2>
                    <p class='lead'>عذراً، الصفحة التي تبحث عنها غير موجودة.</p>
                    <p>المسار المطلوب: <code>" . htmlspecialchars($path) . "</code></p>
                    <div class='mt-4'>
                        <a href='/' class='btn btn-primary btn-lg me-3'>
                            <i class='fas fa-home me-2'></i>العودة للرئيسية
                        </a>
                        <a href='/contracts' class='btn btn-outline-primary btn-lg'>
                            <i class='fas fa-file-contract me-2'></i>إدارة العقود
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>";
    }
    
    private function matchRoute($route, $path)
    {
        $routePattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $route);
        $routePattern = '@^' . $routePattern . '$@';
        return preg_match($routePattern, $path);
    }
    
    private function extractParams($route, $path)
    {
        $routePattern = preg_replace('/\{([^}]+)\}/', '(?P<$1>[^/]+)', $route);
        $routePattern = '@^' . $routePattern . '$@';
        preg_match($routePattern, $path, $matches);
        
        $params = [];
        foreach ($matches as $key => $value) {
            if (!is_numeric($key)) {
                $params[$key] = $value;
            }
        }
        
        return $params;
    }
    
    private function callHandler($handler, $params = [])
    {
        if (is_string($handler)) {
            // Handle "Controller@method" format
            if (strpos($handler, '@') !== false) {
                list($controller, $method) = explode('@', $handler);
                $controllerClass = "App\\Controllers\\{$controller}";
                
                if (class_exists($controllerClass)) {
                    $controllerInstance = new $controllerClass();
                    if (method_exists($controllerInstance, $method)) {
                        return call_user_func_array([$controllerInstance, $method], array_values($params));
                    }
                }
            }
        } elseif (is_callable($handler)) {
            return call_user_func_array($handler, array_values($params));
        }
        
        // Handler not found
        http_response_code(500);
        echo "Handler not found: " . print_r($handler, true);
    }
}