<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Toastr;
use Session;
use App\Models\User;
use App\Models\Follower;
use App\Models\Property;
use App\Models\Roles;

class ConnectionController extends Controller
{

    public function connection_list(Request $request)
    {
      
        $membershipTier = isset(Auth::user()->membership_tier)?Auth::user()->membership_tier:0; 
        $user_id = isset(Auth::user()->id)?Auth::user()->id:0; 


        $follower_user_ids =  Follower::select('follow_id')->where([['user_id','=',$user_id],['status','=','follow']])->distinct()->get();
        $following_user_ids =  Follower::select('user_id')->where([['follow_id','=',$user_id],['status','=','follow']])->distinct()->get();
        
        $follower_list_data = User::where([['status','!=','D']])->whereIn('id', $follower_user_ids)->orderBy('updated_at','DESC')->get();
        $following_list_data = User::where([['status','!=','D']])->whereIn('id', $following_user_ids)->orderBy('updated_at','DESC')->get();
        $new_connection_list_data = User::where([['status','!=','D']])->whereNotIn('id', $following_user_ids)->whereNotIn('id', $follower_user_ids)->where([['id','!=',$user_id]])->orderBy('updated_at','DESC')->get();
     
        $follower_list = array();
        $following_list = array();
        $new_connection_list = array();

        $follower_data = array();
        $following_data = array();
        $fnew_connection_data = array();

/*------------Get Follower Data------------*/
        if(count($follower_list_data)>0){  
            $i=0;
            foreach($follower_list_data as $follower){
               
                $follower_data[$i] =  $follower;
                $follower_data[$i]['user_role']=  Roles::Select('name')->where('id',$follower->role_id)->first(); 
                $follower_data[$i]['user_property_name']=  Property::Select('name')->where('user_id',$follower->id)->first(); 
                $follower_data[$i]['user_property_image']=  Property::Select('property_gallery.file')->join('property_gallery','property_gallery.property_id','=','table_properties.id')->where('table_properties.user_id',$follower->id)->first();                                                                                                              
                $i++;
            }
        } 
/*------------Get  Following Data------------*/
        if(count($following_list_data)>0){  
            $i=0;
            foreach($following_list_data as $new_connection){
               
                $following_data[$i] =  $new_connection;
                $following_data[$i]['user_role']=  Roles::Select('name')->where('id',$new_connection->role_id)->first(); 
                $following_data[$i]['user_property_name']=  Property::Select('name')->where('user_id',$new_connection->id)->first(); 
                $following_data[$i]['user_property_image']=  Property::Select('property_gallery.file')->join('property_gallery','property_gallery.property_id','=','table_properties.id')->where('table_properties.user_id',$new_connection->id)->first();                                                                                                              
                $i++;
            }
        } 

/*------------Get New Connection Data------------*/
        if(count($new_connection_list_data)>0){  
            $i=0;
            foreach($new_connection_list_data as $following){
               
                $fnew_connection_data[$i] =  $following;
                $fnew_connection_data[$i]['user_role']=  Roles::Select('name')->where('id',$following->role_id)->first(); 
                $fnew_connection_data[$i]['user_property_name']=  Property::Select('name')->where('user_id',$following->id)->first(); 
                $fnew_connection_data[$i]['user_property_image']=  Property::Select('property_gallery.file')->join('property_gallery','property_gallery.property_id','=','table_properties.id')->where('table_properties.user_id',$following->id)->first();                                                                                                              
                $i++;
            }
        } 
        $follower_list = $follower_data;        
        $following_list = $following_data;        
        $new_connection_list = $fnew_connection_data;        

        $toralCount =  Follower::where('table_followers.status','follow')->where('table_followers.follow_id',$user_id)->orWhere('table_followers.user_id',$user_id)->count();       
      
        return view('connection.connection_list',compact('follower_list','following_list','new_connection_list','toralCount'));
    } 

