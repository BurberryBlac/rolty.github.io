<?php

namespace App\Http\Controllers;

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

use App\Models\Notifications;
use App\Models\NotificationOff;
use App\Models\NotificationReport;
use App\Models\Property;
use App\Models\PropertyVisit;
use App\Models\PropertyReview;
use App\Models\Event;
use App\Models\Chat;
use App\Models\User;

class Notification extends Controller
{
    public function index(Request $request)
    {
        // $types = NotificationOff::where(['user_id' => Auth::user()->id])->pluck('type')->all();
        $data['notifications'] = Notifications::select('notifications.*', 'u.username', 'u.image')
            ->leftjoin('table_users as u','u.id', '=', 'notifications.from_user_id')
            ->whereRaw('notifications.to_user_id = '.auth()->user()->id.' AND notifications.status = "A"')
            // ->whereNotIn('notifications.type', $types)
            ->orderBy('id', 'DESC')->get()->toArray();

        if (sizeof($data['notifications']) > 0) {
            for ($i=0; $i < sizeof($data['notifications']); $i++) { 
                $data['notifications'][$i]['time'] = $this->calculate_time(strtotime($data['notifications'][$i]['created_at']));
                $payload = json_decode($data['notifications'][$i]['payload']);
                if ($payload != '' && !empty($payload) && ($data['notifications'][$i]['type'] == 'new_property' || $data['notifications'][$i]['type'] == 'visit_property' || $data['notifications'][$i]['type'] == 'review_property')) {
                    // Type = Property
                    $data['notifications'][$i]['property_details'] = Property::where(['id'=>$payload->property_id])->first()->toArray();
                    if ($data['notifications'][$i]['type'] == 'visit_property') {
                        // Type = Visit
                        $data['notifications'][$i]['visit_details'] = PropertyVisit::where(['id'=>$payload->visit_id])->first()->toArray();
                    } else if ($data['notifications'][$i]['type'] == 'review_property') {
                        // Type = Review
                        $data['notifications'][$i]['review_details'] = PropertyReview::where(['id'=>$payload->review_id])->first()->toArray();
                    }
                } else if ($payload != '' && !empty($payload) && $data['notifications'][$i]['type'] == 'new_message') {
                    // Type = Chat
                    $data['notifications'][$i]['chat_details'] = Chat::where(['id'=>$payload->chat_id])->first()->toArray();
                } else if ($payload != '' && !empty($payload) && $data['notifications'][$i]['type'] == 'new_event') {
                    // Type = Event
                    $data['notifications'][$i]['event_details'] = Event::where(['id'=>$payload->event_id])->first()->toArray();
                }
            }
        }

        return view('notifications/index', $data);
    }

    public function removeNotification(Request $request)
    {
        Notifications::where('id',$request->id)->update(['status' => 'I']);
        
        return TRUE;
    }

    public function turnNotificationOff(Request $request)
    {
        $noti_off = new NotificationOff;
        $noti_off->user_id = Auth::user()->id;
        $noti_off->type = $request->type;
        $noti_off->save();

        return TRUE;
    }

    public function reportIssue(Request $request)
    {
        $noti_rep = new NotificationReport;
        $noti_rep->user_id = Auth::user()->id;
        $noti_rep->notification_id = $request->id;
        $noti_rep->save();

        Notifications::where('id',$request->id)->update(['status' => 'I']);

        return redirect()->route('notifications');
    }

    public function markAllRead(Request $request)
    {
        Notifications::where('to_user_id', auth()->user()->id)->update(['view_status' => 1]);
    }

    public function calculate_time($ptime)
    {
        if($ptime < strtotime('-7 days')){
            return date('d M Y', $ptime);
        }else{
            $etime = time() - $ptime;

            if ($etime < 1){
                return '0 seconds';
            }

            $a = array(
                365 * 24 * 60 * 60  =>  'year',
                30 * 24 * 60 * 60  =>  'month',
                24 * 60 * 60  =>  'day',
                60 * 60  =>  'hour',
                60  =>  'minute',
                1  =>  'second'
            );
            $a_plural = array(
                'year'   => 'years',
                'month'  => 'months',
                'day'    => 'days',
                'hour'   => 'hrs',
                'minute' => 'mins',
                'second' => 'sec'
            );

            foreach ($a as $secs => $str){
                $d = $etime / $secs;
                if ($d >= 1){
                    $r = round($d);
                    return $r . ' ' . ($r > 1 ? $a_plural[$str] : $str) . ' ago';
                }
            }
        }
    }
}
