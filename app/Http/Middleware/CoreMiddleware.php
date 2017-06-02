<?php

namespace App\Http\Middleware;

use Closure;

class CoreMiddleware
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
        \Swirf::processInput($request->getContent());
        return $next($request);
    }
}