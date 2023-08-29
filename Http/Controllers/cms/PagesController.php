<?php

namespace App\Http\Controllers\cms;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Carbon\Carbon;
use DB;
use Mail;
use Toastr;
use Session;

class PagesController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Pages Controller
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


    public function about()
    {
        return view('pages.about');
    }

    public function privacy()
    {
        return view('pages.privacy');
    }

    public function terms()
    {
        return view('pages.terms');
    }

    public function contactUs(){
        return view('pages.contact_us');
    }


    public function newsletter(Request $request)
    {
   
        $this->validate($request, [
            'email' => 'required|email',
        ]);
        
        $ip = $request->ip();
        $address = $this->getAddress($ip);
        
        $email = strip_tags(trim($request->email));
                     
        $checkInfo = DB::table('table_newsletter')->where('email', $email)->first();
        
        if(empty($checkInfo)){
        
        	$insert_enquiry = DB::table('table_newsletter')->insert(['email'=>$email,'ip_address'=>$ip,'created_at'=>Carbon::now()->format('Y-m-d H:i:s'),]);
               if($insert_enquiry){
			$to_email="customerservice@realrolty.com";
			$data = ['email'=>$email,'address'=>$address];
			$record = Mail::send('email.promotion',compact('data'), function($message) use ($to_email){
				$message->to($to_email);
				$message->from('developer@quytech.com');
				$message->subject('New Subscriber');
			});
		        Toastr::Success('Successfully Subscribed.', 'Success', ['timeOut' => 3000]);
        		return redirect('/')->with('message', 'Successfully Subscribed.');
		}else{
		 	Toastr::Error('Something Wrong!', 'Error', ['timeOut' => 3000]);
        		return redirect('/');
		}
        }else{
		Toastr::Error('Already Subscribed!', 'Error', ['timeOut' => 3000]);
        	return redirect('/')->with('message', 'Already Subscribed.');
        
        }
    }

    
    public function coming()
    {
        return view('coming');
    }
    
    function getAddress($ip) {
        $details = json_decode($this->curl_get_contents("https://ipinfo.io/".$ip."/json"));

        if(isset($details->city) && isset($details->region) && isset($details->country) && isset($details->postal))
        {
            return $details->city . ', ' . $details->region . ', ' . $details->country . ', ' . $details->postal;    
        }
    }

   function curl_get_contents($url)
   {
    $ch = curl_init($url);  
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
  }
}
