<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

class CsrfMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        // Skip CSRF for biometric hardware endpoints (e.g. ADMS push / iclock cdata)
        if (str_starts_with($request->getUri(), '/api/zkteco/')) {
            return $next($request);
        }

        if (!Csrf::validate($request)) {
            if ($request->isAjax()) {
                return Response::json(['error' => 'CSRF token mismatch.'], 419);
            }
            Session::flash('error', 'انتهت صلاحية الجلسة، يرجى إعادة المحاولة.');
            $referer = $_SERVER['HTTP_REFERER'] ?? '/';
            return Response::redirect($referer);
        }

        return $next($request);
    }
}
