<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class APIKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expectedKey = env('WORDLE_API_KEY');

        if ($request->header('x-api-key') != $expectedKey) {
            return response()->json([
                'message' => 'Unauthorized: Invalid API key'
            ], 401);
        }

        return $next($request);
    }
}
