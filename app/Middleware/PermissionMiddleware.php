<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;

class PermissionMiddleware implements Middleware
{
    private string $permission;

    public function __construct(string $permission = '')
    {
        $this->permission = $permission;
    }

    public function handle(Request $request, callable $next): Response
    {
        $auth = Auth::getInstance();
        if ($auth->guest()) {
            return Response::redirect(url('login'));
        }

        if ($this->permission !== '' && !$auth->can($this->permission)) {
            if ($request->isAjax()) {
                return Response::json(['error' => 'Forbidden: Missing permission ' . $this->permission], 403);
            }
            return View::render('errors/403', ['permission' => $this->permission], 'layouts/error')->setStatusCode(403);
        }

        return $next($request);
    }
}
