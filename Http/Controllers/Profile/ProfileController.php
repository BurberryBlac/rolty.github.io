<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserExperience;
use App\Models\User;
use App\Models\UserEducation;
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

class ProfileController extends Controller
{
    /****** Add work Place ***********/
    public function addWorkPlace(Request $request)
    {
        if($request->isMethod('post'))
        {
            $experience               = new UserExperience();
            $experience->user_id      = auth()->user()->id;
            $experience->designation  = trim($request->designation);
            $experience->company      = trim($request->company);
            $experience->description  = trim($request->description);
            $experience->work_from    =$request->work_year_from.'-'.$request->work_month_from.'-'.$request->work_day_from;
            if(empty($request->work_year_to))
            {
                $experience->work_to    ='present';
            }
            else
            {
                $experience->work_to    =$request->work_year_to.'-'.$request->work_month_to.'-'.$request->work_day_to;
            }
            $experience->status         ='A';
            $experience->address         =trim($request->address);
            $experience->lat=trim($request->work_lat);
            $experience->long=trim($request->work_long);

            $experience->save();
            Toastr::success('Work added Successfull', 'Success', ['timeOut' => 5000]);
            return redirect('my-profile');
            
        }
    }

    /************* work Place details*********/

    public function getWorkDetails(Request $request)
    {
        $result = UserExperience::where(['id'=>$request->id,'user_id'=>auth()->user()->id])->first();

        $details['id']=$result->id;
        $details['designation']=@$result->designation;
        $details['description']=@$result->description;
        $details['company']=@$result->company;


        $details['address']    =@$result->address;
        $details['work_year_from']=date('Y', strtotime($result->work_from));
        $details['work_month_from']=date('m', strtotime($result->work_from));
        $details['work_day_from']=date('d', strtotime($result->work_from));
        $details['work_year_to']=date('Y', strtotime($result->work_to));
        $details['work_month_to']=date('m', strtotime($result->work_to));
        $details['work_day_to']=date('d', strtotime($result->work_to));

        return Response::json(['details'=>$details,'status' => 200,'message' => 'work details fetch Successfully!.']);
    }

    /************************* Edit work Place details*********/
    public function editWorkPlace(Request $request)
    {
        if($request->isMethod('post'))
        {
            $experience               = UserExperience::find($request->work_id);
            $experience->designation  = trim($request->designation);
            $experience->company      = trim($request->company);
            $experience->description  = trim($request->description);
            $experience->work_from    =$request->work_year_from.'-'.$request->work_month_from.'-'.$request->work_day_from;
            if(empty($request->work_year_to))
            {
                $experience->work_to    ='present';
            }
            else
            {
                $experience->work_to    =$request->work_year_to.'-'.$request->work_month_to.'-'.$request->work_day_to;
            }
            $experience->address         =trim($request->address);
            $experience->lat=trim($request->work_lat);
            $experience->long=trim($request->work_long);
            $experience->status         ='A';
            $experience->save();
             Toastr::success('Work details updated Successfull', 'Success', ['timeOut' => 5000]);
            return redirect('my-profile');
            

        }
    }


    /******************add School Or/ College********/
    public function addEducationDetails(Request $request)
    {
        if($request->isMethod('post'))
        {
            $education = new UserEducation;
            $education->user_id=auth()->user()->id;
            $education->edu_name=trim($request->edu_name);
            $education->edu_type=trim($request->edu_type);
            $education->edu_place=trim($request->edu_place);
            $education->edu_from    =$request->edu_year_from.'-'.$request->edu_month_from.'-'.$request->edu_day_from;
            if(empty($request->edu_to))
            {
                $education->edu_to    ='present';
            }
            else
            {
                $education->edu_to    =$request->edu_year_to.'-'.$request->edu_month_to.'-'.$request->edu_day_to;
            }
            $education->save();


            Toastr::success('Education added Successfull', 'Success', ['timeOut' => 5000]);
            return redirect('my-profile');
            

        }
    }

    /******************add School Or/ College********/
    public function updateEducationDetails(Request $request)
    {
        if($request->isMethod('post'))
        {
           
            $education = UserEducation::find($request->education_id);
            $education->edu_name=trim($request->edu_name);
            $education->edu_type=trim($request->edu_type);
            $education->edu_place=trim($request->edu_place);
            $education->edu_from    =$request->edu_year_from.'-'.$request->edu_month_from.'-'.$request->edu_day_from;
            if(empty($request->edu_year_to))
            {
                $education->edu_to    ='present';
            }
            else
            {
                $education->edu_to    =$request->edu_year_to.'-'.$request->edu_month_to.'-'.$request->edu_day_to;
            }
            $education->save();


            Toastr::success('Education updated Successfull', 'Success', ['timeOut' => 5000]);
            return redirect('my-profile');

        }
    }

