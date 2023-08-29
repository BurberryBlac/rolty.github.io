<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Models\User;
use App\Models\Follower;

class FollowersController extends Controller
{
    //
    public function profile(Request $request){
        // $users = User::where('role_id','!=',1)->where('id','!=',Auth::user()->id)->get()->toArray();
        // $followers = Follower::where('user_id',Auth::user()->id)->get()->toArray();
        $followers = Follower::with('followers')->where('user_id',3)->where('status','follow')->get()->toArray();
        $array = array();
        foreach($followers as $key => $value){
            $array[$key] = $value['follow_id'];
        }
        $users = User::where('role_id','!=',1)->where('id','!=',3)->whereNotIn('id',$array)->get()->toArray();
        return view('events.profile')->with('users',$users)->with('followers',$followers);
    }
    public function follow(Request $request){
        $follower = Follower::create(['user_id'=>Auth::user()->id,'follow_id'=>$request->id]);
        return = $follower;
    }
    public function unfollow(Request $request){
        $follower = Follower::where('user_id',Auth::user()->id)->where('follow_id',$request->id)->delete();
        return = $follower;   
    }
}
