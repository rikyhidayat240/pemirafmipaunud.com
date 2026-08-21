<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!$request->user()) {
            return redirect()->route('login');
        }

        // Check if user is admin
        if (!$request->user()->is_admin) {
            return redirect()->route('dashboard')
                ->with('alert', ['type' => 'error', 'title' => 'Akses Ditolak', 'message' => 'Anda tidak memiliki akses ke halaman ini.']);
        }
        return $next($request);
    }
}
