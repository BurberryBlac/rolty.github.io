<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Models\User;
use App\Models\Event;
use App\Models\EventInvite;
use App\Models\Follower;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Response;
use Validator;
use Session;
use Cache;
use Config;
use Toastr;
use Helper;
use Hash;
use DB;
use Image;

class EventContrller extends Controller
{
    //
    // public function __construct()
    // {
    //     $this->middleware('auth');
    // }
    public function events()
    {
        $events = Event::with('invitations')->with('invitations.user')->where('date','>=',date('Y-m-d'))->get()->toArray();
        return view('events.show_event')->with('events',$events);
    }
    public function create_event()
    {
        $membershipTier = isset(Auth::user()->membership_tier)?Auth::user()->membership_tier:0; 
        $totalinviteduserd = isset(Auth::user()->membership_tier)?Auth::user()->no_of_user_envited:0; 
    
        $buyers = User::where('role_id',4)->get()->toArray();

        if($membershipTier == 0 || $membershipTier == 1){
            
            $buyers = User::where('role_id',4)->take(0)->get()->toArray();
        }
        if($membershipTier == 2){
            $take = 10-$totalinviteduserd;
            $buyers = User::where('role_id',4)->take($take)->get()->toArray();
        }
       
       
        return view('events.create_event')->with('buyers',$buyers);
    }
    public function add_event(Request $request)
    {
        $validator = Validator::make($request->all(),[


            'image' => 'sometimes|nullable|mimes:jpeg,jpg,png,gif',
            'event' => 'required|string',
            'long' => 'required',
            'lat' => 'required',
            'date' => 'required',
            'hours' => 'required',
            'mins' => 'required',
            'half' => 'required',
            'description' => 'required|string',
            'invited_users' => 'sometimes|nullable'
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

       // $imageName = time().'.'.$request->image->extension();  
        //$request->image->move(public_path('assets/img/event'), $imageName);

          $image = $request->file('image');
          // $imagename = time() . $image->getClientOriginalName();
          // $filePath =  'event/' . $imagename;
          // $imagePath = Storage::disk('s3')->put($filePath, file_get_contents($image),'public');

          $event_image_name = time().'.'.$image->getClientOriginalExtension();
          $destinationPath = public_path('/assets/uploads/events');
          $imagePath = $destinationPath. "/".  $event_image_name;
          $image->move($destinationPath, $event_image_name);
          $image_path = $event_image_name;

        $event = new Event;
        $event->name = $request->event;
        $event->user_id = Auth::user()->id;
        $event->place = $request->location;
        $event->date = $request->date;
        $event->hours = $request->hours;
        $event->mins = $request->mins;
        $event->half = $request->half;
        $event->lat = $request->lat;
        $event->long = $request->lng;
        $event->description = $request->description;
        $event->image = $image_path;
        $event->save();

        $invited = explode(',', $request->invited_users[0]);
        $i = 0;
        foreach($invited as $key => $value){
            $event_invites = new EventInvite;
            $event_invites->user_id = $value;
            $event_invites->event_id = $event->id;
            $event_invites->save();
            $i++;
        }

        /*Send Notification*/
        $noti['title'] = "New event invite";
        $noti['body'] = auth()->user()->name." Invited you in an event";
        $followers = Follower::where(['type' => '1', 'status' => 'follow', 'follow_id' => auth()->user()->id])->pluck('user_id')->all();
        $to_user_id = array_values(array_unique(array_merge($followers, $invited)));
        if (sizeof($to_user_id) > 0) {
            foreach ($to_user_id as $key => $uid) {
                $types = NotificationOff::where(['user_id' => $uid])->pluck('type')->all();
                if (sizeof($types) > 0) {
                    if (!in_array('new_property', $types)) {
                        $firebaseToken = User::whereNotNull('fcm_token')->where(['id' => $uid])->pluck('fcm_token')->all();
                        app('App\Http\Controllers\Properties\PropertyController')->sendNotification($noti, $firebaseToken, [$uid], 'new_event', ['event_id' => $event->id]);
                    }
                }else{
                    $firebaseToken = User::whereNotNull('fcm_token')->where(['id' => $uid])->pluck('fcm_token')->all();
                    app('App\Http\Controllers\Properties\PropertyController')->sendNotification($noti, $firebaseToken, [$uid], 'new_event', ['event_id' => $event->id]);
                }
            }
        }
        /*Send Notificaiton*/

        /*---------Change no of user envite------*/
        $user = User::where('id',Auth::user()->id)->first();
        if(!empty($user)){

            $user->no_of_user_envited = $user->no_of_user_envited+$i;
            $user->save();
        }
        Toastr::success('Event added Successfull', 'Success', ['timeOut' => 5000]);
        return redirect('show_events');
    }
    public function edit_event(Request $request,$id)
    {
        $event = Event::with('invitations')->with('invitations.user')->where('id',$id)->get()->toArray();

        $buyers = User::where('role_id',4)->get();
        $buyer_response=[];
        foreach($buyers as $buyerList)
        {
            $result['id']=$buyerList->id;
            $result['user_name']=$buyerList->fname.''.$buyerList->lname;
            $result['user_image']=$buyerList->image;

            $check= EventInvite::where(['user_id'=>$buyerList->id,'event_id'=>$id])->count();
            if($check > 0)
            {
              $result['user_invte_status']='1';

            }
            else
            {
              $result['user_invte_status']='0';

            }
            $buyer_response[]=$result;


        }
        return view('events.edit')->with('event',$event[0])->with('buyer_response',$buyer_response);
    }
    public function update_event(Request $request)
    {
        $validator = Validator::make($request->all(),[


            'image' => 'sometimes|nullable|mimes:jpeg,jpg,png,gif',
            'event' => 'required|string',
            'long' => 'required',
            'lat' => 'required',
            'date' => 'required',
            'hours' => 'required',
            'mins' => 'required',
            'half' => 'required',
            'description' => 'required|string',
            'invited_users' => 'sometimes|nullable'
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
        
        if(isset($request->image) && $request->image != null)
        {
            

            
          $image = $request->file('image');
          // $imagename = time() . $image->getClientOriginalName();
          // $filePath =  'event/' . $imagename;
          // $imagePath = Storage::disk('s3')->put($filePath, file_get_contents($image),'public');

          $event_image_name = time().'.'.$image->getClientOriginalExtension();
          $destinationPath = public_path('/assets/uploads/events');
          $imagePath = $destinationPath. "/".  $event_image_name;
          $image->move($destinationPath, $event_image_name);
          $imageName = $event_image_name;


        }
        else
        {
            $imageName = $request->old_image;
        }
        Event::where('id',$request->id)->update([
            'name' => $request->event,'place' => $request->location,'lat'=>$request->lat,'long'=>$request->long,'date' => $request->date, 'hours' => $request->hours,'mins'=>$request->mins, 'half' => $request->half, 'description' => $request->description,'image'=> $imageName
        ]);
        Toastr::success('Event updated Successfull', 'Success', ['timeOut' => 5000]);

        return redirect('show_events');
    }
    
    public function event_details(Request $request){
        $event = Event::where('id',$request->event)->get()->toArray();
        return view('events.event_details')->with('event',$event[0]);
    }
    public function filter(Request $request){
        if($request->filter == 1){
            $events = Event::with('invitations')->with('invitations.user')->where('date','=',date('Y-m-d'))->get()->toArray();
            $filter = 'Today';
        }else{
            $end_date = date('Y-m-d', strtotime("+7 days"));
            $events = Event::with('invitations')->with('invitations.user')->whereBetween('date',[date('Y-m-d'),$end_date])->get()->toArray();
            $filter = 'This week';


        }
        return view('events.show_event')->with('events',$events)->with('filter',$filter);
    }
}