    public function search_new_connection(Request $request){
         

        
        $membershipTier = isset(Auth::user()->membership_tier)?Auth::user()->membership_tier:0; 
        $user_id = isset(Auth::user()->id)?Auth::user()->id:0; 

        $filter_conditions ="table_properties.name like '%".$request->name."%'";

        $follower_user_ids =  Follower::select('follow_id')->where([['user_id','=',$user_id],['status','=','follow']])->distinct()->get();
        $following_user_ids =  Follower::select('user_id')->where([['follow_id','=',$user_id],['status','=','follow']])->distinct()->get();

        $new_connection_list_data = User::where([['status','!=','D']])       
        ->where([['id','!=',$user_id]])
        ->where('fname', 'like', '%' . $request->search_key. '%')
        ->orWhere('lname', 'like', '%' . $request->search_key. '%')
        ->whereNotIn('id', $following_user_ids)
        ->whereNotIn('id', $follower_user_ids)
        ->orderBy('updated_at','DESC')
        ->get();

        //echo "<pre>";print_r($new_connection_list_data);exit;

        $fnew_connection_data =array();
        if(count($new_connection_list_data)>0){  
            $i=0;
            foreach($new_connection_list_data as $following){
               
                $fnew_connection_data[$i] =  $following;
                $user_role = Roles::Select('name')->where('id',$following->role_id)->first(); 
                $fnew_connection_data[$i]['user_role']= isset($user_role->name)?$user_role->name:''; 
                $property_name = Property::Select('name')->where('user_id',$following->id)->first(); 
                $fnew_connection_data[$i]['user_property_name']= isset($property_name->name)?$property_name->name:''; 
                $property_gallery_img = Property::Select('property_gallery.file')->join('property_gallery','property_gallery.property_id','=','table_properties.id')->where('table_properties.user_id',$following->id)->first();                                                                                                              

                $propery_image =isset($property_gallery_img->file)?getPublicPropertyImage('public/assets/uploads/properties/'.$property_gallery_img->file):asset('public/assets/img/default_property.jpeg') ;
                $fnew_connection_data[$i]['user_property_image']=  $propery_image;
                $fnew_connection_data[$i]['user_image']=  isset($following->image)?getPublicUserImage("public/assets/uploads/users/".$following->image): asset('public/assets/img/user/default_user.png');
                
                $i++;
            }
        } 
        
        return $fnew_connection_data;
    }
   

