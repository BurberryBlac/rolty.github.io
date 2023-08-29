<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;
use Twilio\Rest\Client;
use Session;
use View;
use App\Models\User;

class TwilioSMSController extends Controller
{
    //
    public function index(Request $request)
    {
        $receiverNumber = $request->session()->get('set_phone');
        $role = $request->session()->get('set_role');
        if(isset($request->value)){
            if($role == 'agent')
                $role = '1';
                elseif($role == 'agent')
                    $role = '2';
                    elseif($role == 'seller')
                        $role = '3';
                    else
                        $role = '4';
            $data['otp'] = rand(10000,99999);
            $finduser = User::where('phone', $receiverNumber)->where('role_id', $role)->first();
            User::where('id',$finduser['id'])->update(['otp'=>$data['otp']]);
            $request->session()->put('otp', $data['otp']);
        }
        $role = $request->session()->get('set_role');
        $receiverNumber = $request->session()->get('set_phone');
        $receiverNumber = "+1".$receiverNumber;
        $message = "Please use the OTP for further use. OTP : ".$request->session()->get('otp');
  
        try {
  
            $account_sid = "AC429bf4de99a9f2c5d54dcacd6458753f";
            $auth_token = "cbb7b81139d067a5898527db9864235e";
            $twilio_number = "+17162715292";
  
            $client = new Client($account_sid, $auth_token);
            $client->messages->create($receiverNumber, [
                'from' => $twilio_number, 
                'body' => $message]);
            

            return View::make('auth.otp_verification')->with('phone_no',$receiverNumber)->with('role',$role);
  
        } catch (Exception $e) {
            dd("Error: ". $e->getMessage());
        }
    }
}
