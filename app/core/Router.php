<?php

final class Router
{
    private array $routes = [];

    /** @param string|array $roles  '*' = public, 'auth' = any signed-in user, or list of roles */
    public function add(string $method, string $pattern, array $handler, $roles = '*'): void
    {
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', rtrim($pattern, '/')) . '/?$#';
        $this->routes[] = [$method, $regex, $handler, $roles];
    }

    public function get(string $p, array $h, $roles = '*'): void  { $this->add('GET', $p, $h, $roles); }
    public function post(string $p, array $h, $roles = '*'): void { $this->add('POST', $p, $h, $roles); }

    public function dispatch(string $method, string $path): void
    {
        $path = '/' . trim($path, '/');
        $allowed = false;
        foreach ($this->routes as [$m, $regex, $handler, $roles]) {
            if (!preg_match($regex, $path, $mm)) {
                continue;
            }
            $allowed = true;
            if ($m !== $method) {
                continue;
            }
            $this->guard($roles);
            if ($method === 'POST') {
                Csrf::verify();
            }
            $params = array_filter($mm, 'is_string', ARRAY_FILTER_USE_KEY);
            [$class, $action] = $handler;
            (new $class())->$action(...array_values($params));
            return;
        }
        http_response_code($allowed ? 405 : 404);
        View::render('errors/404', ['title' => 'Page not found'], 'auth');
    }

    private function guard($roles): void
    {
        if ($roles === '*') {
            return;
        }
        if (!Auth::check()) {
            if (is_ajax()) {
                json_out(['error' => 'Unauthenticated'], 401);
            }
            $_SESSION['intended'] = $_SERVER['REQUEST_URI'] ?? null;
            redirect('login');
        }
        if ($roles === 'auth') {
            return;
        }
        if (!in_array(Auth::role(), (array) $roles, true)) {
            if (is_ajax()) {
                json_out(['error' => 'Forbidden'], 403);
            }
            http_response_code(403);
            View::render('errors/403', ['title' => 'Access denied']);
            exit;
        }
    }
}
