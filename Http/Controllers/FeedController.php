<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Toastr;
use Session;
use App\Models\User;
use App\Models\Feed;
use App\Models\Property;
use App\Models\Event;
use App\Models\PropertyGallery;
use App\Models\FeedBlockUser;

class FeedController extends Controller
{

    public function feed_list(Request $request)
    {
      
        $membershipTier = isset(Auth::user()->membership_tier)?Auth::user()->membership_tier:0; 
        $user_id = isset(Auth::user()->id)?Auth::user()->id:0; 


        $block_user_ids =  FeedBlockUser::select('blocked_to')->where([['status','=','A'],['blocked_by','=',$user_id],['type','=','block']])->distinct()->get();
        $block_feed_ids =  FeedBlockUser::select('feed_id')->where([['status','=','A'],['blocked_by','=',$user_id],['type','=','report']])->distinct()->get();

        
        $feed_list = Feed::where([['status','!=','D']])->whereNotIn('id', $block_feed_ids)->whereNotIn('user_id',$block_user_ids)->orderBy('updated_at','DESC')->get();
     
        $propertyList = array();
        $data = array();
        if(count($feed_list)>0){  
            $i=0;
            foreach($feed_list as $feed){
               
                $data[$i] =  $feed;
                $data[$i]['user_details']=  $feed->getUserDetails; 
                if(!empty($feed->property_id)){

                    $data[$i]['property_details']= Property::where('id',$feed->property_id)->first();   
                    $data[$i]['property_gallery']= PropertyGallery::where([['property_id','=',$feed->property_id],['thumb_file','!=',null]])->get();   
                    
                }else{

                    $data[$i]['property_details']= array();
                }  
                if(!empty($feed->event_id)){

                    $data[$i]['event_details']= Event::where('id',$feed->event_id)->first();   
                }else{

                    $data[$i]['event_details']= array();
                }                                                                                 
                $i++;
            }
        } 
        $feed_list = $data;        

        $propert_list = Property::where([['status','!=','D']])->get();
        $filterlist =  Property::where([['status','!=','D']])->get();
        if($membershipTier == 0 || $membershipTier == 1){

            $propert_list = Property::where([['status','!=','D'],['trending','!=','mls']])->get();
            $filterlist =  Property::where([['status','!=','D'],['trending','!=','mls']])->get();
        }
      
        $event_list = Event::get();
      
        $min_price="";
        $max_price=""; 
        $min_area="";
        $max_area="";       
      
        return view('feed.feed_list',compact('feed_list','propert_list','event_list','filterlist','min_price','max_price','min_area','max_area'));
    }
 
