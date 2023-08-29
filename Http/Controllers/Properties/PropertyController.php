<?php

namespace App\Http\Controllers\Properties;
use App\Http\Controllers\Controller;
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
use App\Models\Property;
use App\Models\PropertyGallery;
use App\Models\PropertySpecification;
use App\Models\PropertyReview;
use App\Models\PropertyVisit;
use App\Models\PropertyFavourite;
use App\Models\Payment;
use App\Models\User;
use App\Models\Offer;
use App\Models\PropertyBlockUser;
use App\Models\NotificationOff;
use App\Models\Notifications;
use App\Models\Follower;
use Illuminate\Support\Str;

class PropertyController extends Controller
{
    /*****Add Property ****************/
    public function addProperty(Request $request)
    {
        $membershipTier = isset(Auth::user()->membership_tier)?Auth::user()->membership_tier:0; 
        if($membershipTier == 0 || $membershipTier == 1){
            return redirect()->back()->withInput(); 
        }
        if($request->isMethod('post'))
        {
   
            $validator = Validator::make($request->all(),[
                'property_image'=>'required|array|max:2048',
                'property_name'=>'required|min:2|max:20',
                'property_location'=>'required|string',
                'property_price'=>'required|integer|between:1,10000000',
                'ptype'=>'required',
                'description'=>'required',
                'bedroom'=>'required',
                'bathroom'=>'required',
                'kitchen'=>'required',
                'area'=>'required',
                'pets'=>'required',
                'trending'=>'required',
                'balcony'=>'required',
                'parking'=>'required',
                'family_type'=>'required',
                'seller_roles'=>'required',


            ]);


            if ($validator->fails())
            { 
                $messages = $validator->messages();
                foreach ($messages->all() as $message)
                {
                    Toastr::error($message, 'Failed', ['timeOut' => 5000]);
                }
                return redirect()->back()->withErrors($validator)->withInput(); 
                                                  
            }

            $property = new Property;
            $property->user_id= auth()->user()->id;

            $property->name= trim($request->property_name);
            $property->price= trim($request->property_price);
            $property->address=trim($request->property_location);
            $property->lat=trim($request->lat);
            $property->long=trim($request->long);
            $property->desc=trim($request->description);
            $property->specId='1';
            $property->ptype=trim($request->ptype);
            $property->trending=$request->trending;
            $property->book_status='unsold';
            $property->status='A';
            $property->family_type=trim($request->family_type);
            $property->seller_roles=trim($request->seller_roles);
            $property->slug=uniqid();



            $property->save();
            $property_id = $property->id;

            $prescription               = new PropertySpecification;
            $prescription->property_id  =$property_id;
            $prescription->bedroom      = trim($request->bedroom);
            $prescription->bathroom      = trim($request->bathroom);

            $prescription->kitchen      = trim($request->kitchen);
            $prescription->area         = trim($request->area);
            $prescription->pets         = trim($request->pets);
            $prescription->balcony      = trim($request->balcony);
            $prescription->parking      = trim($request->parking);


            $prescription->status       = 'A';
            $prescription->save();


            $property_images = $request->property_image;  
            foreach ($property_images as  $row) 
            {
                $image                               = new PropertyGallery;
                $image->property_id                  = $property_id;
                if($row->isValid())
                {
                    $extension =$row->getClientOriginalExtension();
                    $filename =rand(111,99999).'.'.$extension;
                    $image_path = 'public/assets/uploads/properties/'.$filename;
                    //store images in images folder
                    Image::make($row)->save($image_path);                          
                    $image->file=$filename;
                    $image->thumb_file  =$filename;
                    $image->file_type='image';
                    $image->status='A';
                                   
                }

            
                $image->save();
            }

            /*Send Notification*/
            $noti['title'] = "New property listed";
            $noti['body'] = auth()->user()->name." uploaded new property listing.";
            $to_user_id = User::whereNotNull('id')->where(['role_id' => 4])->pluck('id')->all();
            $followers = Follower::where(['type' => '1', 'status' => 'follow', 'follow_id' => auth()->user()->id])->pluck('user_id')->all();
            $to_user_id = array_values(array_unique(array_merge($followers, $to_user_id)));
            if (sizeof($to_user_id) > 0) {
                foreach ($to_user_id as $key => $uid) {
                    $types = NotificationOff::where(['user_id' => $uid])->pluck('type')->all();
                    if (sizeof($types) > 0) {
                        if (!in_array('new_property', $types)) {
                            $firebaseToken = User::whereNotNull('fcm_token')->where(['id' => $uid])->pluck('fcm_token')->all();
                            $this->sendNotification($noti, $firebaseToken, [$uid], 'new_property', ['property_id' => $property_id]);
                        }
                    }else{
                        $firebaseToken = User::whereNotNull('fcm_token')->where(['id' => $uid])->pluck('fcm_token')->all();
                        $this->sendNotification($noti, $firebaseToken, [$uid], 'new_property', ['property_id' => $property_id]);
                    }
                }
            }
            /*Send Notification*/

            Toastr::success('Property added Successfull', 'Success', ['timeOut' => 5000]);
            return redirect('my-properties');
        }
        return view('properties.add_property');
    }

