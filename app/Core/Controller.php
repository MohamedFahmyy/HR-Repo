<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function view(string $template, array $data = [], string $layout = 'layouts/main'): Response
    {
        return View::render($template, $data, $layout);
    }

    protected function json(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    protected function redirect(string $url, int $status = 302): Response
    {
        return Response::redirect($url, $status);
    }

    protected function validate(Request $request, array $rules): array
    {
        $validator = new Validator($request->all(), $rules);
        if (!$validator->validate()) {
            Session::flash('errors', $validator->errors());
            Session::flash('old', $request->all());
            
            if ($request->isAjax()) {
                Response::json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422)->send();
                exit;
            }
            
            $referer = $_SERVER['HTTP_REFERER'] ?? '/';
            Response::redirect($referer)->send();
            exit;
        }

        return $validator->validated();
    }

    protected function authorize(string $permission): void
    {
        if (!Auth::getInstance()->can($permission)) {
            if (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
                Response::json(['error' => 'Forbidden: You lack permission ' . $permission], 403)->send();
                exit;
            }
            View::render('errors/403', ['permission' => $permission], 'layouts/error')->setStatusCode(403)->send();
            exit;
        }
    }

    protected function flash(string $type, string $message): void
    {
        Session::flash($type, $message);
    }
}
