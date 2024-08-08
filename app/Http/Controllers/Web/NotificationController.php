<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


use App\User;
use Config;
use Illuminate\Support\Facades\Auth;
use Validator,Hash,Mail,DB;
use DateTime,DateTimeZone;
use Redirect,Response,File,Image;
use Illuminate\Support\Facades\URL;
use App\Model\Role;
use App\Model\Wallet;
use App\Model\Profile;
use App\Model\Payment;
use App\Model\Transaction;
use App\Model\Card;
use App\Model\SocialAccount;
use Socialite,Exception;
use Intervention\Image\ImageManager;
use Carbon\Carbon;
use Cartalyst\Stripe\Stripe;
use App\Notification;

class NotificationController extends Controller
{
    public function paginate($items, $perPage = 20, $page = null, $options = [])
    {
		// use Illuminate\Pagination\Paginator;
		// use Illuminate\Support\Collection;
		// use Illuminate\Pagination\LengthAwarePaginator;
	
        $page = $page ?: (\Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof \Illuminate\Support\Collection ? $items : \Illuminate\Support\Collection::make($items);
        return new \Illuminate\Pagination\LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }


    public  function getNotification(Request $request) {
		try
		{
	    	$user = Auth::user();
	    	$notifications = [];
            $per_page = (isset($request->per_page)?$request->per_page:10);
	    	Notification::markAsRead($user->id);
			
	    	$notifications = Notification::select('id','notification_type as pushType','message','module','read_status','created_at','sender_id as form_user','receiver_id as to_user')->where('receiver_id',$user->id)
	    	->orderBy('id', 'desc')->get();
	    	if($notifications){
	    		foreach ($notifications as $key => $notification) {
		    		$notification->form_user = User::select('id','name','profile_image')->where('id',$notification->form_user)->first();
		    		$notification->to_user = User::select('id','name','profile_image')->where('id',$notification->to_user)->first();
		    		$notification->sentAt = \Carbon\Carbon::parse($notification->created_at)->getPreciseTimestamp(3);
	    			$notification->sent = $notification->created_at->diffForHumans();
				}
	    	}
             $notifications = $this->paginate($notifications, $per_page);
			//  return response(['status' => "success", 'statuscode' => 200,
	        //                     'message' => __('Notifications list'), 'data' =>['notifications'=>$notifications]], 200);
			// return json_encode($notifications);
			if($user->hasRole('customer')){
				if(Config::get('client_connected') && (Config::get('client_data')->domain_name=='iedu')){
					
					return view('vendor.iedu.notification')->with('notifications',$notifications);
				}
				else
				{
					// return response(['status' => "success", 'statuscode' => 200,
	                //            'message' => __('Notifications list'), 'data' =>['notifications'=>$notifications]], 200);
					return view('vendor.care_connect_live.notification')->with('notifications',$notifications);
				}
	    	
			}

			if($user->hasRole('service_provider')){
				if(Config::get('client_connected') && (Config::get('client_data')->domain_name=='iedu')){
					
					return view('vendor.iedu.notification')->with('notifications',$notifications);
				}
				else
				{
					// return response(['status' => "success", 'statuscode' => 200,
	                //             'message' => __('Notifications list'), 'data' =>['notifications'=>$notifications]], 200);
					return view('vendor.care_connect_live.notification')->with('notifications',$notifications);
				}
				}
		}catch(Exception $ex){
    		return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage()], 500);
    	}
    }

	public  function getNotificationAjax(Request $request) {
		try
		{
	    	$user = Auth::user();
	    	$notifications = [];
            $per_page = (isset($request->per_page)?$request->per_page:10);
	    	Notification::markAsRead($user->id);
			
	    	$notifications = Notification::select('id','notification_type as pushType','message','module','read_status','created_at','sender_id as form_user','receiver_id as to_user')->where('receiver_id',$user->id)
	    	->orderBy('id', 'desc')->get();
	    	if($notifications){
	    		foreach ($notifications as $key => $notification) {
		    		$notification->form_user = User::select('id','name','profile_image')->where('id',$notification->form_user)->first();
		    		$notification->to_user = User::select('id','name','profile_image')->where('id',$notification->to_user)->first();
		    		$notification->sentAt = \Carbon\Carbon::parse($notification->created_at)->getPreciseTimestamp(3);
	    			$notification->sent = $notification->created_at->diffForHumans();
				}
	    	}
             $notifications = $this->paginate($notifications, $per_page);
			//  return response(['status' => "success", 'statuscode' => 200,
	        //                     'message' => __('Notifications list'), 'data' =>['notifications'=>$notifications]], 200);
			// return json_encode($notifications);
			if($user->hasRole('customer')){
				if(Config::get('client_connected') && (Config::get('client_data')->domain_name=='iedu')){
					
					return view('vendor.iedu.notification')->with('notifications',$notifications);
				}
				else
				{
					return response(['status' => "success", 'statuscode' => 200,
	                           'message' => __('Notifications list'), 'data' =>['notifications'=>$notifications]], 200);
					//return view('vendor.care_connect_live.notification')->with('notifications',$notifications);
				}
	    	
			}

			if($user->hasRole('service_provider')){
				if(Config::get('client_connected') && (Config::get('client_data')->domain_name=='iedu')){
					
					return view('vendor.iedu.notification')->with('notifications',$notifications);
				}
				else
				{
					return response(['status' => "success", 'statuscode' => 200,
	                            'message' => __('Notifications list'), 'data' =>['notifications'=>$notifications]], 200);
					//return view('vendor.care_connect_live.notification')->with('notifications',$notifications);
				}
				}
		}catch(Exception $ex){
    		return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage()], 500);
    	}
    }
}