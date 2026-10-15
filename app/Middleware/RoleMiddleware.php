<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;

class RoleMiddleware implements Middleware
{
    private array $allowedRoles;

    public function __construct(string|array ...$roles)
    {
        $this->allowedRoles = is_array($roles[0] ?? null) ? $roles[0] : $roles;
    }

    public function handle(Request $request, callable $next): Response
    {
        $auth = Auth::getInstance();
        if ($auth->guest()) {
            return Response::redirect(url('login'));
        }

        if (!empty($this->allowedRoles) && !$auth->hasRole($this->allowedRoles)) {
            if ($request->isAjax()) {
                return Response::json(['error' => 'Forbidden: Insufficient role permissions.'], 403);
            }
            return View::render('errors/403', ['role' => implode(', ', $this->allowedRoles)], 'layouts/error')->setStatusCode(403);
        }

        return $next($request);
    }
}
