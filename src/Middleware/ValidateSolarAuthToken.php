<?php

namespace SolarAbyss\Auth\Middleware;

use SolarAbyss\Auth\Facades\Solarize;
use Closure;

class ValidateSolarAuthToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Solarize::isAuthenticated($request)) {
            return $next($request);
        }

        return response()->json(['message' => 'Unauthorized.'], 401);        
    }
}

?>
