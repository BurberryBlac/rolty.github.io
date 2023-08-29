<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use GeneaLabs\LaravelSocialiter\Facades\Socialiter;
use Laravel\Socialite\Facades\Socialite;

class AppleSigninController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function login()
    {
        return Socialite::driver("sign-in-with-apple")
            ->scopes(["name", "email"])
            ->redirect();
    }

    public function callback(Request $request)
    {
        // get abstract user object, not persisted
        $user = Socialite::driver("sign-in-with-apple")->user();
        
        // or use Socialiter to automatically manage user resolution and persistence
        $user = Socialiter::driver("sign-in-with-apple")->login();
    }
}