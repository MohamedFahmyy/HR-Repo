<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;

class GuestMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (Auth::getInstance()->check()) {
            return Response::redirect(url('dashboard'));
        }

        return $next($request);
    }
}
