<?php

abstract class Controller
{
    protected function view(string $view, array $data = [], string $layout = 'app'): void
    {
        View::render($view, $data, $layout);
    }

    /** Run a state-changing action: on business-rule failure flash the message and go back. */
    protected function attempt(callable $fn, ?string $successMsg = null, ?string $redirect = null): void
    {
        try {
            $result = $fn();
            if ($successMsg) flash('success', $successMsg);
            if (is_string($result) && $result !== '') redirect($result);
            $redirect ? redirect($redirect) : redirect_back();
        } catch (DomainException | InvalidArgumentException $e) {
            with_old($_POST);
            flash('error', $e->getMessage());
            redirect_back();
        }
    }

    protected function failValidation(array $errors, string $msg = 'Please correct the highlighted fields.'): void
    {
        with_old($_POST, $errors);
        flash('error', $msg);
        redirect_back();
    }

    protected function notFound(): void
    {
        http_response_code(404);
        View::render('errors/404', ['title' => 'Not found'], 'auth');
        exit;
    }

    protected function forbid(): void
    {
        http_response_code(403);
        View::render('errors/403', ['title' => 'Access denied']);
        exit;
    }

    protected function wantsCsv(): bool
    {
        return in_array($_GET['export'] ?? '', ['csv', 'xlsx'], true);
    }
}