    public function sendNotification($noti, $firebaseToken, $to_user_id, $type, $payload = array())
    {
        $response = array();

        if (!empty($firebaseToken) && is_array($firebaseToken)) {
            $SERVER_API_KEY = env('FCM_SERVER_KEY');

            $data = [
                "registration_ids" => $firebaseToken,
                "notification" => [
                    "title" => $noti['title'],
                    "body" => $noti['body'],
                ]
            ];
            $dataString = json_encode($data);

            $headers = [
                'Authorization: key=' . $SERVER_API_KEY,
                'Content-Type: application/json',
            ];

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);

            $response = curl_exec($ch);

            foreach ($to_user_id as $key => $uid) {
                $notification = new Notifications;

                $notification->from_user_id = auth()->user()->id;
                $notification->to_user_id = $uid;
                $notification->type = $type;
                $notification->payload = json_encode($payload);
                $notification->title = $noti['title'];
                $notification->body = $noti['body'];
                $notification->status = 'A';

                $notification->save();
            }
            return $response;
        }

    }

    /********Edit Property***************/
    public function editProperty(Request $request,$id)
    {
        if($request->isMethod('post'))
        {
            $validator = Validator::make($request->all(),[
                'property_image'=>'sometimes|nullable|array|max:2048',
                'property_name'=>'required|min:2|max:20',
                'property_location'=>'required|string',
                'property_price'=>'required|numeric|between:1,10000000',
                'ptype'=>'required',
                'description'=>'required',
                'bedroom'=>'required',
                'bathroom'=>'required',
                'kitchen'=>'required',
                'area'=>'required',
                'pets'=>'required',
                'trending'=>'required',
                'balcony'=>'required',
                'parking'=>'required',
                'family_type'=>'required',
                'seller_roles'=>'required',


       
            ]);


            if ($validator->fails())
            { 
                $messages = $validator->messages();
                foreach ($messages->all() as $message)
                {
                    Toastr::error($message, 'Failed', ['timeOut' => 5000]);
                }
                return redirect()->back()->withErrors($validator)->withInput(); 
                                                  
            }


            $slug_details= Property::where('slug',$id)->first();
            $property =  Property::find($slug_details->id);

            $property->name= trim($request->property_name);
            $property->price= trim($request->property_price);
            $property->address=trim($request->property_location);
            $property->lat=trim($request->lat);
            $property->long=trim($request->long);
            $property->desc=trim($request->description);
            $property->specId='1';
            $property->ptype=trim($request->ptype);
            $property->trending=trim($request->trending);
            $property->family_type=trim($request->family_type);
            $property->seller_roles=trim($request->seller_roles);

            $property->book_status='unsold';
            $property->status='A';
            $property->save();

           PropertySpecification::where('property_id',$slug_details->id)->update([
            'bedroom'=> trim($request->bedroom),
            'bathroom'=> trim($request->bathroom),

            'kitchen'=> trim($request->kitchen),
            'area'   => trim($request->area),
            'pets'   =>trim($request->pets),
            'balcony'   =>trim($request->balcony),
            'parking'   =>trim($request->parking)


            ]);


            $property_images = $request->property_image; 
            if(!empty($property_images))
            {
                foreach ($property_images as  $row) 
                {
                    $image                               = new PropertyGallery;
                    $image->property_id                  = $slug_details->id;
                    if($row->isValid())
                    {
                        $extension =$row->getClientOriginalExtension();
                        $filename =rand(111,99999).'.'.$extension;
                        $image_path = 'public/assets/uploads/properties/'.$filename;
                        //store images in images folder
                        Image::make($row)->save($image_path);                          
                        $image->file=$filename;
                        $image->thumb_file  =$filename;
                        $image->file_type='image';
                        $image->status='A';
                                       
                    }

                
                    $image->save();
                }
            }

            Toastr::success('Property updated Successfull', 'Success', ['timeOut' => 5000]);
            return redirect('my-properties');

        }
         $details = $this->propertyDetails($id);
        return view('properties.edit_property',compact('details'));
    }
    /***************view Property**********/
    public function viewProperty(Request $request,$id)
    {
        $details = $this->propertyDetails($id);

        if((auth()->user()->role_id == 4) || empty(auth()->user()))
        {
           return view('properties.buyer_view_property',compact('details'));


        }
        else
        {
            return view('properties.view_property',compact('details'));

        }
    }

    /***************property details *********/

    public function propertyDetails($id)
    {

        $data['property']=Property::where('slug',$id)->first();
        if($data['property']->user_id == Auth::user()->id){
        $data['property_access']= "yes";
        }else{
        $data['property_access']= "no";
        }
        $number_array=[1,2,3,4,5];
        $property_review_array=[];
        $location_review_array=[];
        $clean_review_array=[];
        $money_review_array=[];

        foreach($number_array as $row)
        {
             $property_review = PropertyReview::where(['property_id'=>$data['property']->id,'rating'=>$row]);

             $location_review =  PropertyReview::where(['property_id'=>$data['property']->id,'rating'=>$row])->where('rating_type','1');
                        
             $clean_review =  PropertyReview::where(['property_id'=>$data['property']->id,'rating'=>$row])->where('rating_type','2');
             $money_review =  PropertyReview::where(['property_id'=>$data['property']->id,'rating'=>$row])->where('rating_type','3');

             // calculation for property
             $property_count = $property_review->count();
             if($property_count > 0)
             {
                $property_sum = $property_review->sum('rating');
                array_push($property_review_array,$property_sum);


             }
             else
             {
                $property_sum=0;
             }

             /**************location****************/

             $location_count = $location_review->count();
             if($location_count > 0)
             {
                $location_sum = $location_review->sum('rating');
                array_push($location_review_array,$location_sum);


             }
             else
             {
                $location_sum=0;
             }

             /*******************clean*************/
             $clean_count = $clean_review->count();
             if($clean_count > 0)
             {
                $clean_sum = $clean_review->sum('rating');
                array_push($clean_review_array,$clean_sum);


             }
             else
             {
                $clean_sum=0;
             }

            /***********************money*************/
             $money_count = $money_review->count();
             if($money_count > 0)
             {
                $money_sum = $money_review->sum('rating');
                array_push($money_review_array,$money_sum);


             }
             else
             {
                $money_sum=0;
             }
             
        }
        $property_total=array_sum($property_review_array);
        $property_review_count=count($property_review_array);

        if($property_total >0)
        {
            $data['property_review']=floor($property_total/$property_review_count);


        }
        else
        {
            $data['property_review']=0;


        }

        $location_total=array_sum($location_review_array);
        $location_review_count=count($location_review_array);
        
        if($location_total >0)
        {
            $data['location_review']=floor($location_total/$location_review_count);


        }
        else
        {
            $data['location_review']=0;


        }

        $clean_total=array_sum($clean_review_array);
        $clean_review_count=count($clean_review_array);
        
        if($clean_total >0)
        {
            $data['clean_review']=floor($clean_total/$clean_review_count);


        }
        else
        {
            $data['clean_review']=0;


        }


        $money_total=array_sum($money_review_array);
        $money_review_count=count($money_review_array);
        
        if($money_total >0)
        {
            $data['money_review']=floor($money_total/$money_review_count);


        }
        else
        {
            $data['money_review']=0;


        }


        $reviews =array_unique(PropertyReview::where('property_id',$data['property']->id)->pluck('user_id')->toArray());
        $favourite=PropertyFavourite::where(['user_id'=>auth()->user()->id,'property_id'=>$data['property']->id]);
        $favourite_count=$favourite->count();
        if($favourite_count > 0)
        {
            $fav=$favourite->first();
            $data['favourite_status']=$fav->is_favourite;


        }
        else
        {
            $data['favourite_status']='0';

        }
        

        $reviews_response=[];

        foreach($reviews as $row)
        {
            $check = PropertyBlockUser::where(['blocked_to'=>$row,'blocked_by'=>auth()->user()->id])->count();
            if($check < 1)
            {


                $rat_array=[];
                $user_comment = PropertyReview::select('comment')->where(['property_id'=>$data['property']->id,'user_id'=>$row])->first();

                $loc_review=PropertyReview::select('rating')->where(['property_id'=>$data['property']->id,'user_id'=>$row,'rating_type'=>1])->first();

                $loc=@$user_review->rating;

                array_push($rat_array,$loc);


                $money_review=PropertyReview::select('rating')->where(['property_id'=>$data['property']->id,'user_id'=>$row,'rating_type'=>2])->first();
                $money =@$money_review->rating;

                array_push($rat_array,$money);


                $clean_review=PropertyReview::select('rating')->where(['property_id'=>$data['property']->id,'user_id'=>$row,'rating_type'=>3])->first();
                $clean =@$clean_review->rating;
                array_push($rat_array,$clean);


                $use_review = floor(array_sum($rat_array));

                $users = User::where('id',$row)->first();
                $result['id']=$users->id;
                $result['name']=$users->fname.''.$users->lname;
                $result['image']=@$users->image;
                $result['message']=@$user_comment->comment;
                $result['rating']=@$use_review;

                $result['created_at']=$user_comment->created_at;
                $reviews_response[]=$result;
            }
        }
        $data['property']['user_details']=  $data['property']->getUserProfile; 
        $data['review_response']=$reviews_response;
        $data['gallery']=PropertyGallery::where('property_id',$data['property']->id)->get();
        $data['specification']=PropertySpecification::where('property_id',$data['property']->id)->first();
        $data['seller_details']=User::where('id',$data['property']->user_id)->first();
        $data['review_image']=PropertyGallery::where('property_id',$data['property']->id)->first();

        return $data;
    }

    /*********Offers List**********/
    public function offerList(Request $request)
    {
        $response=$this->myPropertyDetails(); 
        return view('offers.list_offers',compact('response'));
    }
    /****************** send Offer***********/
    public function sendOffer(Request $request)
    {
        if($request->isMethod('post'))
        {
            $validator = Validator::make($request->all(),[
                'message'=>'required|string|min:10|max:500'
       
            ]);

            if ($validator->fails())
            { 
                $messages = $validator->messages();
                foreach ($messages->all() as $message)
                {
                    Toastr::error($message, 'Failed', ['timeOut' => 5000]);
                }
                return redirect()->back()->withErrors($validator)->withInput(); 
                                                  
            }
            if(empty(auth()->user()->id))
            {
            Toastr::error('You have to  login first', 'error', ['timeOut' => 5000]);
            return redirect('view-property/'.$request->property_id);
            }

            $property_details=Property::where('slug',$request->property_id)->first();

            $offer = new Offer;
            $offer->user_id=auth()->user()->id;
            $offer->property_id=$property_details->id;
            $offer->message=$request->message;
            $offer->save();

            Toastr::success('Offer  send Successfull', 'Success', ['timeOut' => 5000]);
            return redirect('view-property/'.$request->property_id);

        }

    }

    /*************my properties *********/
    public function myProperties(Request $request)
    {
        $properties = Property::where(['user_id'=>auth()->user()->id,'status'=>'A'])->get();
        $response=[];
        foreach($properties as $row)
        {

           $gallery=PropertyGallery::where('property_id',$row->id)->first();
           $specifications=PropertySpecification::where('property_id',$row->id)->first();
           $result['id']=$row->id;
           $result['name']=@$row->name;
           $result['address']=@$row->address;
           $result['price']=$row->price;
           $result['image']=@$gallery->file;
           $result['trending']=@$row->trending;
           $result['bedroom']=@$row->bedroom;
           $result['bathroom']=@$row->bathroom;
           $result['kitchen']=@$specifications->kitchen;
           $result['area']=@$specifications->area;
           $result['book_status']=@$row->book_status;
           $result['slug']=@$row->slug;
           $response[]=$result;
        }        return view('properties.my_properties',compact('response'));
    }

    /************* My property details***********/
    public function myPropertyDetails()
    {

        $user_id=auth()->user()->id;
         $offer_properties=array_unique(DB::table('table_properties')
                            ->join('offers', 'offers.property_id', '=', 'table_properties.id')
                            ->select('offers.property_id')
                            ->where('table_properties.user_id', $user_id)
                            ->get()->toArray());


        $offer_response=[];
        $response=[];
        foreach($offer_properties as $row)
        {
           $properties= Property::where(['user_id'=>auth()->user()->id,'id'=>$row->property_id])->first();

           $gallery=PropertyGallery::where('property_id',$row->property_id)->first();
           $specifications=PropertySpecification::where('property_id',$row->property_id)->first();
           $result['id']=$properties->id;
           $result['name']=@$properties->name;
           $result['address']=@$properties->address;
           $result['price']=$properties->price;
           $result['image']=@$gallery->file;
           $result['trending']=@$properties->trending;
           $result['bedroom']=@$specifications->bedroom;
           $result['bathroom']=@$specifications->bathroom;
           $result['kitchen']=@$specifications->kitchen;
           $result['area']=@$specifications->area;
           $result['book_status']=@$properties->book_status;
           $result['trending']=@$properties->trending;
           $result['slug']=@$properties->slug;



           $data=array_unique(Offer::where('property_id',$row->property_id)->pluck('user_id')->toArray());

           $users = User::whereIn('id',$data)->get();
           

           $result['offer_data']=User::whereIn('id',$data)->get();
           $response[]=$result;
        }
        return $response;
    }
    /*************** properts offers************/
    public function offerDetails(Request $request)
    {
        $user_array= array_unique(Offer::where('property_id',$request->property_id)->pluck('user_id')->toArray());

        $users_response=[];
        foreach($user_array as $row)
        {
           $users =User::where('id',$row)->where('status','A')->first();



           if(!empty($users->image))
           {
             $html ='<li class="list-group-item px-0">
                  <div class="d-flex align-items-center justify-content-between">
                     <div class="avatar-md">

                        <a href='.url('other-profile/'.$users->id).'  class="image"><img src='.asset('public/assets/uploads/users/'.$users->image).'></a>
                     </div>
                     <div class="flex-fill pl-4">
                        <div class="font-md text-dark">'.$users->lname.'</div>
                        <div class="size-12 text-gray">'.$users->fname.'</div>
                     </div>
                     <button class="btn btn-primary ml-auto rounded-pill px-4 size-14 py-1">Invited</button>
                  </div>
               </li>';
           }
           else
           {
             $html ='<li class="list-group-item px-0">
                  <div class="d-flex align-items-center justify-content-between">
                     <div class="avatar-md">
                        <a href='.url('other-profile/'.$users->id).' class="image"><img src='.asset('public/assets/img/user/default_user.png').'></a>
                     </div>
                     <div class="flex-fill pl-4">
                        <div class="font-md text-dark">'.$users->lname.'</div>
                        <div class="size-12 text-gray">'.$users->fname.'</div>
                     </div>
                     <button class="btn btn-primary ml-auto rounded-pill px-4 size-14 py-1">Invited</button>
                  </div>
               </li>';
           }
            


            $users_response[]=$html;

        }

        return Response::json(['response'=>$users_response,'status' => 200,'message' => 'List of users  fetch  Successfully!.']);
    }

    /**********sold Properties ************/
    public function soldProperties(Request $request)
    {
        $my_properties=Property::where('user_id',auth()->user()->id);
        $properties_array=$my_properties->where('status','A')->get();
        $response=[];
        foreach($properties_array as $row)
        {
        
                $number_array=[1,2,3,4,5];
                $property_review_array=[];

                foreach($number_array as $number_row)
                {
                     $property_review = PropertyReview::where(['property_id'=>$row->id,'rating'=>$number_row]);
                     // calculation for property
                     $property_count = $property_review->count();
                     if($property_count > 0)
                     {
                        $property_sum = $property_review->sum('rating');
                        array_push($property_review_array,$property_sum);

                     }
                     else
                     {
                        $property_sum=0;
                     }     
                }

                $property_total=array_sum($property_review_array);
                $property_review_count=count($property_review_array);

                if($property_total >0)
                {
                  $result['property_review']=floor($property_total/$property_review_count);
                }
                else
                {
                   
                  $result['property_review']=0;


                }

           $gallery=PropertyGallery::where('property_id',$row->id)->first();
           $specifications=PropertySpecification::where('property_id',$row->id)->first();
           $result['id']=$row->id;
           $result['name']=@$row->name;
           $result['address']=@$row->address;
           $result['price']=$row->price;
           $result['image']=@$gallery->file;
           $result['trending']=@$properties->trending;
           $result['bedroom']=@$specifications->bedroom;
           $result['bathroom']=@$specifications->bathroom;
           $result['kitchen']=@$specifications->kitchen;
           $result['area']=@$specifications->area;
           $result['book_status']=@$row->book_status;
           $result['trending']=@$row->trending;
           $result['slug']=@$row->slug;
           $response[]=$result;
        }

        return view('properties.sold_properties',compact('response'));
    }

    /************Sold property details***************/
    public function soldPropertyDetail(Request $request,$id)
    {
           $properties=Property::where(['user_id'=>auth()->user()->id,'slug'=>$id])->first();

           $gallery=PropertyGallery::where('property_id',$properties->id)->first();
           $specifications=PropertySpecification::where('property_id',$properties->id)->first();
           $payment_details = Payment::where('property_id',$properties->id)->first();

           $user_details=User::where('id',$payment_details->user_id)->first();
           $roomchat = base64url_encode($user_details->id."#".time());
           $url=url('chats?roomchat='.$roomchat);


           $result['id']=@$properties->id;
           $result['name']=@$properties->name;
           $result['address']=@$properties->address;
           $result['price']=$properties->price;
           $result['ptype']=$properties->ptype;

           $result['image']=@$gallery->file;
           $result['trending']=@$properties->trending;
           $result['bedroom']=@$specifications->bedroom;
           $result['bathroom']=@$specifications->bathroom;
           $result['kitchen']=@$specifications->kitchen;
           $result['area']=@$specifications->area;
           $result['pets']=@$specifications->pets;

           $result['book_status']=@$properties->book_status;
           $result['trending']=@$properties->trending;
           $result['slug']=@$properties->slug;


           $result['user_name']=@$user_details->fname.' '.@$user_details->lname;
           $result['user_image']=@$user_details->image;
           $result['payment_date']=@date('d M, Y', strtotime($payment_details->created_at));

           $result['transaction_code']=@$payment_details->txn_code;
           $result['message_url']=$url;

        
        return view('properties.sold_property_detail',compact('result'));
    }
    /********************delete Image Property******/
    public function deleteImageProperty(Request $request)
    {
        $details=Property::where('id',$request->property_id)->first();
        PropertyGallery::where(['id'=>$request->id,'property_id'=>$details->id])->delete();
        return Response::json(['status' => 200,'message' => 'Image delete  Successfully!.']);
    }
    /****************My Tour Request ***************/
    public function myTourRequests(Request $request)
    {
        $user_id=auth()->user()->id;

        $visit_properties=array_unique(DB::table('table_properties')
                            ->join('property_visit', 'property_visit.property_id', '=', 'table_properties.id')
                            ->select('property_visit.property_id')
                            ->where('table_properties.user_id', $user_id)
                            ->get()->toArray());



        $response=[];
        foreach($visit_properties as $row)
        {
           $properties= Property::where(['id'=>$row->property_id])->first();

           $visit=PropertyVisit::where('property_id',$row->property_id)->first();

           $gallery=PropertyGallery::where('property_id',$row->property_id)->first();
           $result['id']=$properties->id;
           $result['name']=@$properties->name;
           $result['address']=@$properties->address;
           $result['price']=@$properties->price;
           $result['image']=@$gallery->file;
           $result['book_status']=@$properties->book_status;
           $result['trending']=@$properties->trending;
           $result['slug']=@$properties->slug;
           $result['accept_status']=@$visit->is_accept;




           $data=array_unique(PropertyVisit::where('property_id',$row->property_id)->limit(3)->pluck('user_id')->toArray());

           $users = User::whereIn('id',$data)->get();
           $result['visit_data']=User::whereIn('id',$data)->get();
           $response[]=$result;
        }
        return view('properties.tour_request',compact('response'));
    }

    /************Tour Details*********************/

    public function tourDetails(Request $request)
    {
        $user_array= array_unique(PropertyVisit::where('property_id',$request->property_id)->pluck('user_id')->toArray());

        $users_response=[];
        foreach($user_array as $row)
        {
           $users =User::where('id',$row)->where('status','A')->first();
           $roomchat = base64url_encode($users->id."#".time());
           $url=url('chats?roomchat='.$roomchat);




           if(!empty($users->image))
           {
             $html ='<li class="list-group-item px-0">
                  <div class="d-flex align-items-center justify-content-between">
                     <div class="avatar-md">

                        <a href='.url('other-profile/'.$users->id).' class="image"><img src='.asset('public/assets/uploads/users/'.$users->image).'></a>
                     </div>
                     <div class="flex-fill pl-4">
                        <div class="font-md text-dark">'.$users->lname.'</div>
                        <div class="size-12 text-gray">'.$users->fname.'</div>
                     </div>
                     <a href='.$url.'  class="btn btn-primary ml-auto rounded-pill px-4 size-14 py-1">Message</a>
                  </div>
               </li>';
           }
           else
           {
             $html ='<li class="list-group-item px-0">
                  <div class="d-flex align-items-center justify-content-between">
                     <div class="avatar-md">
                        <a href='.url('other-profile/'.$users->id).' class="image"><img src='.asset('public/assets/img/user/default_user.png').'></a>
                     </div>
                     <div class="flex-fill pl-4">
                        <div class="font-md text-dark">'.$users->lname.'</div>
                        <div class="size-12 text-gray">'.$users->fname.'</div>
                     </div>
                     <a href='.$url.'  class="btn btn-primary ml-auto rounded-pill px-4 size-14 py-1">Message</a>                  
                     </div>
               </li>';
           }
            


            $users_response[]=$html;

        }

        return Response::json(['response'=>$users_response,'status' => 200,'message' => 'List of users  fetch  Successfully!.']);
    }

    /*****Accept Tour *******************/
    public function AcceptTour(Request $request)
    {
        PropertyVisit::where('property_id',$request->property_id)->update(['is_accept'=>1]);
        return Response::json(['status' => 200,'message' => 'Request  accept Successfully!.']);
    }
    /**************send Tour Request**************/
    /*****Accept Tour *******************/
    public function sendTourRequest(Request $request)
    {
        if($request->isMethod('post'))
        {
   
            $validator = Validator::make($request->all(),[
                'date'=>'required',
                'time_hour'=>'required',
                'time_minute'=>'required',
                'time_format'=>'required',
                'note'=>'required'

            ]);


            if ($validator->fails())
            { 
                $messages = $validator->messages();
                foreach ($messages->all() as $message)
                {
                    Toastr::error($message, 'Failed', ['timeOut' => 5000]);
                }
                return redirect()->back()->withErrors($validator)->withInput(); 
                                                  
            }

            $date = strtr($request->date, '/', '-');



            $details=PropertyVisit::where(['user_id'=>auth()->user()->id,'property_id'=>$request->property_id]);
            $count = $details->count();

            $property = Property::where(['id'=>$request->property_id])->first();


            if($count > 0)
            {
                $result = $details->first();
                $details->update(['time'=>$request->time_hour.':'.$request->time_minute.' '.$request->time_format,'note'=>$request->note,'status'=>0,'date'=>date('Y-m-d', strtotime($date))]);
                $visit_id = $details->id;

            }
            else
            {
                $visit= new PropertyVisit;
                $visit->property_id=$request->property_id;
                $visit->user_id=auth()->user()->id;
                $visit->note=trim($request->note);
                $visit->status='A';
                $visit->is_accept='0';
                $visit->time=$request->time_hour.':'.$request->time_minute.' '.$request->time_format;
                $visit->date=date('Y-m-d', strtotime($date));
                $visit->save();

                $visit_id = $visit->id;

            }

            /*Send Notification*/
            $noti['title'] = "New visit request";
            $noti['body'] = auth()->user()->name." has requested to visit the property.";
            $to_user_id = [$property->user_id];
            $types = NotificationOff::where(['user_id' => $property->user_id])->pluck('type')->all();
            if (sizeof($types) > 0) {
                if (!in_array('visit_property', $types)) {
                    $firebaseToken = User::whereNotNull('fcm_token')->where(['id' => $property->user_id])->pluck('fcm_token')->all();
                    $this->sendNotification($noti, $firebaseToken, [$property->user_id], 'visit_property', ['property_id' => $property->id, 'visit_id' => $visit_id]);
                }
            }else{
                $firebaseToken = User::whereNotNull('fcm_token')->where(['id' => $property->user_id])->pluck('fcm_token')->all();
                $this->sendNotification($noti, $firebaseToken, [$property->user_id], 'visit_property', ['property_id' => $property->id, 'visit_id' => $visit_id]);
            }
            /*Send Notification*/

            Toastr::success('Request Send Successfull', 'Success', ['timeOut' => 5000]);
            return redirect()->back();
        }
        
    }
    /******************Submit Review ***********/
    public function submitReview(Request $request)
    {
        if($request->isMethod('post'))
        {
   
            $validator = Validator::make($request->all(),[
                'rating_type'=>'required',
                'rating'=>'required',
                'comment'=>'required|max:2000|min:5',

            ]);


            if ($validator->fails())
            { 
                $messages = $validator->messages();
                foreach ($messages->all() as $message)
                {
                    Toastr::error($message, 'Failed', ['timeOut' => 5000]);
                }
                return redirect()->back()->withErrors($validator)->withInput(); 
                                                  
            }

            $details=PropertyReview::where(['user_id'=>auth()->user()->id,'rating_type'=>$request->rating_type,'property_id'=>$request->property_id]);
            $count = $details->count();


            if($count > 0)
            {
                $result = $details->first();
                $details->update(['rating'=>$request->rating,'comment'=>$request->comment]);

            }
            else
            {
                $review= new PropertyReview;
                $review->property_id=$request->property_id;
                $review->user_id=auth()->user()->id;
                $review->comment=trim($request->comment);
                $review->rating=$request->rating;
                $review->rating_type=$request->rating_type;
                $review->save();

                /*Send Notification*/
                $property = Property::where(['id'=>$request->property_id])->first();
                $noti['title'] = "Property Review";
                $noti['body'] = auth()->user()->name." submitted a review of your property.";
                $to_user_id = [$property->user_id];
                $types = NotificationOff::where(['user_id' => $property->user_id])->pluck('type')->all();
                if (sizeof($types) > 0) {
                    if (!in_array('review_property', $types)) {
                        $firebaseToken = User::whereNotNull('fcm_token')->where(['id' => $property->user_id])->pluck('fcm_token')->all();
                        $this->sendNotification($noti, $firebaseToken, [$property->user_id], 'review_property', ['property_id' => $property->id, 'review_id' => $review->id]);
                    }
                }else{
                    $firebaseToken = User::whereNotNull('fcm_token')->where(['id' => $property->user_id])->pluck('fcm_token')->all();
                    $this->sendNotification($noti, $firebaseToken, [$property->user_id], 'review_property', ['property_id' => $property->id, 'review_id' => $review->id]);
                }
                /*Send Notification*/

            }  

            Toastr::success('Review Submit Successfull', 'Success', ['timeOut' => 5000]);
            return redirect()->back();
        }
    }

    /************ Add favourite property*****/
    public function addPropertyFavourite(Request $request,$id)
    {
        
        $property=Property::where('slug',$id)->first();


           $details= PropertyFavourite::where(['user_id'=>auth()->user()->id,'property_id'=>$property->id]);
            $count=  $details->count();
           if($count > 0)
           {
               $result=$details->first();
               if($result->is_favourite == 1)
               {
                  $fav_status='0';
                   PropertyFavourite::where(['user_id'=>auth()->user()->id,'property_id'=>$property->id])->update(['is_favourite'=>$fav_status]);
                 Toastr::success('Property is remove from  your favourite list', 'Success', ['timeOut' => 5000]);
                return redirect()->back();

               }
               else
               {
                $fav_status='1';
                               PropertyFavourite::where(['user_id'=>auth()->user()->id,'property_id'=>$property->id])->update(['is_favourite'=>$fav_status]);
                               Toastr::success('Property added in your favourite list', 'Success', ['timeOut' => 5000]);
                return redirect()->back();

               }
           }
           else
           {
                $favourite =new PropertyFavourite;
                $favourite->property_id=$property->id;
                $favourite->user_id=auth()->user()->id;
                $favourite->is_favourite='1';
                $favourite->status='A';
                $favourite->save();
                Toastr::success('Property added in your favourite list', 'Success', ['timeOut' => 5000]);
                return redirect()->back();
           }
            
            
    }
    /************Report or Block ********/
    public function blockReportUser(Request $request)
    {
       $block = new PropertyBlockUser;
       $block->blocked_to=$request->buyer_id;
       $block->blocked_by=auth()->user()->id;
       $block->property_id=$request->property_id;
       $block->type=$request->type;
       $block->message=$request->message;
       $block->save();

        Toastr::success('Submit Successfull', 'Success', ['timeOut' => 5000]);
        return redirect()->back();
    }

    /***************property checkout *********/

    public function propertyCheckout(Request $request,$id)
    {

        $property_detail=Property::where('slug',$id)->first();

        $seller_info=User::where('id',$property_detail->id)->first();

        $property_detail['seller_name'] = $seller_info->username;

        return view('payment.checkout',compact('property_detail'));

    }

    /******************favoriteList**********/
    public function myFavouriteList(Request $request)
    {
        $selected_array=[];
        if($request->isMethod('post'))
        {
            $list= (string)$request->pre_slected_value;

            $selected_array = explode(',', $list);

        }
        $my_properties=PropertyFavourite::where(['user_id'=>auth()->user()->id,'is_favourite'=>'1']);
        $properties_array=$my_properties->where('status','A')->get();
        $response=[];
        foreach($properties_array as $row)
        {
        
            $number_array=[1,2,3,4,5];
            $property_review_array=[];

            foreach($number_array as $number_row)
            {
                 $property_review = PropertyReview::where(['property_id'=>$row->property_id,'rating'=>$number_row]);
                 // calculation for property
                 $property_count = $property_review->count();
                 if($property_count > 0)
                 {
                    $property_sum = $property_review->sum('rating');
                    array_push($property_review_array,$property_sum);

                 }
                 else
                 {
                    $property_sum=0;
                 }     
            }

            $property_total=array_sum($property_review_array);
            $property_review_count=count($property_review_array);

            if($property_total >0)
            {
              $result['property_review']=floor($property_total/$property_review_count);
            }
            else
            {
               
              $result['property_review']=0;


            }
           $property_details=Property::where(['id'=>$row->property_id,'status'=>'A'])->first();

           $gallery=PropertyGallery::where('property_id',$row->property_id)->take(4)->get();
           $comparison_details=PropertyGallery::where('property_id',$row->property_id)->first();
           $specifications=PropertySpecification::where('property_id',$row->property_id)->first();
           $result['id']=$property_details->id;
           $result['name']=@$property_details->name;
           $result['address']=@$property_details->address;
           $result['desc']=@$property_details->desc;

           $result['price']=$property_details->price;
           $result['image']=@$gallery;
           $result['trending']=@$property_details->trending;
           $result['bedroom']=@$specifications->bedroom;
           $result['bathroom']=@$specifications->bathroom;
           $result['kitchen']=@$specifications->kitchen;
           $result['area']=@$specifications->area;
           $result['book_status']=@$property_details->book_status;
           $result['trending']=@$property_details->trending;
           $result['slug']=@$property_details->slug;
           $result['is_favourite']=@$row->is_favourite;
           $result['comparison_image']=@$comparison_details->file;
           $response[]=$result;
        }
       return view('properties.favourite_list',compact('response','selected_array'));
    }

    /******************favoriteList**********/
    public function myFavouriteComparisionList(Request $request)
    {
        $list= (string)$request->list;

        $compare_array = explode(',', $list);
        if(count($compare_array) < 2)
        {
            Toastr::error('You have to select atleast two properties for compare', 'Error', ['timeOut' => 5000]);
                return redirect()->back();

        }
        $response=[];
        foreach($compare_array as $row)
        {
        
            $number_array=[1,2,3,4,5];
            $property_review_array=[];

            foreach($number_array as $number_row)
            {
                 $property_review = PropertyReview::where(['property_id'=>$row,'rating'=>$number_row]);
                 // calculation for property
                 $property_count = $property_review->count();
                 if($property_count > 0)
                 {
                    $property_sum = $property_review->sum('rating');
                    array_push($property_review_array,$property_sum);

                 }
                 else
                 {
                    $property_sum=0;
                 }     
            }

            $property_total=array_sum($property_review_array);
            $property_review_count=count($property_review_array);

            if($property_total >0)
            {
              $result['property_review']=floor($property_total/$property_review_count);
            }
            else
            {
               
              $result['property_review']=0;


            }
           $property_details=Property::where(['id'=>$row,'status'=>'A'])->first();

           $gallery=PropertyGallery::where('property_id',$row)->take(4)->get();
           $specifications=PropertySpecification::where('property_id',$row)->first();
           $result['id']=$property_details->id;
           $result['name']=@$property_details->name;
           $result['address']=@$property_details->address;
           $result['desc']=@$property_details->desc;

           $result['price']=$property_details->price;
           $result['image']=@$gallery;
           $result['trending']=@$property_details->trending;
           $result['bedroom']=@$specifications->bedroom;
           $result['bathroom']=@$specifications->bathroom;
           $result['kitchen']=@$specifications->kitchen;
           $result['pets']=@$specifications->pets;

           $result['area']=@$specifications->area;
           $result['book_status']=@$property_details->book_status;
           $result['trending']=@$property_details->trending;
           $result['slug']=@$property_details->slug;
           $result['is_favourite']=@$row->is_favourite;
           $response[]=$result;
        }

        return view('properties.comparison_list',compact('response','list'));
    }

}