    /******* get education details****************/
    public function getEducationDetails(Request $request)
    {
        $result=UserEducation::where(['user_id'=>auth()->user()->id,'id'=>$request->id])->first();

        $details['id']=$result->id;
        $details['edu_name']=@$result->edu_name;
        $details['edu_type']=@$result->edu_type;
        $details['edu_place']=@$result->edu_place;
        $details['edu_year_from']=date('Y', strtotime($result->edu_from));
        dd($details['edu_year_from']);
        $details['edu_month_from']=date('m', strtotime($result->edu_from));
        $details['edu_day_from']=date('d', strtotime($result->edu_from));
        $details['edu_year_to']=date('Y', strtotime($result->edu_to));
        $details['edu_month_to']=date('m', strtotime($result->edu_to));
        $details['edu_day_to']=date('d', strtotime($result->edu_to));
        return Response::json(['details'=>$details,'status' => 200,'message' => 'Education details  fetch Successfully!.']);
    }

    /**************change Cover Pic******/
    public function changeUserCoverPic(Request $request)
    {

            $old=$request->old_cover_image;
            $take_new =$request->take_new_cover_image;
            $select=$request->device_new_cover_image;
            $remove = $request->remove_cover_image;


            if(!empty($take_new))
            {
                $image=$request->take_new_cover_image;
            }


            if(!empty($select))
            {
                $image=$request->device_new_cover_image;
            }

            if(!empty($remove))
            {
                $image=$request->remove_cover_image;
            }

            if(!empty($image))
            {

                    $profile_image_name = time().'.'.$image->getClientOriginalExtension();
                    $destinationPath = public_path('/assets/uploads/users');
                    $imagePath = $destinationPath. "/".  $profile_image_name;
                    $image->move($destinationPath, $profile_image_name);
                    $image_path = $profile_image_name;

                     User::where(['id'=>auth()->user()->id])->update(['cover_image'=>$image_path]);

                      
            }
            else
            {
                if(!empty($old))
                {
                    User::where('id',auth()->user()->id)->update(['cover_image'=>$old]);
                }

            }  
            Toastr::success('User Cover Picture Successfully Updated', 'Success', ['timeOut' => 5000]);
                      return redirect('/my-profile');              
    }

    /**************change Cover Pic******/
    public function changeUserProfilePic(Request $request)
    {

            $old=$request->old_profile_image;
            $take_new =$request->take_new_profile_image;
            $select=$request->device_new_profile_image;
            $remove = $request->remove_profile_image;


            if(!empty($take_new))
            {
                $image=$request->take_new_profile_image;
            }


            if(!empty($select))
            {
                $image=$request->device_new_profile_image;
            }

            if(!empty($remove))
            {
                $image=$request->remove_profile_image;
            }

            if(!empty($image))
            {
                    $profile_image_name = time().'.'.$image->getClientOriginalExtension();
                    $destinationPath = public_path('/assets/uploads/users');
                    $imagePath = $destinationPath. "/".  $profile_image_name;
                    $image->move($destinationPath, $profile_image_name);
                    $image_path = $profile_image_name;

                     User::where(['id'=>auth()->user()->id])->update(['image'=>$image_path]);
            }
            else
            {
                if(!empty($old))
                {
                    User::where('id',auth()->user()->id)->update(['image'=>$old]);
                }

            }   
            Toastr::success('User  Picture Successfully Updated', 'Success', ['timeOut' => 5000]);
            return redirect('/my-profile');  
    }

    /**************delete Image*************/
    public function deleteUserImage(Request $request,$id)
    {
        $profile = User::where('id',auth()->user()->id);
        if($id =='cover')
        {
            $profile->update(['cover_image'=>'default_property.jpeg']);
            Toastr::success('Cover  Picture remove Successfully ', 'Success', ['timeOut' => 5000]);
            return redirect('/my-profile');  

        }
        else
        {
            $profile->update(['image'=>'default.png']);
            Toastr::success('Profile  Picture remove Successfully ', 'Success', ['timeOut' => 5000]);
            return redirect('/my-profile'); 

        }
    }

    /****************update user City**********/
    public function updateUserCity(Request $request)
    {
        User::where('id',auth()->user()->id)->update(['user_city'=>$request->user_city]);
        return Response::json(['status' => 200,'message' => 'Current City updated  Successfully!.']);
    }

}
