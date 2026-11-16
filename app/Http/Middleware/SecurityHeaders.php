<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Thêm các header bảo mật cơ bản cho mọi response trong nhóm middleware `web`
 * (gồm cả `/admin`, vì các header này không chặn inline script/style của Filament).
 *
 * Content-Security-Policy CHƯA bật ở đây: cần liệt kê đủ nguồn inline script
 * (GA4, FB Pixel, Livewire/Filament) trước khi áp CSP, tránh vỡ chức năng.
 * Sẽ bổ sung sau khi rà soát riêng.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        return $response;
    }
}
