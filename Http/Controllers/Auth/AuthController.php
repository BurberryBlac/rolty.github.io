<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use App\Providers\RouteServiceProvider;
use Hash;
use View;
use Session;
use Auth;
use Toastr;
use Illuminate\Support\Facades\Mail;
use App\Mail\EmailOtpSend;
use Carbon\Carbon;

class AuthController extends Controller
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
    protected $redirectTo = RouteServiceProvider::HOME;
   
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    protected function customer_validator(array $data)
    {
        return Validator::make($data, [
            'fname' => ['required', 'string', 'max:100'],
            'lname' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'integer', 'min:10','unique:table_users'],
            'email' => ['required', 'string', 'email', 'max:255','unique:table_users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }

    protected function agent_validator(array $data)
    {
        return Validator::make($data, [
            'fname' => ['required', 'string', 'max:100'],
            'lname' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'integer', 'min:10','unique:table_users'],
            'email' => ['required', 'string', 'email', 'max:255','unique:table_users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'license' => ['required', 'string', 'max:35'],
            'office_name' => ['required', 'string', 'max:100'],
            'office_address' => ['required', 'string', 'max:255'],
        ]);
    }

    protected function validateEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);
    }
   
    public function signup(Request $request)
    {
        if($request->isMethod('POST'))
        {
            $data = $request->all();

            if(!is_null($request->role) && $request->role == 'agent'){
                $role = '2';
                $request->validate([
                    'fname' => ['required', 'string', 'max:100'],
                    'lname' => ['required', 'string', 'max:100'],
                    'phone' => ['required', 'integer', 'min:10','unique:table_users'],
                    'email' => ['required', 'string', 'email', 'max:255','unique:table_users'],
                    'password' => ['required', 'string', 'min:8', 'confirmed'],
                    'license' => ['required', 'string', 'max:35'],
                    'office_name' => ['required', 'string', 'max:100'],
                    'office_address' => ['required', 'string', 'max:255'],
                ]);
                $save_user =$this->_signupUser($data,$role);
                $check_mail = $request->session()->get('set_mail');
                $check_phone = $request->session()->get('set_phone');
                if($check_mail){
                    $request->session()->forget('set_mail');
                    $request->session()->put('set_mail', $data['email']);
                }else{
                    $request->session()->put('set_mail', $data['email']);
                }
                if($check_phone){
                    $request->session()->forget('set_phone');
                    $request->session()->put('set_phone', $data['phone']);
                }else{
                    $request->session()->put('set_phone', $data['phone']);
                }
                if($save_user == 'false'){
                    Toastr::error('This mail address/phone number are already used. Please use a different mail address/phone number','Error');
                    return redirect()->route('signup?role=agent');
                }else{
                    Toastr::success('Please select a otp destination for complete your registration','Success');
                    return redirect('authorization?role=agent');
                }
            }elseif(!is_null($request->role) && $request->role == 'seller'){
                $role = '3';
                $request->validate([
                    'fname' => ['required', 'string', 'max:100'],
                    'lname' => ['required', 'string', 'max:100'],
                    'phone' => ['required', 'integer', 'min:10','unique:table_users'],
                    'email' => ['required', 'string', 'email', 'max:255','unique:table_users'],
                    'password' => ['required', 'string', 'min:8', 'confirmed'],
                ]); 
                $save_user =$this->_signupUser($data,$role);
                $check_mail = $request->session()->get('set_mail');
                $check_phone = $request->session()->get('set_phone');
                if($check_mail){
                    $request->session()->forget('set_mail');
                    $request->session()->put('set_mail', $data['email']);
                }else{
                    $request->session()->put('set_mail', $data['email']);
                }
                if($check_phone){
                    $request->session()->forget('set_phone');
                    $request->session()->put('set_phone', $data['phone']);
                }else{
                    $request->session()->put('set_phone', $data['phone']);
                }
                if($save_user == 'false'){
                    Toastr::error('This mail address/phone number are already used. Please use a different mail address/phone number','Error');
                    return redirect()->route('signup?role=seller');
                }else{
                    Toastr::success('Please select a otp destination for complete your registration','Success');
                    return redirect('authorization?role=seller');
                }
            }elseif(!is_null($request->role) && $request->role == 'admin'){
                //
            }else{
                $role = '4';
                $request->validate([
                    'fname' => ['required', 'string', 'max:100'],
                    'lname' => ['required', 'string', 'max:100'],
                    'phone' => ['required', 'integer', 'min:10','unique:table_users'],
                    'email' => ['required', 'string', 'email', 'max:255','unique:table_users'],
                    'password' => ['required', 'string', 'min:8', 'confirmed'],
                ]);  
                $save_user =$this->_signupUser($data,$role);
                $check_mail = $request->session()->get('set_mail');
                $check_phone = $request->session()->get('set_phone');
                if($check_mail){
                    $request->session()->forget('set_mail');
                    $request->session()->put('set_mail', $data['email']);
                }else{
                    $request->session()->put('set_mail', $data['email']);
                }
                if($check_phone){
                    $request->session()->forget('set_phone');
                    $request->session()->put('set_phone', $data['phone']);
                }else{
                    $request->session()->put('set_phone', $data['phone']);
                }
                if($save_user == 'false'){
                    Toastr::error('This mail address/phone number are already used. Please use a different mail address/phone number','Error');
                    return redirect()->route('signup?role=buyer');
                }else{
                    Toastr::success('Please select a otp destination for complete your registration','Success');
                    return redirect('authorization?role=buyer');
                }
            }
        }else{
            if(!is_null($request->role) && $request->role == 'agent'){
                $login_url = "/login?role=agent";
                $check_role = $request->session()->get('set_role');
                if($check_role){
                    $request->session()->forget('set_role');
                    $request->session()->put('set_role', 'agent');
                }else{
                    $request->session()->put('set_role', 'agent');
                }
                return View::make('agent.signup')->with(compact('login_url'));
            }elseif(!is_null($request->role) && $request->role == 'seller'){
                $login_url = "/login?role=seller";
                $check_role = $request->session()->get('set_role');
                if($check_role){
                    $request->session()->forget('set_role');
                    $request->session()->put('set_role', 'seller');
                }else{
                    $request->session()->put('set_role', 'seller');
                }
                return View::make('seller.signup')->with(compact('login_url'));
            }elseif(!is_null($request->role) && $request->role == 'buyer'){
                $login_url = "/login?role=buyer";
                $check_role = $request->session()->get('set_role');
                if($check_role){
                    $request->session()->forget('set_role');
                    $request->session()->put('set_role', 'buyer');
                }else{
                    $request->session()->put('set_role', 'buyer');
                }
                return View::make('buyer.signup')->with(compact('login_url'));
            }else{
                $buyer_url = 'signup?role=buyer';
                $seller_url = 'signup?role=seller';
                $agent_url = 'signup?role=agent';
                return View::make('auth.roles')->with(compact('buyer_url','seller_url','agent_url'));
            }

        }
          
    }

    public function select_otp_destination(Request $request)
    {
        $data = $request->all();
        
        if($request->role){
            $check_role = $request->role;
            if($check_role == 'agent')
            $role = '1';
            elseif($check_role == 'agent')
            $role = '2';
            elseif($check_role == 'seller')
            $role = '3';
            else
            $role = '4';
        }
        if($request->isMethod('post')){
            $check_mail = $request->session()->get('set_mail');
            $check_phone = $request->session()->get('set_phone');
            $otp = implode('', $data['otp']);
            if($check_mail){
                $otp_check = User::where('email', $check_mail)->where('role_id', $role)->where('otp', $otp)->first();
            }
            if($otp_check){
                User::where('id',$otp_check['id'])->update(['otp'=>null,'otp_verified'=>1]);
                $login = Auth::loginUsingId($otp_check['id']);
                Toastr::success('You have successfully login to Real Rolty!','Login Successful');
                return redirect()->route('home');
            }else{
                Toastr::error('Invalid Otp, Please enter correct otp code.','Otp Incorrect!');
                return redirect('authorization?auth_option=mail&role='.$check_role)->withErrors(['invalid_otp'=>'Invalid Otp, Please enter correct otp code.']);
            }
            
        }else{

            if($request->auth_option == "mail"){

                $check_mail = $request->session()->get('set_mail');
                $data['otp'] = rand(10000,99999);
                $find1 = strpos($check_mail, '@');
                $find2 = strpos($check_mail, '.');
                $is_email = ($find1 !== false && $find2 !== false && $find2 > $find1);
                $finduser = User::where('email', $check_mail)->where('role_id', $role)->first();
                $data['email'] = $check_mail;
                $data['name'] = $finduser['username'];
                if($is_email){
                    User::where('id',$finduser['id'])->update(['otp'=>$data['otp']]);
                    $mail['title'] = "OTP verification";

                    Mail::to($data['email'])->send(new EmailOtpSend($data));

                    return View::make('auth.otp_verification')->with(compact('check_mail'));
                }else{
                    return redirect('authorization');
                }

            }elseif($request->auth_option == "sms"){
                //
                $check_phone = $request->session()->get('set_phone');
                $data['otp'] = rand(10000,99999);
                if($check_phone){
                    $finduser = User::where('phone', $check_phone)->where('role_id', $role)->first();
                    User::where('id',$finduser['id'])->update(['otp'=>$data['otp']]);
                    $request->session()->put('otp', $data['otp']);
                    return redirect()->route('sendSMS');
                }else{
                    return redirect('authorization');
                }
            }else{
                $role = $request->role;
                $mail_url = "authorization?auth_option=mail&role=".$role;
                $sms_url = "authorization?auth_option=sms&role=".$role;
                return View::make('auth.otp_destination')->with(compact('mail_url','sms_url'));
            }
        }
    }

    public function forgot_password(Request $request)
    {
        if($request->role){
            $check_role = $request->role;
            if($check_role == 'agent')
            $role = '1';
            elseif($check_role == 'agent')
            $role = '2';
            elseif($check_role == 'seller')
            $role = '3';
            else
            $role = '4';

            if($request->isMethod("POST")){

                $this->validateEmail($request);

                $input = $request->all();
                $finduser = User::where('email', $input['email'])->where('role_id', $role)->first();
                if($finduser){
                    $digits = 4;
                    $otp = rand(pow(10, $digits-1), pow(10, $digits)-1);
                    $finduser->otp = $otp;
                    $finduser->otp_verified = 0;
                    $finduser->save();
         
                    $to_email=$finduser->email;
                    $data = ['otp'=>$otp,'user_id'=>$finduser->id,'user_name'=>$finduser->username, 'email'=>$finduser->email];
                    Mail::send('email.forgot_mail',compact('data'), function($message) use ($to_email){
                        $message->to($to_email);
                        $message->from('developer@quytech.com', 'Link');
                        $message->subject('Forgot Password Link');
                    });
                    Toastr::success('We have e-mailed your password reset link!','Success');
                    return redirect('check-forgot-password?role='.$check_role);
                }else{
                    return View::make('auth.forgot_password')->withErrors(['email'=>'An account could not be found for the provided email.']);
                }
            }else{
                return View::make('auth.forgot_password');
            }
        }else{
            redirect('login');
        }
    }

    public function change_password(Request $request, $id, $token)
    {
        $user_id = $id;
        $token = $token;  
        if($request->isMethod("POST")){
            
            $newPassword = strip_tags(trim($request->password));
        
            $confirm_password = strip_tags(trim($request->confirm_password));
            
            $request->validate([
                'password' => 'required',
                'confirm_password' => 'required',
            ]);
            
        
            if($newPassword!=$confirm_password){
                Toastr::error('Password do not match!', 'error', ['timeOut' => 5000]);
                return redirect()->back()->withInput();
            }

            $user_id = strip_tags(trim($user_id));
        
            $otp = strip_tags(trim(base64_decode($token))); 
            
            $user = User::where('id',$user_id)->where('otp',$otp)->first();
            
            if($user){
                $user->otp_verified = 1;
                $user->password = Hash::make($newPassword);
                $user->email_verified_at = Carbon::now();
                $user->save();
                toastr()->Success('Your Password has been changed');
                return redirect('/login'); 
            }
            else
            {
                toastr()->Warning('Some thing went wrong');
                return view('auth.set_password', compact('user_id','token'));
            }
        }else{
            return view('auth.set_password', compact('user_id','token'));
        }
    }

    public function check_mail(Request $request)
    {
        $role = $request->role;
        if($role){
            return view('auth.check_mail', compact('role'));
        }else{
            return redirect()->route('login');
        }
    }

    protected function _signupUser($data,$role)
    {
    
        $finduser = User::where('email', $data['email'])->where('role_id', $role)->first();
        if($finduser){
            return false;
        }elseif($role == '2'){
    
            $newUser = User::create([
                'fname' => $data['fname'],
                'lname' => $data['lname'],
                'username' => $data['fname']." ".$data['lname'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'license' => $data['license'],
                'office_name' => $data['office_name'],
                'office_address' => $data['office_address'],
                'otp_verified'=>'0',
                'role_id'=>$role,
                'password' => Hash::make($data['password']),
            ]);

            return $newUser;
    
        }else{
            
            $newUser = User::create([
                'fname' => $data['fname'],
                'lname' => $data['lname'],
                'username' => $data['fname']." ".$data['lname'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'otp_verified'=>'0',
                'role_id'=>$role,
                'password' => Hash::make($data['password']),
            ]);

            return $newUser;
        }
    }
}
