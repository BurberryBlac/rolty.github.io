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

class PropertViewAllController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    /**
     * Show the Near By Property.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function nearBy(Request $request)
    {
        $membershipTier = isset(Auth::user()->membership_tier)?Auth::user()->membership_tier:0;         
        $dataNearBy=array();           
        $toralrating =0;
        $noOfUserRate =0;
    
            $propertyListNearBy = Property::select('table_properties.*',DB::raw('( 6371 * acos( cos( radians(table_properties.lat) ) 
            * cos( radians( user_places.lat ) ) 
            * cos( radians( user_places.long ) - radians(table_properties.long) ) + sin( radians(table_properties.lat) ) 
            * sin( radians( user_places.lat ) ) ) ) 
            AS calculated_distance'))            
            ->join('table_users','table_users.id','=','table_properties.user_id')
            ->join('user_places','user_places.user_id','=','table_users.id')
            ->orderBy('calculated_distance')->get();                                   
                                             
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
                    $i++;
                }
            } 
        
        $propertyList = $dataNearBy;
        
        session()->flashInput($request->input());
        $property_type ="Near By";
        return view('viewallproperty/index',compact('propertyList','property_type'));        
    }

   
    /**
     * Show the Popular By Property.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function popular(Request $request)
    {
        $membershipTier = isset(Auth::user()->membership_tier)?Auth::user()->membership_tier:0; 

        $dataPopular=array();      
        $toralrating =0;
        $noOfUserRate =0;    
          
            $propertyListPopular = Property::select(DB::raw('property_reviews.rating AS totalrating'),'table_properties.*')->join('property_reviews','property_reviews.property_id','=','table_properties.id')->orderBy('totalrating','DESC')->get();
                                                           
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
                    $i++;
                }
            } 
     
        $propertyList= $dataPopular;
        session()->flashInput($request->input());
        $property_type ="Popular";                          
        return view('viewallproperty/index',compact('propertyList','property_type'));
        
    }

     
    /**
     * Show the Off Market Property.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function offMarket(Request $request)
    {
        $membershipTier = isset(Auth::user()->membership_tier)?Auth::user()->membership_tier:0; 
        $data=array();     
        $propertyList = array();       
        $toralrating =0;
        $noOfUserRate =0;                           

            $propertyList = Property::where('trending','off-market')->get();
                      
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
                    $i++;
                }
            }                                    
           
        $propertyList = $data;       
        session()->flashInput($request->input());
        $property_type ="Off Market";    
        return view('viewallproperty/index',compact('propertyList','property_type'));        
    }


     /**
     * Show the upcomming Property.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function upcomming(Request $request)
    {
        $membershipTier = isset(Auth::user()->membership_tier)?Auth::user()->membership_tier:0; 
        $data=array();     
        $propertyList = array();       
        $toralrating =0;
        $noOfUserRate =0;                           

            $propertyList = Property::where('trending','upcoming')->get();
                      
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
                    $i++;
                }
            }                                    
           
        $propertyList = $data;       
        session()->flashInput($request->input());
        $property_type ="Upcomming";      
        return view('viewallproperty/index',compact('propertyList','property_type'));        
    }
      /**
     * Show the mls Property.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function mls(Request $request)
    {
        $membershipTier = isset(Auth::user()->membership_tier)?Auth::user()->membership_tier:0; 
        $data=array();     
        $propertyList = array();       
        $toralrating =0;
        $noOfUserRate =0;                           

            $propertyList = Property::where('trending','mls')->get();
                      
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
                    $i++;
                }
            }                                    
           
        $propertyList = $data;       
        session()->flashInput($request->input());
        $property_type ="MLS";      
        return view('viewallproperty/index',compact('propertyList','property_type'));        
    }
}
