<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use FCM;
use Auth;
use App\Models\Chat;
use App\Models\Roles;

class ChatController extends Controller
{
    
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
		$sender = User::find(Auth::user()->id);
		$role = Roles::find(Auth::user()->role_id);
		$sender_role = $role->name;
        if($request->roomchat){
            $group_user = base64url_decode($request->roomchat);
            // $encode_format = base64url_encode($request->roomchat."#".time());
            $explode_id = explode("#",$group_user);
            $user_id = $explode_id[0];
            $receiver = User::find($user_id);
            return view('chat.index', compact('sender','receiver','sender_role'));
        }else{
            return view('chat.index', compact('sender','sender_role'));
        }
    }

    public function save_fcm_token(Request $request)
    {
        $input = $request->all();
		if(Auth::user()){
			$fcm_token = $input['currentToken'];
			$user_id = Auth::user()->id;
			$user = User::findOrFail($user_id);

			$user->fcm_token = $fcm_token;
			$user->save();
			return response()->json([
				'success'=>true,
				'message'=>'User token updated successfully.'
			]);
		}else{
			return response()->json([
				'success'=>false,
				'message'=>'User login must be required'
			]);
		}
    }

    public function createChat(Request $request)
    {
		$input = $request->all();
		$message = $input['message'];
		$user_id= $input['user_id'];
		$loginid=auth()->user()->id;
		if($loginid != $user_id){
			$receiver_id=$user_id;
			$receiver_name=$input['user_name'];
		}else{
			$receiver_id=$loginid;
			$receiver_name=auth()->user()->name;
		}
		$chat = new Chat([
			'sender_id' => auth()->user()->id,
			'sender_name' => auth()->user()->name,
			'user_id' => $user_id,
			'receiver_id' => $receiver_id,
			'receiver_name' => $receiver_name, 
			'message' => $message
		]);

		$this->broadcastMessage(auth()->user()->name,$message);
		$chat->save(); 

		/*Send Notification*/
		$exist_chat = Chat::where(['sender_id' => auth()->user()->id, 'receiver_id' => $receiver_id])
							->orWhere(['sender_id' => $receiver_id, 'receiver_id' => auth()->user()->id]);
		if ($exist_chat->count() == 0) {
			$noti['title'] = "New message";
            $noti['body'] = "You have a new message by ".auth()->user()->name;
            $to_user_id = [$receiver_id];
            $types = NotificationOff::where(['user_id' => $receiver_id])->pluck('type')->all();
            if (sizeof($types) > 0) {
                if (!in_array('review_property', $types)) {
                    $firebaseToken = User::whereNotNull('fcm_token')->whereIn('id', $to_user_id)->pluck('fcm_token')->all();
            		app('App\Http\Controllers\Properties\PropertyController')->sendNotification($noti, $firebaseToken, $to_user_id, 'new_message', ['chat_id' => $chat->id]);
                }
            }else{
                $firebaseToken = User::whereNotNull('fcm_token')->whereIn('id', $to_user_id)->pluck('fcm_token')->all();
            	app('App\Http\Controllers\Properties\PropertyController')->sendNotification($noti, $firebaseToken, $to_user_id, 'new_message', ['chat_id' => $chat->id]);
            }
		}
		/*Send Notification*/

		return redirect()->back();
    }
	
	private function broadcastMessage($senderName, $message)
	{
		$optionBuilder = new OptionsBuilder();
		$optionBuilder->setTimeToLive(60 * 20); 

		$notificationBuilder = new PayloadNotificationBuilder('New message from : ' . $senderName);
		$notificationBuilder->setBody($message)
			->setSound('default')
			->setClickAction('https://stack.brstdev.com/fluffy/home');

		$dataBuilder = new PayloadDataBuilder();
		$dataBuilder->addData([
			'sender_name' => $senderName,
			'message' => $message
		]); 
 
		$option = $optionBuilder->build();
		$notification = $notificationBuilder->build();
		$data = $dataBuilder->build();

		$tokens = User::all()->pluck('fcm_token')->toArray();
		
		$downstreamResponse = FCM::sendTo($tokens, $option, $notification, $data);

		return $downstreamResponse->numberSuccess();
	}

}