    public function connection_follow(Request $request){
         

        
        $membershipTier = isset(Auth::user()->membership_tier)?Auth::user()->membership_tier:0; 
        $user_id = isset(Auth::user()->id)?Auth::user()->id:0; 



        $check_follow = Follower::where([['user_id','=',$request->user_id],['follow_id','=',$user_id]])->first();
        
        if(empty($check_follow)){

            $follow = new Follower();
            $follow->user_id = $request->user_id;
            $follow->follow_id = $user_id;
            $follow->status = "follow";
            $follow->save();
        }
                                  

        /*-----------geting all data ----------------*/


        $follower_user_ids =  Follower::select('follow_id')->where([['user_id','=',$user_id],['status','=','follow']])->distinct()->get();
        $following_user_ids =  Follower::select('user_id')->where([['follow_id','=',$user_id],['status','=','follow']])->distinct()->get();
        
        $follower_list_data = User::where([['status','!=','D']])->whereIn('id', $follower_user_ids)->orderBy('updated_at','DESC')->get();
        $following_list_data = User::where([['status','!=','D']])->whereIn('id', $following_user_ids)->orderBy('updated_at','DESC')->get();
        $new_connection_list_data = User::where([['status','!=','D']])->whereNotIn('id', $following_user_ids)->whereNotIn('id', $follower_user_ids)->where([['id','!=',$user_id]])->orderBy('updated_at','DESC')->get();
     
        $follower_list = array();
        $following_list = array();
        $new_connection_list = array();

        $follower_data = array();
        $following_data = array();
        $fnew_connection_data = array();

/*------------Get Follower Data------------*/
        if(count($follower_list_data)>0){  
            $i=0;
            foreach($follower_list_data as $follower){
               
                $follower_data[$i] =  $follower;
                $user_role = Roles::Select('name')->where('id',$follower->role_id)->first(); 
                $follower_data[$i]['user_role']= isset($user_role->name)?$user_role->name:''; 
                $property_name = Property::Select('name')->where('user_id',$follower->id)->first(); 
                $follower_data[$i]['user_property_name']= isset($property_name->name)?$property_name->name:''; 
                $property_gallery_img = Property::Select('property_gallery.file')->join('property_gallery','property_gallery.property_id','=','table_properties.id')->where('table_properties.user_id',$follower->id)->first();                                                                                                              

                $propery_image =isset($property_gallery_img->file)?getPublicPropertyImage('public/assets/uploads/properties/'.$property_gallery_img->file):asset('public/assets/img/default_property.jpeg') ;
                $follower_data[$i]['user_property_image']=  $propery_image;
                $follower_data[$i]['user_image']=  isset($follower->image)?getPublicUserImage("public/assets/uploads/users/".$follower->image): asset('public/assets/img/user/default_user.png');
                $i++;
            }
        } 
/*------------Get  Following Data------------*/
        if(count($following_list_data)>0){  
            $i=0;
            foreach($following_list_data as $following){
               
                $following_data[$i] =  $following;
                $user_role = Roles::Select('name')->where('id',$following->role_id)->first(); 
                $following_data[$i]['user_role']= isset($user_role->name)?$user_role->name:''; 
                $property_name = Property::Select('name')->where('user_id',$following->id)->first(); 
                $following_data[$i]['user_property_name']= isset($property_name->name)?$property_name->name:''; 
                $property_gallery_img = Property::Select('property_gallery.file')->join('property_gallery','property_gallery.property_id','=','table_properties.id')->where('table_properties.user_id',$following->id)->first();                                                                                                              

                $propery_image =isset($property_gallery_img->file)?getPublicPropertyImage('public/assets/uploads/properties/'.$property_gallery_img->file):asset('public/assets/img/default_property.jpeg');
                $following_data[$i]['user_property_image']=  $propery_image;
                $following_data[$i]['user_image']=  isset($following->image)?getPublicUserImage("public/assets/uploads/users/".$following->image): asset('public/assets/img/user/default_user.png');
                $i++;
            }
        } 

/*------------Get New Connection Data------------*/
        if(count($new_connection_list_data)>0){  
            $i=0;
            foreach($new_connection_list_data as $new_connection){
               
                $fnew_connection_data[$i] =  $new_connection;
                $user_role = Roles::Select('name')->where('id',$new_connection->role_id)->first(); 
                $fnew_connection_data[$i]['user_role']= isset($user_role->name)?$user_role->name:''; 
                $property_name = Property::Select('name')->where('user_id',$new_connection->id)->first(); 
                $fnew_connection_data[$i]['user_property_name']= isset($property_name->name)?$property_name->name:''; 
                $property_gallery_img = Property::Select('property_gallery.file')->join('property_gallery','property_gallery.property_id','=','table_properties.id')->where('table_properties.user_id',$new_connection->id)->first();                                                                                                              

                $propery_image =isset($property_gallery_img->file)?getPublicPropertyImage('public/assets/uploads/properties/'.$property_gallery_img->file):asset('public/assets/img/default_property.jpeg') ;
                $fnew_connection_data[$i]['user_property_image']=  $propery_image;
                $fnew_connection_data[$i]['user_image']=  isset($new_connection->image)?getPublicUserImage("public/assets/uploads/users/".$new_connection->image): asset('public/assets/img/user/default_user.png');
                $i++;
            }
        } 
        $follower_data;        
        $following_data;        
        $fnew_connection_data;      
        
        if($request->tab=="followers"){
            $return_data = $follower_data;
        }else{
            $return_data = $fnew_connection_data;
        }
        
        return $return_data;
    }




