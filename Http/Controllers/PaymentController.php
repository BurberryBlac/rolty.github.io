<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Response;
use Validator;
use Auth;
use Session;
use Cache;
use Config;
use Toastr;
use Helper;
use Hash;
use DB;
use Image;
use Carbon\Carbon;
use App\Models\Property;
use App\Models\PropertyGallery;
use App\Models\PropertySpecification;
use App\Models\PropertyReview;
use App\Models\PropertyVisit;
use App\Models\PropertyFavourite;
use App\Models\Payment;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Models\Offer;
use Illuminate\Support\Str;
class PaymentController extends Controller
{
    /**********payment history *********/
    public function paymentHistory(Request $request)
    {
        $user_id=auth()->user()->id;
        $payments=Payment::where('user_id',$user_id);

         $previous_week = strtotime("-1 week +1 day");
         $start_week = strtotime("last sunday midnight",$previous_week);
         $end_week = strtotime("next saturday",$start_week);
         $start_week = date("Y-m-d",$start_week);
         $end_week = date("Y-m-d",$end_week);


         if(!empty($request->select_date) && empty($request->select_filter))
         {
          $payment_details=$payments->whereDate('created_at', '=', $request->select_date)->get();

         }
         else
         {
            $payment_details=$payments->get();
         }

        if($request->select_filter =='recently-paid' && empty($request->select_date))
        {
          $payment_details=$payments->whereBetween('created_at', [$start_week, $end_week])->get();
        }
        elseif($request->select_filter == 'last-month' && empty($request->select_date))
        {
          $payment_details=$payments->whereMonth('created_at', '=', Carbon::now()->subMonth()->month)->get();

        }
        elseif($request->select_filter =='this-year' && empty($request->select_date))
        {
             $payment_details=$payments->whereBetween('created_at', [
                    Carbon::now()->startOfYear(),
                    Carbon::now()->endOfYear(),
                ])->get();
        }
        else
        {
            $payment_details=$payments->get();
        }


        $response=[];

        foreach($payment_details as $row)
        {
            $plans=MembershipPlan::where('id',$row->plan_id)->first();

            $result['id']=$row->id;
            $result['payment_date']=date('d M, Y', strtotime($row->created_at));
            $result['payment_method']=$row->payment_type;
            $result['subscription']=$plans->tier.'-'.$plans->title;
            $result['transaction_code']=$row->txn_code;
            $result['amount']=$row->amount;
            $response[]=$result;
        }

        return view('payments.payment_history',compact('response'));
    }
    /******************paymentDetails*******/
    public function paymentDetails(Request $request)
    {
        $details=Payment::where('id',$request->id)->first();
        $property_details=Property::where('id',$details->property_id)->first();
        $gallery=PropertyGallery::where('property_id',$details->property_id)->first();

    
            $html    ='<div class="text-center">
               <img src='.asset('public/assets/img/icons/succes-tick.svg').' height="65" class="mb-3">
               <h6 class="mb-1">Amount Paid</h6>
               <h2 class="font-sb mb-0">$'.$details->amount.'</h2>
               <small>17 July, 06:09 PM</small>
            </div>
            <div class="clearfix mt-2 mb-3">
               <p class="mb-1 size-14">To</p>
               <div class="card shadow border-0 p-2">
                  <div class="d-flex align-items-center p-1">
                     <div class="rectangle-md">
                        <div class="image rounded-sm">
                           <img src='.asset('public/assets/uploads/users/'.auth()->user()->image).'>
                        </div>
                     </div>
                     <div class="size-18 my-0 font-md ml-4">'.auth()->user()->fname.' '.auth()->user()->lname.'</div>
                  </div>
               </div>
            </div>
            <div class="clearfix mt-2 mb-3">
               <p class="mb-1 size-14">Property Name</p>
               <div class="card shadow border-0 p-2">
                  <div class="d-flex align-items-center p-1">
                     <div class="rectangle-md">
                        <div class="image rounded-sm">
                           <img src='.asset('public/assets/uploads/properties/'.$gallery->file).'>
                        </div>
                     </div>
                     <div>
                        <div class="size-18 my-0 font-md ml-4">'.$property_details->name.'</div>
                        <div class="size-14 my-0 font-lt ml-4 text-light">'.$property_details->address.'</div>
                     </div>
                  </div>
               </div>
            </div>
            <div class="text-center text-black py-2">
               <div>Order ID : '.$details->order_id.'</div>
               <div class="d-flex align-items-center justify-content-center mt-1"><img src='.asset('public/assets/img/icons/apple.svg').' height="18" class="mr-3 mt-n1"> Apple Pay Ref. ID : </div>
            </div>
            <div class="d-flex align-items-center justify-content-between mt-3 mb-2 pb-1">
               <a href="" class="btn btn-primary-border border-1 size-14 col-5 ml-3">
               <img src='.asset('public/assets/img/icons/download.svg').' height="14" class="mt-n1 mr-2 icon-black"> Download Invoice
               </a>
               <div class="dropdown col-5 px-0">
                  <a href="" class="btn btn-primary-border border-1 size-14 mr-3 d-block" data-toggle="dropdown">
                  <img src='.asset('public/assets/img/icons/help.svg').' height="16" class="mt-n1 mr-2 icon-black"> 24x7 Help
                  </a>
                  <div class="dropdown-menu dropdown-menu-right default-dropdown shadow">
                     <div class="d-flex justify-content-between px-3 py-1">
                        <div class="text-left lh-20 mr-4 pr-2" style="line-height: 14px;">
                           <div class="size-12 mb-0 text-nowrap">Customer Helpline Number</div>
                           <small class="text-light size-10">1800 1234 1234</small>
                        </div>
                        <a href="tel:180012341234" class="ml-auto text-primary"><i class="fa fa-phone" aria-hidden="true"></i></a>
                     </div>
                     <div class="d-flex justify-content-between px-3 py-1 mt-2">
                        <div class="text-left lh-20" style="line-height: 14px;">
                           <h6 class="size-12 mb-0 text-nowrap">Customer Helpline Email</h6>
                           <small class="text-light size-10">info@realrolty.com</small>
                        </div>
                        <div class="ml-auto text-primary size-14 pt-1" role="button"><i class="fa fa-clone " aria-hidden="true"></i></div>
                     </div>
                  </div>
               </div>
            </div>';

         return Response::json(['response'=>$html,'status' => 200,'message' => 'List of users  fetch  Successfully!.']);
    }


}
