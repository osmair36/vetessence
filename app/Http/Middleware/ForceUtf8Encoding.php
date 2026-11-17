<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceUtf8Encoding
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Force UTF-8 charset in all HTML responses
        if (method_exists($response, 'header')) {
            $contentType = $response->headers->get('Content-Type', '');
            if (empty($contentType) || str_contains($contentType, 'text/html')) {
                $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
            }
        }

        return $response;
    }
}