    public function connection_change_tab(Request $request){
         

        
        $membershipTier = isset(Auth::user()->membership_tier)?Auth::user()->membership_tier:0; 
        $user_id = isset(Auth::user()->id)?Auth::user()->id:0; 
                                 
        /*-----------geting all data ----------------*/


        $follower_user_ids =  Follower::select('follow_id')->where([['user_id','=',$user_id],['status','=','follow']])->distinct()->get();
        $following_user_ids =  Follower::select('user_id')->where([['follow_id','=',$user_id],['status','=','follow']])->distinct()->get();
        
        $follower_list_data = User::where([['status','!=','D']])->whereIn('id', $follower_user_ids)->orderBy('updated_at','DESC')->get();
        $following_list_data = User::where([['status','!=','D']])->whereIn('id', $following_user_ids)->orderBy('updated_at','DESC')->get();
        $new_connection_list_data = User::where([['status','!=','D']])->whereNotIn('id', $following_user_ids)->whereNotIn('id', $follower_user_ids)->where([['id','!=',$user_id]])->orderBy('updated_at','DESC')->get();
         

        $follower_data = array();
        $following_data = array();
        $fnew_connection_data = array();

/*------------Get Follower Data------------*/
        if(count($follower_list_data)>0){  
            $i=0;
            foreach($follower_list_data as $follower){
               
                $follower_data[$i] =  $follower;
                $user_role = Roles::Select('name')->where('id',$follower->role_id)->first(); 
                $follower_data[$i]['user_role']= isset($user_role->name)?$user_role->name:''; 
                $property_name = Property::Select('name')->where('user_id',$follower->id)->first(); 
                $follower_data[$i]['user_property_name']= isset($property_name->name)?$property_name->name:''; 
                $property_gallery_img = Property::Select('property_gallery.file')->join('property_gallery','property_gallery.property_id','=','table_properties.id')->where('table_properties.user_id',$follower->id)->first();                                                                                                              

                $propery_image =isset($property_gallery_img->file)?getPublicPropertyImage('public/assets/uploads/properties/'.$property_gallery_img->file):asset('public/assets/img/default_property.jpeg') ;
                $follower_data[$i]['user_property_image']=  $propery_image;
                $follower_data[$i]['user_image']=  isset($follower->image)?getPublicUserImage("public/assets/uploads/users/".$follower->image): asset('public/assets/img/user/default_user.png');
                $i++;
            }
        } 
/*------------Get  Following Data------------*/
        if(count($following_list_data)>0){  
            $i=0;
            foreach($following_list_data as $following){
                $following_data[$i] =  $following;
                $user_role = Roles::Select('name')->where('id',$following->role_id)->first(); 
                $following_data[$i]['user_role']= isset($user_role->name)?$user_role->name:''; 
                $property_name = Property::Select('name')->where('user_id',$following->id)->first(); 
                $following_data[$i]['user_property_name']= isset($property_name->name)?$property_name->name:''; 
                $property_gallery_img = Property::Select('property_gallery.file')->join('property_gallery','property_gallery.property_id','=','table_properties.id')->where('table_properties.user_id',$following->id)->first();                                                                                                              

                $propery_image =isset($property_gallery_img->file)?getPublicPropertyImage('public/assets/uploads/properties/'.$property_gallery_img->file):asset('public/assets/img/default_property.jpeg');
                $following_data[$i]['user_property_image']=  $propery_image;
                $following_data[$i]['user_image']=  isset($following->image)?getPublicUserImage("public/assets/uploads/users/".$following->image): asset('public/assets/img/user/default_user.png');
                $i++;
            }
        } 

/*------------Get New Connection Data------------*/
        if(count($new_connection_list_data)>0){  
            $i=0;
            foreach($new_connection_list_data as $new_connection){
                $fnew_connection_data[$i] =  $new_connection;
                $user_role = Roles::Select('name')->where('id',$new_connection->role_id)->first(); 
                $fnew_connection_data[$i]['user_role']= isset($user_role->name)?$user_role->name:''; 
                $property_name = Property::Select('name')->where('user_id',$new_connection->id)->first(); 
                $fnew_connection_data[$i]['user_property_name']= isset($property_name->name)?$property_name->name:''; 
                $property_gallery_img = Property::Select('property_gallery.file')->join('property_gallery','property_gallery.property_id','=','table_properties.id')->where('table_properties.user_id',$new_connection->id)->first();                                                                                                              

                $propery_image =isset($property_gallery_img->file)?getPublicPropertyImage('public/assets/uploads/properties/'.$property_gallery_img->file):asset('public/assets/img/default_property.jpeg') ;
                $fnew_connection_data[$i]['user_property_image']=  $propery_image;
                $fnew_connection_data[$i]['user_image']=  isset($new_connection->image)?getPublicUserImage("public/assets/uploads/users/".$new_connection->image): asset('public/assets/img/user/default_user.png');
                $i++;
            }
        } 
        $follower_data;        
        $following_data;        
        $fnew_connection_data;      
        
        if($request->tab=="followers"){
            $return_data = $follower_data;
        }elseif($request->tab=="new_connection"){
            $return_data = $fnew_connection_data;
        }else{
            $return_data =  $following_data; 
        }
        
        return $return_data;
    }

    
}
