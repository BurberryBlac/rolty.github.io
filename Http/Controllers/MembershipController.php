<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Toastr;
use Session;
use App\Models\User;
use App\Models\Membership;
use App\Models\MembershipSubscriber;

class MembershipController extends Controller
{

    public function membership_plan_list(Request $request)
    {
     
        $plan_list = Membership::get();                 
      
        return view('membership.plan_list',compact('plan_list'));
    }
 
    public function purchage_membership(Request $request)
    {
        
      
        $user = auth::user();
        
          
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
    }      
    
}
