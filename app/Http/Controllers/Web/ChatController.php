<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use LRedis;
use Config;
use App\User,App\Model\Message;
use Illuminate\Support\Facades\Auth;
use Validator,Hash,Mail,DB;
use DateTime,DateTimeZone;
use Redirect,Response,File,Image;
use Illuminate\Support\Facades\URL;
use App\Model\Role;
use App\Model\Wallet;
use App\Model\Profile;
use App\Model\Payment;
use App\Model\Card;
use App\Model\Request as Booking;
use App\Model\SocialAccount;
use Socialite,Exception;
use Intervention\Image\ImageManager;
use Carbon\Carbon;

use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version2X;

class ChatController extends Controller{

    public function getCustomerChat(Request $request)
    {
    	$user = Auth::user();
    	$service_type = 'chat';
        $per_page = (isset($request->per_page)?$request->per_page:10);
    	$chats = Booking::select('id','service_id','from_user','to_user','created_at as booking_date','created_at')->
            where(function($q) use ($user) {
            	if($user->hasrole('customer')){
            		$q->where('from_user',$user->id);
            	}else if($user->hasrole('service_provider')){
            		$q->where('to_user',$user->id);
            	}
			})
            ->whereHas('servicetype', function($query) use ($service_type){
                if($service_type!=='all')
                    return $query->where('type', $service_type);
            })
            ->whereHas('requesthistory', function($query){
                    return $query->whereIn('status',['completed','in-progress','in_progress']);
            })
            ->orderBy('id', 'desc')->cursorPaginate($per_page);
      
            $id = null;
            foreach ($chats as $key => $request_status) {
            	if($id==null)
            		$id = $request_status->id;
            	$last_message = \App\Model\Message::getLastMessage($request_status);
                $request_status->unReadCount = \App\Model\Message::getUnReadCount($request_status,$user->id);
                $date = Carbon::parse($request_status->booking_date,'UTC')->setTimezone('Asia/Kolkata');
                $request_status->booking_date = $date->isoFormat('D MMMM YYYY, h:mm:ss a');
                $request_status->time = $date->isoFormat('h:mm a');
                $request_history = $request_status->requesthistory;
                $request_status->last_message = $last_message;
                $request_status->duration = $request_history->duration;
                $request_status->service_type = $request_status->servicetype->type;
                $request_status->status = $request_history->status;
                $request_status->from_user = User::select('id', 'name', 'email','phone','profile_image','manual_available')->where('id',$request_status->from_user)->first();
                $request_status->to_user = User::select('id', 'name', 'email','phone','profile_image','manual_available')->with('profile')->where('id',$request_status->to_user)->first();
        }
        if(isset($request['request_id'])){
        	$id = $request['request_id'];
        }
        // print_r($id);die; 
      // return $chats;
        $messages = \App\Model\Message::getMessages($id,$user->id);
       
        return view('vendor.care_connect_live.chat',compact('chats'))->with($messages);
    }


    public function getChatSearch(Request $request)
    {
       $searchVal = $request->searchVal;
       $user = Auth::user();
       $service_type = 'chat';
       $chats = Booking::select('id','service_id','from_user','to_user')->
            where(function($q) use ($user) {
            	if($user->hasrole('customer')){
            		$q->where('from_user',$user->id);
            	}else if($user->hasrole('service_provider')){
            		$q->where('to_user',$user->id);
            	}
			})
            ->whereHas('servicetype', function($query) use ($service_type){
                if($service_type!=='all')
                    return $query->where('type', $service_type);
            })
            ->whereHas('requesthistory', function($query){
                    return $query->whereIn('status',['completed','in-progress','in_progress']);
            })
            ->orderBy('id', 'desc')->get();
      
            $id = null;
            foreach ($chats as $key => $request_status) {
            	if($id==null)
            	$id = $request_status->id;
                $request_status->from_user = User::select('id', 'name','manual_available')->where('id',$request_status->from_user)->first();
                $request_status->to_user = User::select('id', 'name','manual_available')->where('id',$request_status->to_user)->first();
             }
             return $chats;

      
    }

    public function test()
    {
        // $redis = LRedis::connection();
	    // Message::create([
        //     'user_id' => '1',
        //     'message' => '1',
        //     'request_id' => '1'
        // ]);
		// $data = ['message' => '1', 'user' => '1'];
		// $redis->publish('message', json_encode($data));
        $socket_url = env('SOCKET_URL');
        $client = new Client(new Version2X($socket_url,[
            'user_id'   =>  '262'
        ]));

        $client->initialize();
        $client->emit('broadcast', ['message' => 'hello shaveta']);
        $client->close();
    }

   
}