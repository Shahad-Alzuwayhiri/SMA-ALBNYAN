<?php
// Laravel-like helper functions for standalone PHP

if (!function_exists('route')) {
    function route($name, $params = []) {
        // Map route names to URLs (add more as needed)
        $routes = [
            'dashboard' => '/dashboard',
            'manager.dashboard' => '/manager-dashboard',
            'login' => '/login',
            'register' => '/register',
            'profile' => '/profile',
            'profile.update' => '/profile',
            'contracts.index' => '/contracts',
            'contracts.create' => '/contracts/create',
            'contracts.show' => '/contracts/{id}',
            'contracts.edit' => '/contracts/{id}/edit',
            'contracts.pdf' => '/contracts/{id}/pdf',
            'contracts.in-progress' => '/contracts-in-progress',
            'contracts.closed' => '/contracts-closed',
            'notifications' => '/notifications',
            'notifications.read' => '/notifications/{id}/read',
            'notifications.mark-all-read' => '/notifications/mark-all-read',
            'notifications.clear-all' => '/notifications/clear-all',
            'tasks.update' => '/tasks/{id}/update',
            'tasks.delete' => '/tasks/{id}',
            // ... add more as needed
        ];
        $url = $routes[$name] ?? '/';
        // Replace {id} with param if present
        if (is_array($params) && isset($params['id'])) {
            $url = str_replace('{id}', $params['id'], $url);
        } elseif (is_scalar($params)) {
            $url = str_replace('{id}', $params, $url);
        }
        return $url;
    }
}

if (!function_exists('view')) {
    function view($name, $data = []) {
        require_once __DIR__ . '/SimpleViewEngine.php';
        return SimpleViewEngine::render($name, $data);
    }
}

if (!function_exists('redirect')) {
    function redirect($url) {
        header('Location: ' . $url);
        exit;
    }
}

if (!function_exists('old')) {
    function old($key, $default = '') {
        return $_POST[$key] ?? $default;
    }
}

if (!function_exists('session')) {
    function session($key = null, $value = null) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if ($key && $value !== null) {
            $_SESSION[$key] = $value;
        }
        if ($key) {
            return $_SESSION[$key] ?? null;
        }
        return $_SESSION;
    }
}

if (!function_exists('asset')) {
    function asset($path) {
        return '/static/' . ltrim($path, '/');
    }
}

if (!function_exists('auth')) {
    function auth() {
        return new class {
            public function user() {
                if (isset($_SESSION['user_id'])) {
                    $user = (object)[
                        'id' => $_SESSION['user_id'],
                        'name' => $_SESSION['user_name'] ?? 'مستخدم',
                        'email' => $_SESSION['user_email'] ?? '',
                        'role' => $_SESSION['user_role'] ?? 'employee',
                    ];
                    $user->isManager = function() use ($user) { 
                        return $user->role === 'manager'; 
                    };
                    return $user;
                }
                // Default demo user for testing
                $user = (object)[
                    'id' => 1,
                    'name' => 'أحمد محمد',
                    'email' => 'ahmed@example.com',
                    'role' => 'manager',
                ];
                $user->isManager = function() use ($user) { 
                    return $user->role === 'manager'; 
                };
                return $user;
            }
            public function check() {
                return isset($_SESSION['user_id']) || true; // Demo: always authenticated
            }
        };
    }
}

if (!function_exists('back')) {
    function back() {
        return new class {
            public function withErrors($errors) {
                $_SESSION['errors'] = $errors;
                return $this;
            }
            public function with($key, $value) {
                $_SESSION[$key] = $value;
                return $this;
            }
            public function onlyInput($key) {
                return $this;
            }
            public function __destruct() {
                $url = $_SERVER['HTTP_REFERER'] ?? '/';
                header('Location: ' . $url);
                exit;
            }
        };
    }
}

if (!function_exists('now')) {
    function now() {
        return new class {
            public function subDays($days) {
                return new class($days) {
                    private $days;
                    public function __construct($days) { $this->days = $days; }
                    public function format($format) {
                        return date($format, strtotime("-{$this->days} days"));
                    }
                };
            }
            public function subHours($hours) {
                return new class($hours) {
                    private $hours;
                    public function __construct($hours) { $this->hours = $hours; }
                    public function format($format) {
                        return date($format, strtotime("-{$this->hours} hours"));
                    }
                };
            }
            public function subMonths($months) {
                return new class($months) {
                    private $months;
                    public function __construct($months) { $this->months = $months; }
                    public function format($format) {
                        return date($format, strtotime("-{$this->months} months"));
                    }
                };
            }
            public function subMinutes($minutes) {
                return new class($minutes) {
                    private $minutes;
                    public function __construct($minutes) { $this->minutes = $minutes; }
                    public function format($format) {
                        return date($format, strtotime("-{$this->minutes} minutes"));
                    }
                };
            }
            public function format($format) {
                return date($format);
            }
        };
    }
}

// Global variables for views
if (!isset($GLOBALS['errors'])) {
    $GLOBALS['errors'] = new class {
        public function any() {
            return !empty($_SESSION['errors']);
        }
        public function all() {
            return $_SESSION['errors'] ?? [];
        }
    };
}

if (!function_exists('response')) {
    function response($content, $status = 200, $headers = []) {
        http_response_code($status);
        foreach ($headers as $key => $value) {
            header("$key: $value");
        }
        return $content;
    }
}

if (!function_exists('compact')) {
    function compact() {
        $args = func_get_args();
        $result = [];
        $caller_vars = debug_backtrace()[0];
        foreach ($args as $arg) {
            if (isset($caller_vars[$arg])) {
                $result[$arg] = $caller_vars[$arg];
            }
        }
        return $result;
    }
}
