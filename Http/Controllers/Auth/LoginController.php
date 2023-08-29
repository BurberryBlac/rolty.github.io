<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Laravel\Socialite\Facades\Socialite;
use View;
use Session;
use Toastr;
use App\Models\User;
use Location;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */
  
    use AuthenticatesUsers;
  
    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';
   
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
   
    public function login(Request $request)
    {
        if($request->isMethod('POST'))
        {
            $userIp = $request->ip();
            $result = $this->IPtoLocation($userIp);
            $input = $request->all();
            
            $this->validate($request, [
                'email' => 'required|email',
                'password' => 'required',
            ]);
            if(!is_null($request->role) && $request->role == 'agent'){
                $role = '2';
            }elseif(!is_null($request->role) && $request->role == 'seller'){
                $role = '3';
            }elseif(!is_null($request->role) && $request->role == 'buyer'){
                $role = '4';
            }else{
                $role = '1';
            }
          
            if(auth()->attempt(array('email' => $input['email'], 'password' => $input['password'], 'role_id' => $role)))
            {
                if (auth()->user()->role_id == 1)
                {
                    Toastr::success('You have successfully login to Real Rolty!','Login Successful');
                    return redirect()->route('admin.home');
                }
                elseif(auth()->user()->role_id == 2)
                {
                    Toastr::success('You have successfully login to Real Rolty!','Login Successful');
                    return redirect()->route('home');
                }
                elseif(auth()->user()->role_id == 3)
                {
                    Toastr::success('You have successfully login to Real Rolty!','Login Successful');
                    return redirect()->route('home');
                }
                else
                {
                    Toastr::success('You have successfully login to Real Rolty!','Login Successful');
                    return redirect()->route('home');
                }
            }else{
                Toastr::error('Invalid Credentials.','Error');
                return redirect('login?role='.$request->role)
                    ->withErrors(['invalid_login'=>'Email-Address or Password Are Wrong.']);
            }
        }else{
            if(!is_null($request->role) && $request->role == 'agent'){
                $register_url = "/signup?role=agent";
                $check_role = $request->session()->get('set_role');
                if($check_role){
                    $request->session()->forget('set_role');
                    $request->session()->put('set_role', 'agent');
                }else{
                    $request->session()->put('set_role', 'agent');
                }
                return View::make('auth.login')->with(compact('register_url'));
            }elseif(!is_null($request->role) && $request->role == 'seller'){
                $register_url = "/signup?role=seller";
                $check_role = $request->session()->get('set_role');
                if($check_role){
                    $request->session()->forget('set_role');
                    $request->session()->put('set_role', 'seller');
                }else{
                    $request->session()->put('set_role', 'seller');
                }
                return View::make('auth.login')->with(compact('register_url'));
            }elseif(!is_null($request->role) && $request->role == 'buyer'){
                $register_url = "/signup?role=buyer";
                $check_role = $request->session()->get('set_role');
                if($check_role){
                    $request->session()->forget('set_role');
                    $request->session()->put('set_role', 'buyer');
                }else{
                    $request->session()->put('set_role', 'buyer');
                }
                return View::make('auth.login')->with(compact('register_url'));
            }else{
                $buyer_url = 'login?role=buyer';
                $seller_url = 'login?role=seller';
                $agent_url = 'login?role=agent';
                return View::make('auth.roles')->with(compact('buyer_url','seller_url','agent_url'));
            }
        }
          
    }

    public function logout() {
        Auth::logout();
        Session::flush();
        Toastr::success('Logout Successful','Success');
        return redirect('/');
    }

    // Google Login
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    // Google Callback
    public function handleGoogleCallback()
    {
        $user = Socialite::driver('google')->stateless()->user();

        $this->_signupOrLoginUser($user);

        // Return home after SignIn
        return redirect()->route('home');
    }

    // Google Login
    public function redirectToFacebook()
    {
        return Socialite::driver('facebook')->redirect();
    }

    // Google Callback
    public function handleFacebookCallback()
    {
        $user = Socialite::driver('facebook')->user();

        $this->_signupOrLoginUser($user);

        // Return home after SignIn
        return redirect()->route('home');
    }

    protected function _signupOrLoginUser($data)
    {
        $user = $data;

        $role_value = Session::get('set_role');
        if($role_value){
            if($role_value == 'agent'){
                $role = '2';
            }elseif($role_value == 'seller'){
                $role = '3';
            }elseif($role_value == 'buyer'){
                $role = '4';
            }
        }else{
            $role = '4';
        }
    
        $finduser = User::where('google_id', $user['id'])->where('role_id', $role)->first();
        if($finduser){
    
            Auth::loginUsingId($finduser['id']);
    
        }else{
            $exp_name = explode(" ", $user['name']);
            if(count($exp_name) > 1){
                $f_name = $exp_name[0];
                $l_name = $exp_name[1];
            }else{
                $f_name = $exp_name[0];
                $l_name = $exp_name[0];
            }
            
            $newUser = User::create([
                'fname' => $f_name,
                'lname' => $l_name,
                'username' => $user['name'],
                'email' => $user['email'],
                'google_id'=> $user['id'],
                'otp_verified'=>1,
                'role_id'=>$role,
                'password' => encrypt('12345678')
            ]);
    
            Auth::loginUsingId($newUser['id']);
        }
    }
    
    public function IPtoLocation($ip)
    {
        $apiURL = 'https://freegeoip.app/json/'.$ip; 
        
        // Make HTTP GET request using cURL 
        $ch = curl_init($apiURL); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        $apiResponse = curl_exec($ch); 
        if($apiResponse === FALSE) { 
            $msg = curl_error($ch); 
            curl_close($ch); 
            return false; 
        } 
        curl_close($ch); 
        
        // Retrieve IP data from API response 
        $ipData = json_decode($apiResponse, true); 
        
        // Return geolocation data 
        return !empty($ipData)?$ipData:false; 
    }
    
    private function findNearestProperties($latitude, $longitude, $radius = 400)
    {
        /*
         * using eloquent approach, make sure to replace the "Restaurant" with your actual model name
         * replace 6371000 with 6371 for kilometer and 3956 for miles
         */
        $restaurants = Property::selectRaw("id, name, address, lat, long, rating, zone ,
                         ( 6371000 * acos( cos( radians(?) ) *
                           cos( radians( latitude ) )
                           * cos( radians( longitude ) - radians(?)
                           ) + sin( radians(?) ) *
                           sin( radians( latitude ) ) )
                         ) AS distance", [$latitude, $longitude, $latitude])
            ->where('active', '=', 1)
            ->having("distance", "<", $radius)
            ->orderBy("distance",'asc')
            ->offset(0)
            ->limit(20)
            ->get();

        return $restaurants;
    }
}
