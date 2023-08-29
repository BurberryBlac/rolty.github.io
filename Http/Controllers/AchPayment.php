<?php 
namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
use DwollaSwagger;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Redirect;
use Auth;
use App\Models\User;
use Toastr;
use Session;
use App\Models\Membership;
use App\Models\MembershipSubscriber;
 
class AchPayment extends Controller {
     
    public function addAchCustomer(Request $request){

        $result['amount'] = $request->amount;
        $result['membership_id'] = $request->membership_id;
        
        return view('addAchCustomer')->with('result',$result);
    }
     
    public function processAchCustomer(Request $request){
         
         
        $rules = array(
            'achFirstName' => 'required|regex:/^[a-zA-Z\s]+$/',
            'achLastName' => 'required|regex:/^[a-zA-Z\s]+$/',
            'achEmail' => 'required|email',
        );
         
        $validatorMesssages = array(
            'achFirstName.required' => 'First name is required.',
            'achLastName.required' => 'Last name is required.',
            'achEmail.required' => 'Email address is required.',
            'achEmail.email' => 'Enter valid email address.',
        );
         
        $validator = Validator::make($request->all(), $rules, $validatorMesssages);
         
        if ($validator->fails()) {
         
            return redirect('add-ach-customer')
                ->withErrors($validator)
                ->withInput();
        } else {
            // generate Dwolla API access token.
            $this->generateAchAPIToken();
             
            $dwolla_api_env_url = 'https://api-sandbox.dwolla.com';
             
            $apiClient = new DwollaSwagger\ApiClient($dwolla_api_env_url);
            $customersApi = new DwollaSwagger\CustomersApi($apiClient);
            $customers = $customersApi->_list(1,0,null, null, null ,$request->achEmail);

             
            if( $customers->total ){
                $ach_customer_id = $customers->_embedded->{'customers'}[0]->id;
                $customers->_embedded->{'customers'}[0]->email;
            }else{
                $customer = $customersApi->create([
                  'firstName' => $request->achFirstName,
                  'lastName' => $request->achLastName,
                  'email' => $request->achEmail,
                  'ipAddress' => $_SERVER['REMOTE_ADDR']
                ]);
                 
                $customers = $customersApi->_list(1,0,null, null, null ,$request->achEmail);
                $ach_customer_id = $customers->_embedded->{'customers'}[0]->id;
                

            }
                $user_id      = auth()->user()->id;
                $user=User::find($user_id);
                $user->customer_id = $ach_customer_id;
                $user->plan_id = $request->membership_id;
                $user->amount = $request->amount;
                //print_r($user);exit;
                $user->save();
            //print_r($customers);
             
            // save returned $ach_customer_id to database for future access.
            //echo $ach_customer_id;
             
            \Session::flash('success', "Dwolla customer account added. Now verify your bank.");
            return Redirect::to('ach-verify-bank');
             
        }
    }
     
    public function verifyAchCustomerBank(){
        // generate Dwolla API access token.
        $this->generateAchAPIToken();
         
        $dwolla_api_env_url = 'https://api-sandbox.dwolla.com';
         
        $apiClient = new DwollaSwagger\ApiClient($dwolla_api_env_url);
        $customersApi = new DwollaSwagger\CustomersApi($apiClient);
        $fundingsourcesApi = new DwollaSwagger\FundingsourcesApi($apiClient);
        $user=User::find(auth()->user()->id);
        
         
        $ach_customer_id = $user->customer_id; // get saved ach_customer_id from database;
         
        if($ach_customer_id !=  ''){
            $customer_fund_source = $fundingsourcesApi->getCustomerFundingSources($ach_customer_id);
             
            if( isset( $customer_fund_source->_embedded->{'funding-sources'}[0]->id )){
                 
                $fund_sources = $customer_fund_source->_embedded->{'funding-sources'};
                return view('verifyAchBankAccount', ['fund_sources'=> $fund_sources, 'fsToken'=> '']);
                 
            } else {
             
                $fsToken = $customersApi->getCustomerIavToken($dwolla_api_env_url."/customers/".$ach_customer_id);
             
                return view('verifyAchBankAccount', ['fsToken'=> $fsToken->token]);
            }
        }else{
            \Session::flash('error', "ACH customer account is not added.");
            return Redirect::to('add-ach-customer');
        }
         
    }
     
