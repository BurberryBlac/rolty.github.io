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
use App\Models\Event;
use App\Models\Offer;
use App\Models\PropertyFavourite;


class HomeController extends Controller
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
        $user_id = isset(Auth::user()->id)?Auth::user()->id:0; 
        $data=array();
        $dataNearBy=array();
        $dataPopular=array();
        $propertyList = array();
        $min_price="";
        $max_price="";
        $min_area="";
        $max_area="";
        $toralrating =0;
        $noOfUserRate =0;
        
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
                     
                $max_price = $max_price."000";
                
                $propertyList = Property::select('table_properties.*')
                ->join('table_pspecification','table_pspecification.property_id','=','table_properties.id')
                ->join('table_users','table_users.id','=','table_properties.user_id')
                ->join('table_roles','table_roles.id','=','table_users.role_id')
                //->join('property_reviews','property_reviews.property_id','=','table_properties.id')
                //->groupBy('table_properties.id')
                ->whereRaw($filter_conditions)
                ->whereBetween('table_properties.price',array($min_price,$max_price))
                ->distinct()
                ->get();                
             
                $propertyListNearBy = Property::select('table_properties.*','table_pspecification.*',DB::raw('( 6371 * acos( cos( radians(table_properties.lat) ) 
                * cos( radians( user_places.lat ) ) 
                * cos( radians( user_places.long ) - radians(table_properties.long) ) + sin( radians(table_properties.lat) ) 
                * sin( radians( user_places.lat ) ) ) ) 
                AS calculated_distance'))
                ->join('table_pspecification','table_pspecification.property_id','=','table_properties.id')
                ->join('table_users','table_users.id','=','table_properties.user_id')
                ->join('user_places','user_places.user_id','=','table_users.id')
                ->join('table_roles','table_roles.id','=','table_users.role_id')
                ->whereRaw($filter_conditions)
                ->orderBy('calculated_distance')
                ->whereBetween('table_properties.price',array($min_price,$max_price))
                ->distinct()
                ->get();  
                
                $propertyListPopular = Property::select('table_properties.*','table_pspecification.*')                
                ->join('table_pspecification','table_pspecification.property_id','=','table_properties.id') 
                ->join('table_users','table_users.id','=','table_properties.user_id')
                ->join('table_roles','table_roles.id','=','table_users.role_id')
                //->join('property_reviews','property_reviews.property_id','=','table_properties.id')
                //->groupBy('table_properties.id')
                //->orderBy('property_rate','DESC')
                ->whereRaw($filter_conditions)
                ->whereBetween('table_properties.price',array($min_price,$max_price))
                ->distinct()
                ->get();  
                             
            }
            if(isset($request->area) && !empty($request->area)){
                
                $alfer_remove_area = str_replace('$','',$request->area);
                $alfer_remove_area = str_replace('k','',$alfer_remove_area);
                $exploaded_area = explode('-',$alfer_remove_area); 
                $min_area = str_replace(' ', '', $exploaded_area[0]);
                $max_area = str_replace(' ', '', $exploaded_area[1]); 
                $max_area = $max_area."000";  

                $propertyList = Property::select('table_properties.*')
                ->join('table_pspecification','table_pspecification.property_id','=','table_properties.id')
                ->join('table_users','table_users.id','=','table_properties.user_id')
                ->join('table_roles','table_roles.id','=','table_users.role_id')
                //->join('property_reviews','table_properties.id','=','property_reviews.property_id')
                //->groupBy('table_properties.id')
                ->whereRaw($filter_conditions)
                ->whereBetween('table_pspecification.area',array($min_area,$max_area))
                ->distinct()
                ->get();                
             
                
                $propertyListNearBy = Property::select('table_properties.*','table_pspecification.*',DB::raw('( 6371 * acos( cos( radians(table_properties.lat) ) 
                * cos( radians( user_places.lat ) ) 
                * cos( radians( user_places.long ) - radians(table_properties.long) ) + sin( radians(table_properties.lat) ) 
                * sin( radians( user_places.lat ) ) ) ) 
                AS calculated_distance'))
                ->join('table_pspecification','table_pspecification.property_id','=','table_properties.id')
                ->join('table_users','table_users.id','=','table_properties.user_id')
                ->join('user_places','user_places.user_id','=','table_users.id')
                ->join('table_roles','table_roles.id','=','table_users.role_id')
                ->whereRaw($filter_conditions)
                ->orderBy('calculated_distance')
                ->whereBetween('table_pspecification.area',array($min_area,$max_area))
                ->distinct()
                ->get();  
                
                $propertyListPopular = Property::select('table_properties.*','table_pspecification.*')                
                ->join('table_pspecification','table_pspecification.property_id','=','table_properties.id')
                ->join('table_users','table_users.id','=','table_properties.user_id')
                ->join('table_roles','table_roles.id','=','table_users.role_id')
                //->join('property_reviews','property_reviews.property_id','=','table_properties.id')
                //->groupBy('table_properties.id')
                //->orderBy('property_rate','DESC')
                ->whereRaw($filter_conditions)
                ->whereBetween('table_pspecification.area',array($min_area,$max_area))
                ->distinct()
                ->get();                
                
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
                
                $max_area = $max_area."000";   
                $max_price = $max_price."000"; 

                $propertyList = Property::select('table_properties.*')
                ->join('table_pspecification','table_pspecification.property_id','=','table_properties.id')
                ->join('table_users','table_users.id','=','table_properties.user_id')
                ->join('table_roles','table_roles.id','=','table_users.role_id')
                //->join('property_reviews','table_properties.id','=','property_reviews.property_id')
                //->groupBy('table_properties.id')
                ->whereRaw($filter_conditions)
                ->whereBetween('table_properties.price',array($min_price,$max_price))
                ->whereBetween('table_pspecification.area',array($min_area,$max_area))
                ->distinct()
                ->get();                
             
                $propertyListNearBy = Property::select('table_properties.*','table_pspecification.*',DB::raw('( 6371 * acos( cos( radians(table_properties.lat) ) 
                * cos( radians( user_places.lat ) ) 
                * cos( radians( user_places.long ) - radians(table_properties.long) ) + sin( radians(table_properties.lat) ) 
                * sin( radians( user_places.lat ) ) ) ) 
                AS calculated_distance'))
                ->join('table_pspecification','table_pspecification.property_id','=','table_properties.id')
                ->join('table_users','table_users.id','=','table_properties.user_id')
                ->join('user_places','user_places.user_id','=','table_users.id')
                ->join('table_roles','table_roles.id','=','table_users.role_id')
                ->whereRaw($filter_conditions)->orderBy('calculated_distance')
                ->whereBetween('table_properties.price',array($min_price,$max_price))
                ->whereBetween('table_pspecification.area',array($min_area,$max_area))
                ->distinct()
                ->get();  
               
                $propertyListPopular = Property::select('table_properties.*','table_pspecification.*')                
                ->join('table_pspecification','table_pspecification.property_id','=','table_properties.id')
                ->join('table_users','table_users.id','=','table_properties.user_id')
                ->join('table_roles','table_roles.id','=','table_users.role_id')
                //->join('property_reviews','property_reviews.property_id','=','table_properties.id')
               // ->groupBy('table_properties.id')
                //->orderBy('property_rate','DESC')
                ->whereRaw($filter_conditions)
                ->whereBetween('table_properties.price',array($min_price,$max_price))
                ->whereBetween('table_pspecification.area',array($min_area,$max_area))
                ->distinct()
                ->get();                

            }
            if(!isset($request->price_range) && empty($request->price_range) && !isset($request->area) && empty($request->area)){
                
                $propertyList = Property::select('table_properties.*')
                ->join('table_pspecification','table_pspecification.property_id','=','table_properties.id')
                ->join('table_users','table_users.id','=','table_properties.user_id')
                ->join('table_roles','table_roles.id','=','table_users.role_id')
                ->join('property_reviews','table_properties.id','=','property_reviews.property_id')
               // ->groupBy('table_properties.id')
                ->whereRaw($filter_conditions)
                ->distinct()
                ->get(); 
                
                $propertyListNearBy = Property::select('table_properties.*','table_pspecification.*',DB::raw('( 6371 * acos( cos( radians(table_properties.lat) ) 
                * cos( radians( user_places.lat ) ) 
                * cos( radians( user_places.long ) - radians(table_properties.long) ) + sin( radians(table_properties.lat) ) 
                * sin( radians( user_places.lat ) ) ) ) 
                AS calculated_distance'))
                ->join('table_pspecification','table_pspecification.property_id','=','table_properties.id')
                ->join('table_users','table_users.id','=','table_properties.user_id')
                ->join('user_places','user_places.user_id','=','table_users.id')
                ->join('table_roles','table_roles.id','=','table_users.role_id')
                ->whereRaw($filter_conditions)
                ->orderBy('calculated_distance')
                ->distinct()
                ->get();    
                
                $propertyListPopular = Property::select('table_properties.*','table_pspecification.*')                
                ->join('table_pspecification','table_pspecification.property_id','=','table_properties.id')
                ->join('table_users','table_users.id','=','table_properties.user_id')
                ->join('table_roles','table_roles.id','=','table_users.role_id')
                //->join('property_reviews','table_properties.id','=','property_reviews.property_id')
                //->groupBy('table_properties.id')
                //->orderBy('property_rate','DESC')
                ->whereRaw($filter_conditions)
                ->distinct()
                ->get();                 
                          
            }
               
            if(count($propertyList)>0){
  
                $i=0;
                foreach($propertyList as $property){
                   
                    $data[$i] =  $property;
                    $data[$i]['property_specifications']= PropertySpecification::where('property_id',$property->id)->first();                 
                    $data[$i]['property_gallery']= $property->getPropertyThumnail;  
                    $propertyTotalRating =  $property->getPropertyRating;  
                 
                    if(count($propertyTotalRating)>0){
                         
                        foreach($propertyTotalRating as $proRate){
                            $toralrating = $toralrating + $proRate->rating;
                            $noOfUserRate = $noOfUserRate + 1;
                        }
                      $rateing =  $toralrating/$noOfUserRate;
                        }else{
                            $rateing =0;
                        }                                    
                     $data[$i]['property_rate']= round($rateing);     
                     $data[$i]['favourite']= PropertyFavourite::where([['user_id','=',$user_id],['property_id','=',$property->id]])->first();                                                                      
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
                         
                        foreach($propertyTotalRating as $proRate){
                            $toralrating = $toralrating + $proRate->rating;
                            $noOfUserRate = $noOfUserRate + 1;
                        }
                      $rateing =  $toralrating/$noOfUserRate;
                        }else{
                            $rateing =0;
                        }                                    
                     $dataNearBy[$i]['property_rate']= round($rateing);     
                     $dataNearBy[$i]['favourite']= PropertyFavourite::where([['user_id','=',$user_id],['property_id','=',$propertyNearBy->id]])->first();                                                                       
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
                         
                        foreach($propertyTotalRating as $proRate){
                            $toralrating = $toralrating + $proRate->rating;
                            $noOfUserRate = $noOfUserRate + 1;
                        }
                      $rateing =  $toralrating/$noOfUserRate;
                        }else{
                            $rateing =0;
                        }                                    
                     $dataPopular[$i]['property_rate']= round($rateing);    
                     $dataPopular[$i]['favourite']= PropertyFavourite::where([['user_id','=',$user_id],['property_id','=',$propertyPopular->id]])->first();                                                                      
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
           // ->join('property_reviews','table_properties.id','=','property_reviews.property_id')                                
            ->orderBy('calculated_distance')
            ->distinct()
            ->get();                           
           
            $propertyList = Property::select(DB::raw('AVG(property_reviews.rating) as `property_rate`'),'table_properties.*')
            ->leftjoin('property_reviews','table_properties.id','=','property_reviews.property_id')
           // ->groupBy('table_properties.id')            
            ->get();

            
            $propertyListPopular = Property::select(DB::raw('AVG(property_reviews.rating) as `property_rate`'),'table_properties.*')
            ->leftjoin('property_reviews','table_properties.id','=','property_reviews.property_id')
            //->groupBy('table_properties.id')
            ->orderBy('property_rate','DESC')
            ->get();
            
            if(count($propertyList)>0){  
                $i=0;
                foreach($propertyList as $property){
                   
                    $data[$i] =  $property;
                    $data[$i]['property_specifications']= PropertySpecification::where('property_id',$property->id)->first();                 
                    $data[$i]['property_gallery']= $property->getPropertyThumnail;    
                //     $propertyTotalRating =  $property->getPropertyRating;  
                //     if(count($propertyTotalRating)>0){
                         
                //         foreach($propertyTotalRating as $proRate){
                //             $toralrating = $toralrating + $proRate->rating;
                //             $noOfUserRate = $noOfUserRate + 1;
                //         }
                //       $rateing =  $toralrating/$noOfUserRate;
                // }else{
                //     $rateing =0;
                // }                                    
                // $data[$i]['property_rate']= round($rateing);   
                $data[$i]['favourite']= PropertyFavourite::where([['user_id','=',$user_id],['property_id','=',$property->id]])->first();                                                                      
                    $i++;
                }
            }                                    
            if(count($propertyListNearBy)>0){  
                $i=0;
                foreach($propertyListNearBy as $propertyNearBy){
                   
                    $dataNearBy[$i] =  $propertyNearBy;
                    $dataNearBy[$i]['property_specifications']= PropertySpecification::where('property_id',$propertyNearBy->id)->first();                 
                    $dataNearBy[$i]['property_gallery']= $propertyNearBy->getPropertyThumnail;   
                //     $propertyTotalRating =  $propertyNearBy->getPropertyRating;  
                //     if(count($propertyTotalRating)>0){
                         
                //         foreach($propertyTotalRating as $proRate){
                //             $toralrating = $toralrating + $proRate->rating;
                //             $noOfUserRate = $noOfUserRate + 1;
                //         }
                //       $rateing =  $toralrating/$noOfUserRate;
                // }else{
                //     $rateing =0;
                // }                                    
                // $dataNearBy[$i]['property_rate']= round($rateing);                                       
                $dataNearBy[$i]['favourite']= PropertyFavourite::where([['user_id','=',$user_id],['property_id','=',$propertyNearBy->id]])->first();                                                                     
                    $i++;
                }
            } 
            if(count($propertyListPopular)>0){
                $i=0;
                foreach($propertyListPopular as $propertyPopular){
                   
                    $dataPopular[$i] =  $propertyPopular;
                    $dataPopular[$i]['property_specifications']= PropertySpecification::where('property_id',$propertyPopular->id)->first();                 
                    $dataPopular[$i]['property_gallery']= $propertyPopular->getPropertyThumnail;                                       
                    // $propertyTotalRating =  $propertyPopular->getPropertyRating;  
                    // if(count($propertyTotalRating)>0){
                         
                    //         foreach($propertyTotalRating as $proRate){
                    //             $toralrating = $toralrating + $proRate->rating;
                    //             $noOfUserRate = $noOfUserRate + 1;
                    //         }
                    //       $rateing =  $toralrating/$noOfUserRate;
                    // }else{
                    //     $rateing =0;
                    // }                                    
                    // $dataPopular[$i]['property_rate']= round($rateing); 
                    $dataPopular[$i]['favourite']= PropertyFavourite::where([['user_id','=',$user_id],['property_id','=',$propertyPopular->id]])->first();                                                                       
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


        $upcommingCount = Property::where('trending','upcoming')->count(); 
        $mlsCount = Property::where('trending','mls')->count(); 
        $offMarketCount= Property::where('trending','off-market')->count(); 

        if(auth()->user()){
            if(auth()->user()->role_id == 2){
                return view('seller/home',compact('propertyList','propertyListNearBy','propertyListPopular','filterlist','min_price','max_price','min_area','max_area','feed_list','upcommingCount','mlsCount','offMarketCount'));
            }elseif(auth()->user()->role_id == 3){
                return view('seller/home',compact('propertyList','propertyListNearBy','propertyListPopular','filterlist','min_price','max_price','min_area','max_area','feed_list','upcommingCount','mlsCount','offMarketCount'));
            }else{
                return view('buyer/home',compact('propertyList','propertyListNearBy','propertyListPopular','filterlist','min_price','max_price','min_area','max_area','feed_list','upcommingCount','mlsCount','offMarketCount'));
            }
        }else{
            return view('buyer/home',compact('propertyList','propertyListNearBy','propertyListPopular','filterlist','min_price','max_price','min_area','max_area','feed_list','upcommingCount','mlsCount','offMarketCount'));
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


        $feed_list = Feed::where('user_id',auth()->user()->id)->where([['status','!=','D']])->orderBy('updated_at','DESC')->get();

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
        $propert_list = Property::where('user_id',auth()->user()->id)->get();
        $event_list = Event::where('user_id',auth()->user()->id)->get();

      return view('profile.my_profile',compact('properties','user_address','total_followers','experience_details','education_details','response','feed_list','propert_list','event_list'));
    }

    /*************other Profile***********/
    public function otherProfile(Request $request,$id)
    {

          $properties=$this->landingPageList($id);
          $user_address=UserPlace::where('user_id',$id)->orderBy('id')->first();
          $user_data=User::where('id',$id)->first();
          $followers= User::where('id',$id)->with('myFollowers')->first();
          $total_followers = count($followers->myFollowers);
          $experience_details =UserExperience::where('user_id',$id)->orderBy('id','DESC')->get();
          $education_details =UserEducation::where('user_id',$id)->orderBy('id','DESC')->get();
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
        $feed_list = Feed::where([['status','!=','D']])->orderBy('updated_at','DESC')->get();

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
        $event_list = Event::get();
        $filterlist = Property::where([['status','!=','D']])->get();

          return view('profile.other_profile',compact('properties','user_address','total_followers','experience_details','education_details','response','feed_list','propert_list','event_list','user_data'));
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
            $successmsg = 'Post successfully updated.';

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
            $successmsg = 'Post successfully added.';
        }

        $feed->save();
        Toastr::success($successmsg,'Success');
        return redirect('my-profile');
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
        Toastr::success('Post deleted Successfully!', 'Success', ['timeOut' => 5000]);
        return redirect('my-profile');      
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
            return redirect('home');

        }
    }


    /****************** chnage Favourite Status***********/
    public function chnageFavouriteStatus(Request $request)
    {
              
        $user_id = Auth::user()->id;
        if(empty($user_id)){
            return redirect('login');
        }
        if(isset($request->property_id) && !empty($request->property_id) && !empty($user_id)){

            $favourite = PropertyFavourite::where([['user_id','=',$user_id],['property_id','=',$request->property_id]])->first();
           
            if(!empty($favourite)){

                $status_for_change = 1;
                if($favourite->is_favourite==1){
                    $status_for_change = 0;
                }
                $favourite->is_favourite = $status_for_change;
                $favourite->save();
                return $status_for_change;
            }else{

                $favourite = new PropertyFavourite();
                $favourite->user_id = $user_id;
                $favourite->property_id = $request->property_id;
                $favourite->is_favourite = 1;
                $favourite->save();
                return 1;
            }
        }
        return "something went wrong";
    }
    
    
}
