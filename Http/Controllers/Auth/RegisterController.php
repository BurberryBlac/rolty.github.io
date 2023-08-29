<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use App\Mail\EmailOtpSend;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'fname' => ['required', 'string', 'max:100'],
            'lname' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'integer', 'min:10'],
            'email' => ['required', 'string', 'email', 'max:255','unique:table_users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
      $find1 = strpos($data['email'], '@');
      $find2 = strpos($data['email'], '.');
      $is_email = ($find1 !== false && $find2 !== false && $find2 > $find1);

      $data['otp'] = rand(10000,99999);
        $user = User::create([
            'fname' => $data['fname'],
            'lname' => $data['lname'],
            'username' => $data['fname']." ".$data['lname'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'otp' => $data['otp'],
            'otp_verified'=>'0',
            'role_id'=>'4',
            'password' => Hash::make($data['password']),
        ]);

        if($is_email){
            $mail['title'] = "OTP verification";

            Mail::to($data['email'])->send(new EmailOtpSend($data));
            
            return $user;
        }

        return $user;
    }
}
