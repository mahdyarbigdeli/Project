<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CustomerAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if the customer is authenticated using the 'customer' guard
        if (!Auth::guard('customer')->check()) {
            // If not authenticated, redirect to the login page or show an error
            return redirect()->route('index'); // Adjust to your login route
        }
        // Proceed if authenticated
        return $next($request);
    }
}
