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
use App\Models\Property;
use App\Models\PropertyGallery;
use App\Models\PropertySpecification;
use App\Models\UserPlace;
use App\Models\User;
use App\Models\UserExperience;
use App\Models\UserEducation;
use App\Models\Feed;

class FavouriteController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    // public function __construct()
    // {
    //     $this->middleware('auth');
    // }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        $membershipTier = isset(Auth::user()->membership_tier)?Auth::user()->membership_tier:0; 
        $data=array();
        $dataNearBy=array();
        $dataPopular=array();
        $propertyList = array();
        $min_price="";
        $max_price="";
        $min_area="";
        $max_area="";

        if($request->isMethod('POST'))
        {

            $filter_conditions ="";
            $filter_conditions .="table_properties.status!='D'";
            if(isset($request->name) && !empty($request->name)){
                $filter_conditions .=" AND table_properties.name like '%".$request->name."%'";
            }
            if(isset($request->location) && !empty($request->location)){
                $filter_conditions .=" AND table_properties.address like '%".$request->location."%'";
            }
            if(isset($request->bedroom) && !empty($request->bedroom)){
                $filter_conditions .=" AND table_pspecification.bedroom =".$request->bedroom;
            }
            if(isset($request->balcony) && !empty($request->balcony)){
                $filter_conditions .=" AND table_pspecification.balcony = '".$request->balcony."'";
            }
            if(isset($request->property_characteristics) && !empty($request->property_characteristics)){
                $filter_conditions .=" AND table_properties.family_type = '".$request->property_characteristics."'";
            }
            if(isset($request->pets) && !empty($request->pets)){
                $filter_conditions .=" AND table_pspecification.pets='".$request->pets."'";
            }
            if(isset($request->parking) && !empty($request->parking)){
                $filter_conditions .="AND table_pspecification.parking = '".$request->parking."'";
            }
            if(isset($request->listing_type) && !empty($request->listing_type)){
                $filter_conditions .=" AND table_roles.name = '".$request->listing_type."'";
            }
            if(isset($request->price_range) && !empty($request->price_range)){
                
                $alfer_remove = str_replace('$','',$request->price_range);
                $alfer_remove = str_replace('k','',$alfer_remove);
                $exploaded_price = explode('-',$alfer_remove); 
                $min_price = str_replace(' ', '', $exploaded_price[0]);
                $max_price = str_replace(' ', '', $exploaded_price[1]);            
                $propertyList = Property::join('table_pspecification','table_pspecification.property_id','=','table_properties.id')
                ->join('table_users','table_users.id','=','table_properties.user_id')
                ->join('table_roles','table_roles.id','=','table_users.role_id')
                ->whereRaw($filter_conditions)->whereBetween('table_properties.price',array($min_price,$max_price))->get();                
             
                $propertyListNearBy = Property::select('table_properties.*','table_pspecification.*',DB::raw('( 6371 * acos( cos( radians(table_properties.lat) ) 
                * cos( radians( user_places.lat ) ) 
                * cos( radians( user_places.long ) - radians(table_properties.long) ) + sin( radians(table_properties.lat) ) 
                * sin( radians( user_places.lat ) ) ) ) 
                AS calculated_distance'))
                ->join('table_pspecification','table_pspecification.property_id','=','table_properties.id')
                ->join('table_users','table_users.id','=','table_properties.user_id')
                ->join('user_places','user_places.user_id','=','table_users.id')
                ->join('table_roles','table_roles.id','=','table_users.role_id')
                ->whereRaw($filter_conditions)->orderBy('calculated_distance')->whereBetween('table_properties.price',array($min_price,$max_price))->get();  
               
                $propertyListPopular = Property::select(DB::raw('property_reviews.rating AS totalrating'),'table_properties.*','table_pspecification.*','table_properties.*')->join('property_reviews','property_reviews.property_id','=','table_properties.id')->join('table_pspecification','table_pspecification.property_id','=','table_properties.id')             ->join('table_users','table_users.id','=','table_properties.user_id')
                ->join('table_roles','table_roles.id','=','table_users.role_id')->orderBy('totalrating','DESC')->whereRaw($filter_conditions)->whereBetween('table_properties.price',array($min_price,$max_price))->get();                
            }
            if(isset($request->area) && !empty($request->area)){
                
                $alfer_remove_area = str_replace('$','',$request->area);
                $alfer_remove_area = str_replace('k','',$alfer_remove_area);
                $exploaded_area = explode('-',$alfer_remove_area); 
                $min_area = str_replace(' ', '', $exploaded_area[0]);
                $max_area = str_replace(' ', '', $exploaded_area[1]); 
                             
                $propertyList = Property::join('table_pspecification','table_pspecification.property_id','=','table_properties.id')
                ->join('table_users','table_users.id','=','table_properties.user_id')
                ->join('table_roles','table_roles.id','=','table_users.role_id')
                ->whereRaw($filter_conditions)->whereBetween('table_pspecification.area',array($min_area,$max_area))->get();                
             
                
                $propertyListNearBy = Property::select('table_properties.*','table_pspecification.*',DB::raw('( 6371 * acos( cos( radians(table_properties.lat) ) 
                * cos( radians( user_places.lat ) ) 
                * cos( radians( user_places.long ) - radians(table_properties.long) ) + sin( radians(table_properties.lat) ) 
                * sin( radians( user_places.lat ) ) ) ) 
                AS calculated_distance'))
                ->join('table_pspecification','table_pspecification.property_id','=','table_properties.id')
                ->join('table_users','table_users.id','=','table_properties.user_id')
                ->join('user_places','user_places.user_id','=','table_users.id')
                ->join('table_roles','table_roles.id','=','table_users.role_id')
                ->whereRaw($filter_conditions)->orderBy('calculated_distance')->whereBetween('table_pspecification.area',array($min_area,$max_area))->get();  
                
                $propertyListPopular = Property::select(DB::raw('property_reviews.rating AS totalrating'),'table_properties.*','table_pspecification.*','table_properties.*')->join('property_reviews','property_reviews.property_id','=','table_properties.id')->join('table_pspecification','table_pspecification.property_id','=','table_properties.id')             ->join('table_users','table_users.id','=','table_properties.user_id')
                ->join('table_roles','table_roles.id','=','table_users.role_id')->orderBy('totalrating','DESC')->whereRaw($filter_conditions)->whereBetween('table_pspecification.area',array($min_area,$max_area))->get();                
                
            }
            if(isset($request->price_range) && !empty($request->price_range) && isset($request->area) && !empty($request->area)){
                
                $alfer_remove_area = str_replace('$','',$request->area);
                $alfer_remove_area = str_replace('k','',$alfer_remove_area);
                $exploaded_area = explode('-',$alfer_remove_area); 
                $min_area = str_replace(' ', '', $exploaded_area[0]);
                $max_area = str_replace(' ', '', $exploaded_area[1]); 
                $alfer_remove = str_replace('$','',$request->price_range);
                $alfer_remove = str_replace('k','',$alfer_remove);
                $exploaded_price = explode('-',$alfer_remove); 
                $min_price = str_replace(' ', '', $exploaded_price[0]);
                $max_price = str_replace(' ', '', $exploaded_price[1]); 
                
                $propertyList = Property::join('table_pspecification','table_pspecification.property_id','=','table_properties.id')
                ->join('table_users','table_users.id','=','table_properties.user_id')
                ->join('table_roles','table_roles.id','=','table_users.role_id')
                ->whereRaw($filter_conditions)->whereBetween('table_properties.price',array($min_price,$max_price))->whereBetween('table_pspecification.area',array($min_area,$max_area))->get();                
             
                $propertyListNearBy = Property::select('table_properties.*','table_pspecification.*',DB::raw('( 6371 * acos( cos( radians(table_properties.lat) ) 
                * cos( radians( user_places.lat ) ) 
                * cos( radians( user_places.long ) - radians(table_properties.long) ) + sin( radians(table_properties.lat) ) 
                * sin( radians( user_places.lat ) ) ) ) 
                AS calculated_distance'))
                ->join('table_pspecification','table_pspecification.property_id','=','table_properties.id')
                ->join('table_users','table_users.id','=','table_properties.user_id')
                ->join('user_places','user_places.user_id','=','table_users.id')
                ->join('table_roles','table_roles.id','=','table_users.role_id')
                ->whereRaw($filter_conditions)->orderBy('calculated_distance')->whereBetween('table_properties.price',array($min_price,$max_price))->whereBetween('table_pspecification.area',array($min_area,$max_area))->get();  
               
                $propertyListPopular = Property::select(DB::raw('property_reviews.rating AS totalrating'),'table_properties.*','table_pspecification.*','table_properties.*')->join('property_reviews','property_reviews.property_id','=','table_properties.id')->join('table_pspecification','table_pspecification.property_id','=','table_properties.id')             ->join('table_users','table_users.id','=','table_properties.user_id')
                ->join('table_roles','table_roles.id','=','table_users.role_id')->orderBy('totalrating','DESC')->whereRaw($filter_conditions)->whereBetween('table_properties.price',array($min_price,$max_price))->whereBetween('table_pspecification.area',array($min_area,$max_area))->get();                

            }
            if(!isset($request->price_range) && empty($request->price_range) && !isset($request->area) && empty($request->area)){
                
                $propertyList = Property::join('table_pspecification','table_pspecification.property_id','=','table_properties.id')
                ->join('table_users','table_users.id','=','table_properties.user_id')
                ->join('table_roles','table_roles.id','=','table_users.role_id')->whereRaw($filter_conditions)->get();                
                $propertyListNearBy = Property::select('table_properties.*','table_pspecification.*',DB::raw('( 6371 * acos( cos( radians(table_properties.lat) ) 
                * cos( radians( user_places.lat ) ) 
                * cos( radians( user_places.long ) - radians(table_properties.long) ) + sin( radians(table_properties.lat) ) 
                * sin( radians( user_places.lat ) ) ) ) 
                AS calculated_distance'))
                ->join('table_pspecification','table_pspecification.property_id','=','table_properties.id')
                ->join('table_users','table_users.id','=','table_properties.user_id')
                ->join('user_places','user_places.user_id','=','table_users.id')
                ->join('table_roles','table_roles.id','=','table_users.role_id')
                ->whereRaw($filter_conditions)->orderBy('calculated_distance')->get();                             
                $propertyListPopular = Property::select(DB::raw('property_reviews.rating AS totalrating'),'table_properties.*','table_pspecification.*','table_properties.*')
                ->join('property_reviews','property_reviews.property_id','=','table_properties.id')
                ->join('table_pspecification','table_pspecification.property_id','=','table_properties.id')
                ->join('table_users','table_users.id','=','table_properties.user_id')
                ->join('table_roles','table_roles.id','=','table_users.role_id')
                ->orderBy('totalrating','DESC')->whereRaw($filter_conditions)->get();                
            }
                      
            if(count($propertyList)>0){
  
                $i=0;
                foreach($propertyList as $property){
                   
                    $data[$i] =  $property;
                    $data[$i]['property_specifications']= PropertySpecification::where('property_id',$property->id)->first();                 
                    $data[$i]['property_gallery']= $property->getPropertyThumnail;  
                    $propertyTotalRating =  $property->getPropertyRating;  
                    if(count($propertyTotalRating)>0){
                            $toralrating =0;
                            $noOfUserRate =0;
                            foreach($propertyTotalRating as $proRate){
                                $toralrating = $toralrating + $proRate->rating;
                                $noOfUserRate = $noOfUserRate + 1;
                            }
                    }                                    
                    $data[$i]['property_rate']= round($toralrating/$noOfUserRate);                                       
                    $i++;
                }
            }
            if(count($propertyListNearBy)>0){  
                $i=0;
                foreach($propertyListNearBy as $propertyNearBy){
                   
                    $dataNearBy[$i] =  $propertyNearBy;
                    $dataNearBy[$i]['property_specifications']= PropertySpecification::where('property_id',$propertyNearBy->id)->first();                 
                    $dataNearBy[$i]['property_gallery']= $propertyNearBy->getPropertyThumnail;  
                    $propertyTotalRating =  $propertyNearBy->getPropertyRating;  
                    if(count($propertyTotalRating)>0){
                            $toralrating =0;
                            $noOfUserRate =0;
                            foreach($propertyTotalRating as $proRate){
                                $toralrating = $toralrating + $proRate->rating;
                                $noOfUserRate = $noOfUserRate + 1;
                            }
                    }                                    
                    $dataNearBy[$i]['property_rate']= round($toralrating/$noOfUserRate);                                       
                    $i++;
                }
            } 
            if(count($propertyListPopular)>0){  
                $i=0;
                foreach($propertyListPopular as $propertyPopular){
                   
                    $dataPopular[$i] =  $propertyPopular;
                    $dataPopular[$i]['property_specifications']= PropertySpecification::where('property_id',$propertyPopular->id)->first();                 
                    $dataPopular[$i]['property_gallery']= $propertyPopular->getPropertyThumnail;                                       
                    $propertyTotalRating =  $propertyPopular->getPropertyRating;  
                    if(count($propertyTotalRating)>0){
                            $toralrating =0;
                            $noOfUserRate =0;
                            foreach($propertyTotalRating as $proRate){
                                $toralrating = $toralrating + $proRate->rating;
                                $noOfUserRate = $noOfUserRate + 1;
                            }
                    }                                    
                    $dataPopular[$i]['property_rate']= round($toralrating/$noOfUserRate);     
                    $i++;
                }
            } 
        }else{

            $propertyListNearBy = Property::select('table_properties.*',DB::raw('( 6371 * acos( cos( radians(table_properties.lat) ) 
            * cos( radians( user_places.lat ) ) 
            * cos( radians( user_places.long ) - radians(table_properties.long) ) + sin( radians(table_properties.lat) ) 
            * sin( radians( user_places.lat ) ) ) ) 
            AS calculated_distance'))            
            ->join('table_users','table_users.id','=','table_properties.user_id')
            ->join('user_places','user_places.user_id','=','table_users.id')
            ->orderBy('calculated_distance')->get();                           

            $propertyList = Property::get();
            $propertyListPopular = Property::select(DB::raw('property_reviews.rating AS totalrating'),'table_properties.*')->join('property_reviews','property_reviews.property_id','=','table_properties.id')->orderBy('totalrating','DESC')->get();
            
            if(count($propertyList)>0){  
                $i=0;
                foreach($propertyList as $property){
                   
                    $data[$i] =  $property;
                    $data[$i]['property_specifications']= PropertySpecification::where('property_id',$property->id)->first();                 
                    $data[$i]['property_gallery']= $property->getPropertyThumnail;    
                    $propertyTotalRating =  $property->getPropertyRating;  
                    if(count($propertyTotalRating)>0){
                            $toralrating =0;
                            $noOfUserRate =0;
                            foreach($propertyTotalRating as $proRate){
                                $toralrating = $toralrating + $proRate->rating;
                                $noOfUserRate = $noOfUserRate + 1;
                            }
                    }                                    
                    $data[$i]['property_rate']= round($toralrating/$noOfUserRate);                                            
                    $i++;
                }
            }                                    
            if(count($propertyListNearBy)>0){  
                $i=0;
                foreach($propertyListNearBy as $propertyNearBy){
                   
                    $dataNearBy[$i] =  $propertyNearBy;
                    $dataNearBy[$i]['property_specifications']= PropertySpecification::where('property_id',$propertyNearBy->id)->first();                 
                    $dataNearBy[$i]['property_gallery']= $propertyNearBy->getPropertyThumnail;   
                    $propertyTotalRating =  $propertyNearBy->getPropertyRating;  
                    if(count($propertyTotalRating)>0){
                            $toralrating =0;
                            $noOfUserRate =0;
                            foreach($propertyTotalRating as $proRate){
                                $toralrating = $toralrating + $proRate->rating;
                                $noOfUserRate = $noOfUserRate + 1;
                            }
                    }                                    
                    $dataNearBy[$i]['property_rate']= round($toralrating/$noOfUserRate);                                       
                    $i++;
                }
            } 
            if(count($propertyListPopular)>0){
                $i=0;
                foreach($propertyListPopular as $propertyPopular){
                   
                    $dataPopular[$i] =  $propertyPopular;
                    $dataPopular[$i]['property_specifications']= PropertySpecification::where('property_id',$propertyPopular->id)->first();                 
                    $dataPopular[$i]['property_gallery']= $propertyPopular->getPropertyThumnail;                                       
                    $i++;
                }
            } 
        }
        $propertyList = $data;
        $propertyListNearBy = $dataNearBy;
        $propertyListPopular= $dataPopular;
        session()->flashInput($request->input());

        
        $filterlist = Property::where([['status','!=','D']])->get();
        if($membershipTier == 0 || $membershipTier == 1){
            
            $filterlist = Property::where([['status','!=','D'],['trending','!=','mls']])->get();
        }
      






        $feed_list = Feed::where([['status','!=','D']])->orderBy('updated_at','DESC')->get();
    
        $feeddata = array();
        if(count($feed_list)>0){  
            $i=0;
            foreach($feed_list as $feed){
               
                $feeddata[$i] =  $feed;
                $feeddata[$i]['user_details']=  $feed->getUserDetails; 
                if(!empty($feed->property_id)){

                    $feeddata[$i]['property_details']= Property::where('id',$feed->property_id)->first();   
                    $feeddata[$i]['property_gallery']= PropertyGallery::where([['property_id','=',$feed->property_id],['thumb_file','!=',null]])->get();   
                    
                }else{

                    $feeddata[$i]['property_details']= array();
                }  
                                                                                            
                $i++;
            }
        } 
        $feed_list = $feeddata;

        if(auth()->user()){
            if(auth()->user()->role_id == 2){
                return view('seller/home',compact('propertyList','propertyListNearBy','propertyListPopular','filterlist','min_price','max_price','min_area','max_area','feed_list'));
            }elseif(auth()->user()->role_id == 3){
                return view('seller/home',compact('propertyList','propertyListNearBy','propertyListPopular','filterlist','min_price','max_price','min_area','max_area','feed_list'));
            }else{
                return view('buyer/home',compact('propertyList','propertyListNearBy','propertyListPopular','filterlist','min_price','max_price','min_area','max_area','feed_list'));
            }
        }else{
            return view('buyer/home',compact('propertyList','propertyListNearBy','propertyListPopular','filterlist','min_price','max_price','min_area','max_area','feed_list'));
        }
    }

    public function coming()
    {
        return view('home');
    }

    public function buyerHome()
    {
        $id=auth()->user()->id;
        $view_response=$this->landingPageList($id=null);
        return view('buyer/home',compact('view_response'));
    }

    public function sellerHome()
    {
        $id=auth()->user()->id;
        $view_response=$this->landingPageList($id);    
        return view('seller.home',compact('view_response'));
    }

    public function agentHome()
    {
        $id=auth()->user()->id;
        $view_response=$this->landingPageList($id);    
        return view('agent.home',compact('view_response'));
    }


    public function landingPageList($id=null)
    {
        $properties = Property::where(['status'=>'A']);

        if($id==null)
        {
            $properties=$properties->get();

        }
        else
        {
            $properties=$properties->where('user_id',$id)->get();

        }
        $view_response=[];
        foreach($properties as $row)
        {
            $gallery_details= PropertyGallery::where('property_id',$row->id)->first();
            $specifications = PropertySpecification::where('property_id',$row->id)->first();
            $result['id']=$row->id;
            $result['property_name']=@$row->name;
            $result['property_address']=@$row->address;
            $result['property_price']=@$row->price;
            $result['property_description']=@$row->desc;
            $result['property_image']=@$gallery_details->file;
            $result['bedroom']=@$specifications->bedroom;
            $result['kitchen']=@$specifications->kitchen;
            $result['bathroom']=@$specifications->bathroom;
            $result['area']    =@$specifications->area;
            $result['pets'] =@$specifications->pets;
            $result['updated_at'] =@$row->updated_at;
            $result['book_status'] =@$row->book_status;


            $view_response[]=$result;

        }
        return $view_response;
    }

    /******* my profile************************/
    public function myProfile(Request $request)
    {
      $user_id=auth()->user()->id;
      $properties=$this->landingPageList($user_id);
      $user_address=UserPlace::where('user_id',$user_id)->orderBy('id')->first();
      $followers= User::where('id',$user_id)->with('myFollowers')->first();
      $total_followers = count($followers->myFollowers);
      $experience_details =UserExperience::where('user_id',$user_id)->orderBy('id','DESC')->get();
      $education_details =UserEducation::where('user_id',$user_id)->orderBy('id','DESC')->get();
      $response=[];
      foreach($followers->myFollowers as $row)
      {
        $user_details=User::where('id',$row->follow_id)->first();
        $experience=UserExperience::where('user_id',$row->follow_id)->orderBy('id','DESC')->first();

        $result['id']= $user_details->id;
        $result['full_name']=$user_details->fname .' '.$user_details->lname;
        $result['image']=@$user_details->image;
        $result['role_id']=$user_details->role_id;
        $result['company']=@$experience->company;

        $response[]=$result;

      }

      return view('profile.my_profile',compact('properties','user_address','total_followers','experience_details','education_details','response'));
    }
}