    public function add_feed(Request $request)
    {
        
        $user = auth::user();
           
        $image_path ="";
        $video_path ="";
        if(isset($request->file)) {
            $image = $request->file('file');
            
            $profile_image_name = time().'.'.$image->getClientOriginalExtension();
            $destinationPath = public_path('/assets/img/feeds');
            $imagePath = $destinationPath. "/".  $profile_image_name;
            $image->move($destinationPath, $profile_image_name);

            $mime = $_FILES['file']['type'];
            if(strstr($mime, "video/")){
                $video_path  = $profile_image_name;
            }else if(strstr($mime, "image/")){
                $image_path = $profile_image_name;
            }           
        }	      
        $description ="";
        if(isset($request->property_id) && !empty($request->property_id)){

            $propert_details = Property::where([['id','=',$request->property_id]])->first();
            if(!empty($propert_details)){
                $description = $propert_details->desc;
            }
          
            $propertyGall = PropertyGallery::where([['property_id','=',$request->property_id],['thumb_file','!=',null]])->first();   
            if(!empty($propertyGall)){
                $image_path =  $propertyGall->thumb_file;
            }            
        }
        if(isset($request->event_id) && !empty($request->event_id)){

            $event_details = Event::where([['id','=',$request->event_id]])->first();
            if(!empty($event_details)){
                $description = $event_details->description;
                $image_path =  $event_details->image;
            }                               
        }
        if(isset($request->desc)){
            $description = $request->desc;
        }
        if(isset($request->feed_id) && !empty($request->feed_id)){
           
            $feed =  Feed::where('id',$request->feed_id)->first();        
            $feed->user_id = $user->id;
            $feed->desc = $description;
            if(isset($video_path) && !empty($video_path)){

                $feed->video_link = isset($video_path)?$video_path:'';
                $feed->attachment = '';
            }
            if(isset($image_path) && !empty($image_path)){

                $feed->attachment = isset($image_path)?$image_path:'';
                $feed->video_link = '';
            }
            $feed->feed_type = $request->feed_type;
            $feed->property_id = isset($request->property_id)?$request->property_id:null;
            $feed->event_id = isset($request->event_id)?$request->event_id:null;                 
            $feed->updated_at = now();   
            $successmsg = 'Feed successfully updated.';

        }else{
            
            $feed = new Feed();        
            $feed->user_id = $user->id;
            $feed->desc = $description;
            $feed->video_link = isset($video_path)?$video_path:'';
            $feed->attachment = isset($image_path)?$image_path:'';
            $feed->feed_type = $request->feed_type;
            $feed->property_id = isset($request->property_id)?$request->property_id:null;
            $feed->event_id = isset($request->event_id)?$request->event_id:null;
            $feed->created_at = now();      
            $feed->updated_at = now();   
            $successmsg = 'Feed successfully added.';
        }

        $feed->save();
        Toastr::success($successmsg,'Success');
        return redirect()->route('feed-list');
    }  

    public function get_property_by_id(Request $request){
         
        $property =  Property::where('id',$request->property_id)->first();  

        if(!empty($property)){
            $propertyImage = PropertyGallery::where([['property_id','=',$property->id],['thumb_file','!=',null]])->first();   
            $propertyImageSecond = PropertyGallery::where([['property_id','=',$property->id],['thumb_file','!=',null]])->orderBy('id','DESC')->first();   
            if(!empty($propertyImage)){
                $property['property_image_path'] =  $propertyImage->thumb_file;
            }else{
                $property['property_image_path'] = "";
            }  
            if(!empty($propertyImageSecond)){
                $property['property_image_path_second'] =  $propertyImageSecond->thumb_file;
            }else{
                $property['property_image_path_second'] = "";
            } 
            $property->getUserProfile;      
        }
        return $property;
    }
    public function get_event_by_id(Request $request){
         
        $event =  Event::where('id',$request->event_id)->first(); 

        if(!empty($event)){

            $event->getUserProfile;  
        }      
        return $event;
    }
    /***********Delete Feed***********/
    public function deleteFeed(Request $request)
    {
        Feed::where('id',$request->delete_id)->update(['status'=>'D']);
        Toastr::success('Feed deleted Successfully!', 'Success', ['timeOut' => 5000]);
        return redirect('feed-list');      
    }
    /***********save Live Video***********/
    public function saveLiveVideo(Request $request)
    {
       // print_r($request->all());exit;

        $imagePath = "";
        if(isset($request->video)) {
            $image = $request->file('video');
            
            $profile_image_name = time().'.'.$image->getClientOriginalExtension();
            $destinationPath = public_path('/assets/img/feeds');
            $imagePath = $destinationPath. "/".  $profile_image_name;
            $image->move($destinationPath, $profile_image_name);
               
        }
        return redirect('feed-list');      
    }

     /***********Feed Live***********/
     public function feedLive(Request $request)
     {        
 
        return view('feed.feed_live');    
     }

    /************Report or Block ********/
    public function blockReportUser(Request $request)
    {        
       
       $block = new FeedBlockUser;
       $block->blocked_to=$request->user_id;
       $block->blocked_by=auth()->user()->id;
       $block->feed_id=$request->feed_id;
       $block->type=$request->type;       
       $block->save();

       $msg= "Feed successfully Reported";
       if($request->type == "block"){
            $msg= "User successfully blocked";
       }
        Toastr::success($msg, 'Success', ['timeOut' => 5000]);
        return redirect()->back();
    }

    
}
