<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

class AuthMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (Auth::getInstance()->guest()) {
            if ($request->isAjax()) {
                return Response::json(['error' => 'Unauthenticated.'], 401);
            }
            Session::flash('error', 'يجب تسجيل الدخول أولاً للوصول إلى هذه الصفحة.');
            return Response::redirect(url('login'));
        }

        return $next($request);
    }
}