    public function achPaymentProcess(){
        $user_id      = auth()->user()->id;
        $user=User::find($user_id);
        return view('achPaymentProcess')->with('user',$user);
    }
     
    public function achPaymentSubmit(Request $request){
         
        $rules = array(
            'paymentAmount' => 'required|integer',
        );
         
        $validatorMesssages = array(
            'paymentAmount.required' => 'Payment amount is required.',
            'paymentAmount.integer' => 'Payment amount must be a valid number.',
        );
         
        $validator = Validator::make($request->all(), $rules, $validatorMesssages);
         
        if ($validator->fails()) {
         
            return redirect('ach-payment-process')
                ->withErrors($validator)
                ->withInput();
        } else {
            // generate Dwolla API access token.
            $this->generateAchAPIToken();
             
            $user=User::find(auth()->user()->id);
            $ach_customer_id = $user->customer_id; // get saved ach_customer_id from database;
             
            $dwolla_api_env_url = config('services.dwolla.env_url');
            $dwolla_api_fund_id = config('services.dwolla.fund_id');
             
            $apiClient = new DwollaSwagger\ApiClient($dwolla_api_env_url);
            $customersApi = new DwollaSwagger\CustomersApi($apiClient);
            $fundingsourcesApi = new DwollaSwagger\FundingsourcesApi($apiClient);
            $customer_fund_source = $fundingsourcesApi->getCustomerFundingSources($ach_customer_id);
                 
            if( isset( $customer_fund_source->_embedded->{'funding-sources'}[0]->id )){
                $fund_sources = $customer_fund_source->_embedded->{'funding-sources'};
                 
                $transfer_request = array ( '_links' => array ( 'source' => 
                array ( 'href' => $dwolla_api_env_url.'/funding-sources/'.$fund_sources[0]->id, ),
                'destination' => 
                array ( 'href' => $dwolla_api_env_url.'/funding-sources/'.$dwolla_api_fund_id,
                ), ),
                'amount' => array ( 'currency' => 'USD', 'value' => $request->paymentAmount ) );
 
                $transferApi = new DwollaSwagger\TransfersApi($apiClient);
                $transferUrl = $transferApi->create($transfer_request);
                 
                if($transferUrl != ''){
                    $transferData = $transferApi->byId($transferUrl);
                     
                   /* $user->plan_id = 'Null';
                    $user->amount = 'Null';
                    $user->save();*/

                    // save $transferData->id to database and send email notification;
                 
                    /*\Session::flash('success', "Bill payment has successfully completed. Transaction ID: " .$transferData->id);
                    return Redirect::to('purchage-membership');*/
                     //print_r($request->membership_id);exit;
                    /*$membershipDetails = Membership::where('id',$request->membership_id)->first();; 
                    $membership_subscriber = MembershipSubscriber::where([['user_id','=',$user->id]])->first();        
                                    
                    
                    if(!empty($membership_subscriber) && $membership_subscriber->plan_id == $request->membership_id){

                        $checkExpired = $membership_subscriber->updated_at;

                    
                        $diffrentSecond ="";
                        if(!empty($checkExpired)){

                            $updateDate = strtotime($checkExpired);                
                            $currentDate = strtotime(now());                
                            $diffrentSecond = abs($updateDate - $currentDate);
                            
                            if($membership_subscriber->duration_type == "monthly"){
                                $secondForCompair =2592000;
                            }elseif($membership_subscriber->duration_type == "yearly"){
                                $secondForCompair =2592000*12;
                            }else{
                                $secondForCompair =86400;
                            }
                                        
                            if($diffrentSecond < $secondForCompair){
                                Toastr::error("You have already purchaged the plan",'Error');
                                return redirect()->route('membership-plan-list'); 
                            }                
                        }
                    }

                    if(empty($membership_subscriber)){
                        
                        $membership_subscriber = new MembershipSubscriber();        
                        $membership_subscriber->user_id = $user->id;              
                        $membership_subscriber->plan_id = isset($membershipDetails->id)?$membershipDetails->id:0;            
                        $membership_subscriber->duration = 1;            
                        $membership_subscriber->duration_type = "monthly";            
                        $successmsg = 'Plan successfully purchaged.';  

                    }else{                      
                        $membership_subscriber->plan_id = isset($membershipDetails->id)?$membershipDetails->id:0;             
                        $membership_subscriber->updated_at = now();   
                        $successmsg = 'Plan successfully updated.';  
                    }

                    if($membership_subscriber->save()){

                        $userDetails = User::where('id',$user->id)->first();
                        if(!empty($userDetails)){

                            $userDetails->membership_tier = $membershipDetails->tier;
                            $userDetails->membership_status = "A";
                            $userDetails->no_of_user_envited = 0;
                            $userDetails->save();
                        }
                    }
                    Toastr::success($successmsg,'Success');*/
                    $membershipDetails = Membership::where('id',$request->membership_id)->first();
                    
                    
                    $membership_subscriber = MembershipSubscriber::where([['user_id','=',$user->id]])->first();        
                                    
                    
                    if(!empty($membership_subscriber) && $membership_subscriber->plan_id == $request->membership_id){

                        $checkExpired = $membership_subscriber->updated_at;

                    
                        $diffrentSecond ="";
                        if(!empty($checkExpired)){

                            $updateDate = strtotime($checkExpired);                
                            $currentDate = strtotime(now());                
                            $diffrentSecond = abs($updateDate - $currentDate);
                            
                            if($membership_subscriber->duration_type == "monthly"){
                                $secondForCompair =2592000;
                            }elseif($membership_subscriber->duration_type == "yearly"){
                                $secondForCompair =2592000*12;
                            }else{
                                $secondForCompair =86400;
                            }
                                        
                            if($diffrentSecond < $secondForCompair){
                                Toastr::error("You have already purchaged the plan",'Error');
                                return redirect()->route('membership-plan-list'); 
                            }                
                        }
                    }

                    if(empty($membership_subscriber)){
                        
                        $membership_subscriber = new MembershipSubscriber();        
                        $membership_subscriber->user_id = $user->id;              
                        $membership_subscriber->plan_id = isset($membershipDetails->id)?$membershipDetails->id:0;            
                        $membership_subscriber->duration = 1;            
                        $membership_subscriber->duration_type = "monthly";            
                        $successmsg = 'Plan successfully purchaged.';  

                    }else{                      
                        $membership_subscriber->plan_id = isset($membershipDetails->id)?$membershipDetails->id:0;             
                        $membership_subscriber->updated_at = now();   
                        $successmsg = 'Plan successfully updated.';  
                    }

                    if($membership_subscriber->save()){
                    
                        $userDetails = User::where('id',$membership_subscriber->user_id)->first();           
                        if(!empty($userDetails)){
                                        
                            User::where('id',$membership_subscriber->user_id)->update(['membership_tier'=>$membershipDetails->tier+1,'membership_status'=>'A','no_of_user_envited'=>0]);              
                        }
                    }
                    Toastr::success($successmsg,'Success');
                    return redirect()->route('membership-plan-list');

                }else{
                    $this->payment_failed_email($user);
                    \Session::flash('error', "Bill payment has failed. Please try again.");
                    return Redirect::to('ach-payment-process');
                }
            }else{
                $this->payment_failed_email($user);
                \Session::flash('error', "Your bank account not verified.");
                return Redirect::to('ach-payment-process');
            }
        }
    }
     
    private function generateAchAPIToken(){
     
        $dwolla_api_key = 'W2FvwqXtpvMqm4uk5Ji5fCrj7rpALxJSUhHRpNCZVcISbyaeZK';
        $dwolla_api_secret = '93zEOixZQoikmNKpDDrFDXtrb6dEtg3yzugUpJeSdgQvukawkb';
        $dwolla_api_env_url = 'https://api-sandbox.dwolla.com';
         
        $basic_credentials = base64_encode($dwolla_api_key.':'.$dwolla_api_secret);
        $ch = curl_init($dwolla_api_env_url.'/token');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Basic '.$basic_credentials, 'Content-Type: application/x-www-form-urlencoded;charset=UTF-8'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = json_decode(curl_exec($ch));
         
        $token= $data->access_token;
        DwollaSwagger\Configuration::$access_token = $token;
        curl_close($ch);
    }
     
     
}