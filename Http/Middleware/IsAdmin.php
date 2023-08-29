<?php

namespace App\Http\Middleware;

use Closure;
use Auth;

class IsAdmin
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
        if(auth::check() && auth()->user()->role_id == 1){
            $response=$next($request);
            $response->headers->set('Cache-Control','nocache,no-store,must-revalidate');
            return $response;
        }
   
        return redirect('home')->with('error',"You don't have admin access.");
    }
}
