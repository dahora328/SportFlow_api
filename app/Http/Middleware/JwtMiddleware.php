<?php
namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        {
            try {
                JWTAuth::parseToken()->authenticate();
            } catch (Exception $e) {
                // Return a friendlier error message for unauthorized access
                return response()->json(['message' => 'Não autorizado'], 401);
            }

            return $next($request);
        }
    }
}
