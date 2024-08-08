<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Config;
use App\User;
use App\Notification;
use Illuminate\Support\Facades\Auth;
use Validator,Hash,Mail,DB;
use DateTime,DateTimeZone;
use Redirect,Response,File,Image;
use Illuminate\Support\Facades\URL;
use App\Model\Role;
use App\Model\Wallet;
use App\Model\Profile;
use App\Model\Payment;
use App\Model\RequestHistory;
use App\Model\Transaction;
use App\Model\SocialAccount;
use Socialite,Exception;
use Intervention\Image\ImageManager;
use Carbon\Carbon;
use Twilio\Rest\Client;
use Twilio\TwiML\VoiceResponse;
use Twilio\Jwt\AccessToken;
use Twilio\Jwt\Grants\VoiceGrant;
use Twilio\Jwt\Grants\VideoGrant;
use App\Model\EnableService;
use App\Helpers\Helper;
class CallerController extends Controller{

	public function __construct() {
		$this->middleware('auth')->except(['callbackExotel','callTwillio','accessTokenTwillio','twillioCallback','callTwillio1','placeCall','incoming','makeCallTestToken']);
	}

    /**
     * @SWG\Post(
     *     path="/start-request",
     *     description="startRequest",
     * tags={"Service Provider"},
     *     security={
     *     {"Bearer": {}},
     *   },
     *  @SWG\Parameter(
     *         name="request_id",
     *         in="query",
     *         type="number",
     *         description=" Request Id",
     *         required=true,
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK",
     *     ),
     *     @SWG\Response(
     *         response=400,
     *         description="Please provide all data"
     *     )
     * )
     */

    public function startRequest(Request $request){
        $user = Auth::user();
        $rules = ['request_id' => 'required|exists:requests,id'];
        $validator = Validator::make($request->all(),$rules);
        if ($validator->fails()) {
            return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                $validator->getMessageBag()->first()), 400);
        }
        $sr_request = \App\Model\Request::where(['id'=>$request->request_id])->first();
        $category_name = '';
        if($sr_request->sr_info->getCategoryData($sr_request->sr_info->id)){
            $category_name = $sr_request->sr_info->getCategoryData($sr_request->sr_info->id)->name;
        }
        $call_id = null;
    if(config('client_connected') && Config::get("client_data")->domain_name == "care_connect_live")
    {
        if($sr_request->requesthistory->status=='in-progress')
        {
            $main_service_type = ($sr_request->servicetype->service_type)?$sr_request->servicetype->service_type:$sr_request->servicetype->type;
            if(strtolower($main_service_type)=='call'||strtolower($main_service_type)=='video call'  || strtolower($main_service_type)=='audio_call' || strtolower($main_service_type)=='video_call') 
            {
                $sr_request->requesthistory->status = "in-progress";
                
                $action = 'call';
                $calling_type = $main_service_type;
            
            }
            $sr_request->requesthistory->calling_type = $calling_type;
            $sr_request->requesthistory->save();
            if(strtolower($main_service_type)=='video call' || strtolower($main_service_type)=='call' || strtolower($main_service_type)=='audio_call' || strtolower($main_service_type)=='video_call')
            {
                $call_id = bin2hex(random_bytes(9)).$sr_request->id;
                if($sr_request->cus_info->device_type=='IOS'){
                    $apn_notification =array(
                        'pushType'=>strtoupper($action),
                        'title'=>strtoupper($action),
                        'message'=>__('notification.sp_calling_text',['vendor_name' => $user->name]),
                        'isCallFrom'=>strtoupper($action),
                        'request_id'=>$sr_request->id,
                        'service_type'=>$sr_request->servicetype->type,
                        'main_service_type'=>$main_service_type,
                        'request_time'=>$sr_request->booking_date,
                        'sender_name'=>$user->name,
                        'sender_image'=>$user->profile_image,
                        'vendor_category_name'=>$category_name,
                        'tokens'=>$sr_request->cus_info->apn_token
                    );
                    $push = Helper::sendAPNPushNotification($sr_request->cus_info,$apn_notification);
                }else{
                    $notification = new Notification();
                    $notification->push_notification(array($sr_request->from_user),array(
                        'pushType'=>strtoupper($action),
                        'message'=>__('notification.sp_calling_text',['vendor_name' => $user->name]),
                        'isCallFrom'=>strtoupper($action),
                        'request_id'=>$sr_request->id,
                        'call_id'=>$call_id,
                        'service_type'=>$sr_request->servicetype->type,
                        'main_service_type'=>$main_service_type,
                        'request_time'=>$sr_request->booking_date,
                        'sender_name'=>$user->name,
                        'sender_image'=>$user->profile_image,
                        'vendor_category_name'=>$category_name,
                    ));
                }
            }else{

            }
            $sr_request->call_id = $call_id;
            $sr_request->save();
            return response(
                array(
                'status' =>"success",
                'statuscode' => 200,
                'action'=>$action,
                'data'=>array('action'=>$action,'call_id'=>$call_id),
                'message' =>__("Request $action"))
            , 200);
        }
    }


        if($sr_request->requesthistory->status=='accept'){
            $calling_type = $sr_request->servicetype->type;
            $main_service_type = ($sr_request->servicetype->service_type)?$sr_request->servicetype->service_type:$sr_request->servicetype->type;
            $action = $sr_request->servicetype->type;
            if(strtolower($main_service_type)=='chat'){
                $sr_request->requesthistory->status = "in-progress";
                $action = 'chat';
            }elseif(strtolower($main_service_type)=='call'||strtolower($main_service_type)=='video call'  || strtolower($main_service_type)=='audio_call' || strtolower($main_service_type)=='video_call') {
                if(config('client_connected') && Config::get("client_data")->domain_name == "care_connect_live"){
                    $sr_request->requesthistory->status = "in-progress";
                }
                $action = 'call';
                $calling_type = $main_service_type;
            }else{
                $sr_request->requesthistory->status = "in-progress";
            }
            $sr_request->requesthistory->calling_type = $calling_type;
            $sr_request->requesthistory->save();
            if(strtolower($sr_request->servicetype->type)=='chat'){
                $notification = new Notification();
                $notification->push_notification(array($sr_request->from_user),array(
                    'pushType'=>"CHAT_STARTED",
                    'message'=>"$user->name started chat",
                    'request_id'=>$sr_request->id,
                    'service_type'=>$sr_request->servicetype->type,
                    'main_service_type'=>$main_service_type,
                    'request_time'=>$sr_request->booking_date,
                    'senderName'=>$user->name,
                    'senderId'=>$user->id,
                    'sender_image'=>$user->profile_image,
                    'vendor_category_name'=>$category_name,
                ));
            }else if(strtolower($main_service_type)=='video call' || strtolower($main_service_type)=='call' || strtolower($main_service_type)=='audio_call' || strtolower($main_service_type)=='video_call'){
                $call_id = bin2hex(random_bytes(9)).$sr_request->id;
                if($sr_request->cus_info->device_type=='IOS'){
                    $apn_notification =array(
                        'pushType'=>strtoupper($action),
                        'title'=>strtoupper($action),
                        'message'=>__('notification.sp_calling_text',['vendor_name' => $user->name]),
                        'isCallFrom'=>strtoupper($action),
                        'request_id'=>$sr_request->id,
                        'service_type'=>$sr_request->servicetype->type,
                        'main_service_type'=>$main_service_type,
                        'request_time'=>$sr_request->booking_date,
                        'sender_name'=>$user->name,
                        'sender_image'=>$user->profile_image,
                        'vendor_category_name'=>$category_name,
                        'tokens'=>$sr_request->cus_info->apn_token
                    );
                    $push = Helper::sendAPNPushNotification($sr_request->cus_info,$apn_notification);
                }else{
                    $notification = new Notification();
                    $notification->push_notification(array($sr_request->from_user),array(
                        'pushType'=>strtoupper($action),
                        'message'=>__('notification.sp_calling_text',['vendor_name' => $user->name]),
                        'isCallFrom'=>strtoupper($action),
                        'request_id'=>$sr_request->id,
                        'call_id'=>$call_id,
                        'service_type'=>$sr_request->servicetype->type,
                        'main_service_type'=>$main_service_type,
                        'request_time'=>$sr_request->booking_date,
                        'sender_name'=>$user->name,
                        'sender_image'=>$user->profile_image,
                        'vendor_category_name'=>$category_name,
                    ));
                }
            }else{

            }
            $sr_request->call_id = $call_id;
            $sr_request->save();
            return response(
                array(
                'status' =>"success",
                'statuscode' => 200,
                'action'=>$action,
                'data'=>array('action'=>$action,'call_id'=>$call_id),
                'message' =>__("Request $action"))
            , 200);
        }else{
            return response(
                array(
                'status' =>"error",
                'statuscode' => 400,
                'message' =>__("Request status already ".$sr_request->requesthistory->status))
            , 400);
        }
    }


    /**
     * @SWG\Post(
     *     path="/start-call",
     *     description="start Call",
     * tags={"Service Provider"},
     *     security={
     *     {"Bearer": {}},
     *   },
     *  @SWG\Parameter(
     *         name="request_id",
     *         in="query",
     *         type="number",
     *         description=" Request Id",
     *         required=true,
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK",
     *     ),
     *     @SWG\Response(
     *         response=400,
     *         description="Please provide all data"
     *     )
     * )
     */

    public function startCall(Request $request){
        $user = Auth::user();
        $rules = ['request_id' => 'required|exists:requests,id'];
        $validator = Validator::make($request->all(),$rules);
        if ($validator->fails()) {
            return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                $validator->getMessageBag()->first()), 400);
        }
        $sr_request = \App\Model\Request::where(['id'=>$request->request_id])->first();
        $category_name = '';
        if($sr_request->sr_info->getCategoryData($sr_request->sr_info->id)){
            $category_name = $sr_request->sr_info->getCategoryData($sr_request->sr_info->id)->name;
        }
        $main_service_type = ($sr_request->servicetype->service_type)?$sr_request->servicetype->service_type:$sr_request->servicetype->type;
        $action = 'call';
        $call_id = bin2hex(random_bytes(9)).$sr_request->id;
        $call_to = $sr_request->cus_info;
        if($user->hasrole('customer')){
            $call_to = $sr_request->sr_info;
        }
        if($call_to->device_type=='IOS'){
            $apn_notification =array(
                'pushType'=>strtoupper($action),
                'title'=>strtoupper($action),
                'message'=>__('notification.sp_calling_text',['vendor_name' => $user->name]),
                'isCallFrom'=>strtoupper($action),
                'request_id'=>$sr_request->id,
                'service_type'=>$sr_request->servicetype->type,
                'main_service_type'=>$main_service_type,
                'request_time'=>$sr_request->booking_date,
                'sender_name'=>$user->name,
                'sender_image'=>$user->profile_image,
                'vendor_category_name'=>$category_name,
                'tokens'=>$call_to->apn_token
            );
            $push = Helper::sendAPNPushNotification($call_to,$apn_notification);
        }else{
            $notification = new Notification();
            $notification->push_notification(array($sr_request->from_user),array(
                'pushType'=>strtoupper($action),
                'message'=>__('notification.sp_calling_text',['vendor_name' => $user->name]),
                'isCallFrom'=>strtoupper($action),
                'request_id'=>$sr_request->id,
                'call_id'=>$call_id,
                'service_type'=>$sr_request->servicetype->type,
                'main_service_type'=>$main_service_type,
                'request_time'=>$sr_request->booking_date,
                'sender_name'=>$user->name,
                'sender_image'=>$user->profile_image,
                'vendor_category_name'=>$category_name,
            ));
        }
        $sr_request->call_id = $call_id;
        $sr_request->save();
        return response(
            array(
            'status' =>"success",
            'statuscode' => 200,
            'action'=>$action,
            'data'=>array('action'=>$action,'call_id'=>$call_id),
            'message' =>__("Request $action"))
        , 200);
    }


     /**
     * Test Token
     *
     */
    public function makeCallTestToken(Request $request){
        try{
            $input = $request->all();
            //validation rules
            $rules = array(                        
                        'token'=>'required',
                        'password'=>'required',
                        'sender_type'=>'required',
                    );        
            //validate input
            $validation = \Validator::make($input,$rules);
            if($validation->fails()){
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                    $validation->getMessageBag()->first()), 400);
            }
            $push = Helper::sendAPNPushNotificationTest($request);
            return $push;
        }catch(Exception $e){
            return response(array('status' => "error", 'statuscode' => 500, 'message' =>$e->getMessage()), 500);
        }
    }

	/**
     * @SWG\Post(
     *     path="/make-call",
     *     description="makeCallRequest",
     * tags={"Service Provider"},
     *     security={
     *     {"Bearer": {}},
     *   },
     *  @SWG\Parameter(
     *         name="request_id",
     *         in="query",
     *         type="number",
     *         description=" Request Id",
     *         required=true,
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK",
     *     ),
     *     @SWG\Response(
     *         response=400,
     *         description="Please provide all data"
     *     )
     * )
     */

    public static function makeCallRequest(Request $request) {
    	try{
	    	$user = Auth::user();
	    	$rules = ['request_id' => 'required'];
            $validator = Validator::make($request->all(),$rules);
            if ($validator->fails()) {
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                    $validator->getMessageBag()->first()), 400);
            }
            $status = 400;
            $message = 'Something went wrong';
            $service_id = \App\Model\Service::getServiceId('call');
            $sr_request = \App\Model\Request::where(['id'=>$request->request_id,'service_id'=>$service_id])->first();
            if($sr_request){
                $category_name = '';
                if($sr_request->sr_info->getCategoryData($sr_request->sr_info->id)){
                    $category_name = $sr_request->sr_info->getCategoryData($sr_request->sr_info->id)->name;
                }
                $calling_type = EnableService::where('type','audio/video')->first();
                $slot_duration = EnableService::where('type','slot_duration')->first();
                $timelimit  = 30 * 60;
                if($slot_duration){
                    $timelimit = $slot_duration->value * 60;
                }
                if($calling_type->value=='exotel'){
    	    		if($sr_request->requesthistory->status=='accept'){
                        // $sr_service_charges = \App\Model\Subscription::where(['consultant_id'=>$sr_request->sr_info->id,'service_id'=>$sr_request->service_id])->first();
                        // $charges_per_second = $sr_service_charges->charges/$sr_service_charges->duration;
                        // $timelimit = $sr_request->cus_info->wallet->balance/$charges_per_second;
    		    		$response = \Curl::to('https://'.env('EXOTEL_KEY').':'.env('EXOTEL_TOKEN').'@twilix.exotel.in/v1/Accounts/'.env('EXOTEL_SID').'/Calls/connect .json')
    		    		->withData(['From'=>$sr_request->sr_info->phone, 
                            'To'=>$sr_request->cus_info->phone,
    		    			'CustomField'=>$sr_request->id,
                            "StatusCallbackEvents[0]"=>"terminal",
    		    			"StatusCallback"=>env('APP_URL')."/api/callback_exotel",
    		    			"StatusCallbackContentType"=>"application/json",
                            "TimeLimit"=>$timelimit,
                        ])
    		    		->post();
    		    		$array_response = json_decode($response);
    		    		if(isset($array_response->RestException)){
    		    			$status = $array_response->RestException->Status;
    		    			$message = $array_response->RestException->Message;
    		    		}elseif (isset($array_response->Call) && isset($array_response->Call->Sid)) {
    		    			$sr_request->requesthistory->sid = $array_response->Call->Sid;
                            $sr_request->requesthistory->calling_type = $calling_type->value;
    		    			$sr_request->requesthistory->account_sid = $array_response->Call->AccountSid;
    		    			$sr_request->requesthistory->virtual_number = $array_response->Call->PhoneNumberSid;
    		    			// $sr_request->requesthistory->status = $array_response->Call->Status;
    		    			$sr_request->requesthistory->save();
    		    			return response(['status' => "success", 'statuscode' => 200,
    		    				'message' => __('notification.sp_calling_text', ['vendor_name' =>$user->name]), 'data' =>['isCallFrom'=>'Exotel']], 200);
    		    		}
    	    		}else{
    	    			$message = 'status is '.$sr_request->requesthistory->status;
    	    		}
                }else{
                    $isCallFrom = $calling_type->value;
                    if($calling_type->value=='twillio'){
                        $isCallFrom = 'twilio';
                    }elseif ($calling_type->value=='twilio_video' || $calling_type->value=='jistimeet_video') {
                    }
                    $sr_request->requesthistory->calling_type = $isCallFrom;
                    $sr_request->requesthistory->save();
                    return response(['status' => "success", 'statuscode' => 200,
                                'message' => __('notification.sp_calling_text', ['vendor_name' => $user->name]), 'data' =>['isCallFrom'=>$isCallFrom]], 200);
                }
                return response(array('status' => "error", 'statuscode' => $status, 'message' =>__($message)), $status);
	    	}else{
	    		$message = 'No Call Request Found';
	    	}
	    	return response(array('status' => "error", 'statuscode' => $status, 'message' =>__($message)), $status);
    	}catch(Exception $ex){
    		return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage()], 500);
    	}
    }

    public static function callbackExotel(Request $request){
    	try{
    		$status = 400;
            $message = 'Something went wrong';
    		$input = $request->all();
    		if(isset($input['CallSid'])){
    			$req_history = RequestHistory::where('sid',$input['CallSid'])->first();
    			if($req_history){
    				$req_history->increment('duration',$input['ConversationDuration']);
    				$req_history->recording_url = $input['RecordingUrl'];
                    if($input['Status']=='completed'){
    				    $req_history->status = $input['Status'];
                    }
    				if(isset($input['Legs']) && count($input['Legs'])){
	    				$req_history->from_on_call_duration = $input['Legs'][0]['OnCallDuration'];
	    				$req_history->from_pick_status = $input['Legs'][0]['Status'];
	    				$req_history->to_on_call_duration = $input['Legs'][1]['OnCallDuration'];
	    				$req_history->to_pick_status = $input['Legs'][1]['Status'];
    				}
    				if($req_history->save() && $req_history->status=='completed'){
    					if($req_history->request->sr_info){
    						// $sr_service_charges = \App\Model\Subscription::where(['consultant_id'=>$req_history->request->sr_info->id,'service_id'=>$req_history->request->service_id])->first();
    						// $charges_per_second = $sr_service_charges->charges/$sr_service_charges->duration;
    						// $total_charges = $charges_per_second * $input['ConversationDuration'];
    						// $withdrawal_to = array(
    						// 	'balance'=>$total_charges,
    						// 	'user'=>$req_history->request->cus_info,
    						// 	'from_id'=>$req_history->request->sr_info->id,
          //                       'request_id'=>$req_history->request_id,
    						// 	'status'=>'succeeded'
    						// );
    						// Transaction::createWithdrawal($withdrawal_to);
	    					$deposit_to = array(
    							'user'=>$req_history->request->sr_info,
    							'from_id'=>$req_history->request->cus_info->id,
                                'request_id'=>$req_history->request_id,
    							'status'=>'succeeded'
    						);
    						Transaction::updateDeposit($deposit_to);
                            $notification = new Notification();
                            $notification->sender_id = $req_history->request->to_user;
                            $notification->receiver_id = $req_history->request->from_user;
                            $notification->module_id = $req_history->request->id;
                            $notification->module ='request';
                            $notification->notification_type ='REQUEST_COMPLETED';
                            $notification->message ="Your Request has been completed";
                            $notification->save();

                            $notification = new Notification();
                            $notification->sender_id = $req_history->request->from_user;
                            $notification->receiver_id = $req_history->request->to_user;
                            $notification->module_id = $req_history->request->id;
                            $notification->module ='request';
                            $notification->notification_type ='REQUEST_COMPLETED';
                            $notification->message ="Your Request has been completed";
                            $notification->save();
                            $notification->push_notification(array($req_history->request->to_user,$req_history->request->from_user),array('request_id'=>$req_history->request->id,'pushType'=>'REQUEST_COMPLETED','message'=>__("Your Request has been completed")));
    					}
    				}
    			}
    			return response(array('status' => "success", 'statuscode' => 200, 'message' =>__('request completed')), $status);
    		}
	    	return response(array('status' => "error", 'statuscode' => $status, 'message' =>__($message)), $status);
    	}catch(Exception $ex){
    		return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage()], 500);
    	}
    }

    public static function callTwillio(Request $request){
        // \Log::info('callTwillio Request'.json_encode($request->all()));
        $callerId = 'client:quick_start';
        $to = isset($request->to) ? $request->to : "";
        $callerNumber = env('TWILIO_NUMBER');
        $service_id = \App\Model\Service::getServiceId('call');
        $service_request = \App\Model\Request::where(['id'=>$request->requestId,'service_id'=>$service_id])->first();
        $timelimit = 0;
        $slot_duration = EnableService::where('type','slot_duration')->first();
        $timelimit  = 30 * 60;
        if($slot_duration){
            $timelimit = $slot_duration->value * 60;
        }
        $response = new VoiceResponse();
        if (!isset($to) || empty($to)) {
          $response->say('Congratulations! You have just made your first call! Good bye.');
        } else if (is_numeric($to)) {
          $dial = $response->dial(null,
            array(
              'callerId' => $callerNumber,
              'timeLimit'=>$timelimit,
            ));
          $dial->number($to);
        } else {
          $dial = $response->dial(null,
            array(
               'callerId' => $callerId,
               'timeLimit'=>$timelimit,
            ));
          $dial->client($to);
        }
        if($service_request){
            $service_request->requesthistory->sid = $request->CallSid;
            $service_request->requesthistory->account_sid = $request->AccountSid;
            $service_request->requesthistory->virtual_number = $request->From;
            // $service_request->requesthistory->status = $request->CallStatus;
            $service_request->requesthistory->save();
        }
        // \Log::info('callTwillio1 response'.$response);
        print($response);
    }

    public static function incoming(Request $request){
        // \Log::info('incoming Request'.json_encode($request->all()));
        $response = new \Twilio\Twiml();
        $identity = isset($request->identity) ? $request->identity : "alice";
        if(isset($request->to)){
            $identity = $request->to;
        }
        $voice = array("voice"=>$identity);
        $response->say('Congratulations! You have received your first inbound call! Good bye.',$voice);
        // \Log::info('incoming response'.json_encode($response));
        print $response;
    }

    public static function placeCall(Request $request){
        // \Log::info('placeCall request '.json_encode($request->all()));
        $to = isset($request->identity) ? $request->identity : "alice";
        if(isset($request->to)){
            $to = $request->to;
        }
        $callerNumber = env('TWILIO_NUMBER');
        $callerId = 'client:quick_start';
        $client = new \Twilio\Rest\Client(env('TWILIO_API_KEY'),env('TWILIO_API_SECRET'), env('TWILIO_ACCOUNT_SID'));
        $call = NULL;
        if (!isset($to) || empty($to)) {
          $call = $client->calls->create(
            'client:alice', // Call this number
            $callerId,      // From a valid Twilio number
            array(
              'url' => env('APP_URL').'/api/incoming',
              'statusCallback' => env('APP_URL').'/api/callback'
            )
          );
        } else if (is_numeric($to)) {
          $call = $client->calls->create(
            $to,           // Call this number
            $callerNumber, // From a valid Twilio number
            array(
              'url' => env('APP_URL').'/api/incoming',
              'statusCallback' => env('APP_URL').'/api/callback'
            )
          );
        } else {
          $call = $client->calls->create(
            'client:'.$to, // Call this number
            $callerId,     // From a valid Twilio number
            array(
              'url' => env('APP_URL').'/api/incoming',
              'statusCallback' => env('APP_URL').'/api/callback'
            )
          );
        }
        // \Log::info('placeCall Response'.$call);
    }

    public static function twillioCallback(Request $request){
        // \Log::info('CAll back call'.json_encode($request->all()));
        $input = $request->all();
        $sid    = env('TWILIO_ACCOUNT_SID');
        $token  = env('TWILLIO_TOKEN');
        $twilio = new Client($sid, $token);
        $calls = $twilio->calls->read(['parentCallSid'=>$input['CallSid']],1);
        if($calls && $calls[0]){
            $req_history = RequestHistory::where('sid',$input['CallSid'])->first();
            if($req_history){
                $req_history->increment('duration',$calls[0]->duration);
                $req_history->status = $calls[0]->status;
                $req_history->from_on_call_duration = $input['CallDuration'];
                $req_history->from_pick_status = $input['CallStatus'];
                $req_history->to_on_call_duration = $calls[0]->duration;
                $req_history->to_pick_status = $calls[0]->status;
                if($req_history->save() && $req_history->status=='completed' && $req_history->request->sr_info){
                    // $sr_service_charges = \App\Model\Subscription::where(['consultant_id'=>$req_history->request->sr_info->id,'service_id'=>$req_history->request->service_id])->first();
                    // $charges_per_second = $sr_service_charges->charges/$sr_service_charges->duration;
                    // $total_charges = $charges_per_second * $calls[0]->duration;
                    // $withdrawal_to = array(
                    //     'balance'=>$total_charges,
                    //     'user'=>$req_history->request->cus_info,
                    //     'from_id'=>$req_history->request->sr_info->id,
                    //     'request_id'=>$req_history->request_id,
                    //     'status'=>'succeeded'
                    // );
                    // Transaction::createWithdrawal($withdrawal_to);
                    // $deposit_to = array(
                    //     'balance'=>$total_charges,
                    //     'user'=>$req_history->request->sr_info,
                    //     'from_id'=>$req_history->request->cus_info->id,
                    //     'request_id'=>$req_history->request_id,
                    //     'status'=>'succeeded'
                    // );
                    // Transaction::createDeposit($deposit_to);
                    $deposit_to = array(
                                'user'=>$req_history->request->sr_info,
                                'from_id'=>$req_history->request->cus_info->id,
                                'request_id'=>$req_history->request_id,
                                'status'=>'succeeded'
                            );
                    Transaction::updateDeposit($deposit_to);
                    $notification = new Notification();
                    $notification->sender_id = $req_history->request->to_user;
                    $notification->receiver_id = $req_history->request->from_user;
                    $notification->module_id = $req_history->request->id;
                    $notification->module ='request';
                    $notification->notification_type ='REQUEST_COMPLETED';
                    $notification->message ="Your Request has been completed";
                    $notification->save();

                    $notification = new Notification();
                    $notification->sender_id = $req_history->request->from_user;
                    $notification->receiver_id = $req_history->request->to_user;
                    $notification->module_id = $req_history->request->id;
                    $notification->module ='request';
                    $notification->notification_type ='REQUEST_COMPLETED';
                    $notification->message ="Your Request has been completed";
                    $notification->save();
                    $notification->push_notification(array($req_history->request->to_user,$req_history->request->from_user),array('request_id'=>$req_history->request->id,'pushType'=>'REQUEST_COMPLETED','message'=>__("Your Request has been completed")));
                }
            }
        }
    }

    public static function accessTokenTwillio(Request $request){
        // \Log::info('accessTokenTwillio Request'.json_encode($request->all()));
        // Required for all Twilio access tokens
        try{
            $twilioAccountSid = env('TWILIO_ACCOUNT_SID');
            $twilioApiKey = env('TWILIO_API_KEY');
            $twilioApiSecret = env('TWILIO_API_SECRET');
            $push_credential_sid = env('PUSH_CREDENTIAL_SID');
            $app_sid = env('APP_SID');
            $identity = isset($request->identity) ? $request->identity : "alice";
            $token = new AccessToken(
                $twilioAccountSid,
                $twilioApiKey,
                $twilioApiSecret,
                3600,
                $identity
            );
            // Create Voice grant
            $grant = new VoiceGrant();
            $grant->setOutgoingApplicationSid($app_sid);
            $grant->setPushCredentialSid($push_credential_sid);
            $token->addGrant($grant);

            $grant = new VideoGrant();
            // $grant->setRoom('cool room');
            // $grant->setOutgoingApplicationSid($app_sid);
            // $grant->setPushCredentialSid($push_credential_sid);
            $token->addGrant($grant);
            // echo $token->toJWT();
            // die;
        return response(array('status' => "success",'data'=>['twilioToken'=>$token->toJWT()], 'statuscode' => 200, 'message' =>__('Twilio Token')),200);
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage()], 500);
        }
    }
    /**
     * @SWG\Get(
     *     path="/contact-list",
     *     description="User Contact List",
     * tags={"Service Provider"},
     *     security={
     *     {"Bearer": {}},
     *   },
     *     @SWG\Response(
     *         response=200,
     *         description="OK",
     *     ),
     *     @SWG\Response(
     *         response=400,
     *         description="Please provide all data"
     *     )
     * )
     */

    public function contactList(){
        try{
            $user = Auth::user();
            $per_page = (isset($request->per_page)?$request->per_page:10);
            $lists = \App\Model\ContactList::select('id','name')->orderBy('name','ASC')->where([
                'parent_id'=>null,
                'user_id'=>$user->id,
            ])->cursorPaginate($per_page);
            foreach ($lists as $key => $list) {
                $list->phone_numbers = \App\Model\ContactList::select('phone','type_label')->where('parent_id',$list->id)->orWhere('id',$list->id)->get();
            }   
            $after = null;
            if($lists->meta['next']){
                $after = $lists->meta['next']->target;
            }
            $before = null;
            if($lists->meta['previous']){
                $before = $lists->meta['previous']->target;
            }
            $per_page = $lists->perPage();
            return response(['status' => "success", 'statuscode' => 200,
                                'message' => __('Contact List...'),
                                'data' =>[
                                    'contacts'=>$lists->items(),
                                    'after'=>$after,
                                    'before'=>$before,
                                    'per_page'=>$per_page
                                ]], 200);
            }catch(Exception $ex){
                return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage().' '.$ex->getLine()], 500);
            }
    }

    /**
     * @SWG\Post(
     *     path="/contact-add",
     *     description="Add Contact List",
     * tags={"Service Provider"},
     *     security={
     *     {"Bearer": {}},
     *   },
     *  @SWG\Parameter(
     *         name="contacts",
     *         in="query",
     *         type="string",
     *         description="array type [{'name':'abc','phone_numbers':[{'phone':1234567,'type_label':'mobile'},{'phone':90838838,'type_label':'home'}]}]",
     *         required=true,
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK",
     *     ),
     *     @SWG\Response(
     *         response=400,
     *         description="Please provide all data"
     *     )
     * )
     */

    public function addContactList(Request $request){
        try{
            $input = $request->all();
            $user = Auth::user();
            $rules = [
                'contacts' => 'required'
            ];
            $validator = Validator::make($request->all(),$rules);
            if ($validator->fails()) {
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                    $validator->getMessageBag()->first()), 400);
            }

            if(!is_array($input['contacts']))
                $input['contacts'] = json_decode($input['contacts'],true);
            if(is_array($input['contacts'])){
                foreach ($input['contacts'] as $key => $contact) {
                    $parent_id = null;
                    if(!is_array($contact['phone_numbers']))
                        $contact['phone_numbers'] = json_decode($contact['phone_numbers'],true);
                    if(is_array($contact['phone_numbers'])){
                        foreach ($contact['phone_numbers'] as $key => $value) {
                            if($value){
                                $parent = \App\Model\ContactList::firstOrCreate([
                                    'user_id'=>$user->id,
                                    'parent_id'=>$parent_id,
                                    'name'=>$contact['name'],
                                    'phone'=>$value['phone'],
                                    'type_label'=>$value['type_label'],
                                ]);
                                $parent_id = $parent->id;
                            }
                        }
                    }
                }
            }
            return response(['status' => "success", 'statuscode' => 200,
                                'message' => __('Contact List Added')], 200);
            }catch(Exception $ex){
                return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage().' '.$ex->getLine()], 500);
            }
    }

    /**
     * @SWG\Post(
     *     path="/contact-delete",
     *     description="Delete Contact",
     * tags={"Service Provider"},
     *     security={
     *     {"Bearer": {}},
     *   },
     *  @SWG\Parameter(
     *         name="id",
     *         in="query",
     *         type="number",
     *         description="contact ID",
     *         required=true,
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK",
     *     ),
     *     @SWG\Response(
     *         response=400,
     *         description="Please provide all data"
     *     )
     * )
     */

    public function deleteContact(Request $request){
        try{
            $input = $request->all();
            $user = Auth::user();
            $rules = [
                'id' => 'required|exists:contact_lists,id'
            ];
            $validator = Validator::make($request->all(),$rules);
            if ($validator->fails()) {
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                    $validator->getMessageBag()->first()), 400);
            }
            \App\Model\ContactList::where('id',$input['id'])->delete();
            \App\Model\ContactList::where('parent_id',$input['id'])->delete();
            return response(['status' => "success", 'statuscode' => 200,
                                'message' => __('Contact Deleted...')], 200);
            }catch(Exception $ex){
                return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage().' '.$ex->getLine()], 500);
            }
    }

    /**
     * @SWG\Post(
     *     path="/contact-message",
     *     description="Send SMS Contact",
     * tags={"Service Provider"},
     *     security={
     *     {"Bearer": {}},
     *   },
     *  @SWG\Parameter(
     *         name="body",
     *         in="query",
     *         type="string",
     *         description="Message",
     *         required=true,
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK",
     *     ),
     *     @SWG\Response(
     *         response=400,
     *         description="Please provide all data"
     *     )
     * )
     */

    public function sendContactMessage(Request $request){
        // $data['to'] = $request->phone;
        $input = $request->all();
        $user = Auth::user();
        // $rules = [
        //     // 'body' => 'required'
        // ];
        // $validator = Validator::make($request->all(),$rules);
        // if ($validator->fails()) {
        //     return response(array('status' => "error", 'statuscode' => 400, 'message' =>
        //         $validator->getMessageBag()->first()), 400);
        // }
        $lists = \App\Model\ContactList::select('id','name','phone')->orderBy('id','ASC')->where([
                'parent_id'=>null,
                'user_id'=>$user->id,
            ])->get();
        if($lists->count()<=0){
            return response(array(
                'status' => "success",
                'statuscode' => 200,
                'data'=>array('contact_added'=>false),
                'message' =>'You have not added any contact ,Please add contacts'), 200);
        }
        $f_keys = Helper::getClientFeatureKeys('social login','Twilio OTP');
        $accountSid = isset($f_keys['account_sid'])?$f_keys['account_sid']:"";
        $authToken = isset($f_keys['token'])?$f_keys['token']:"";
        $number = isset($f_keys['number'])?$f_keys['number']:"";
        try {
            $body = "Help Needed!
".$user->name." need help of you.
".$user->name." current location is ".$user->profile->location_name."
http://maps.google.com/maps?daddr=".$user->profile->lat.",".$user->profile->long."(".$user->profile->location_name.")";

            $twilio = new Client($accountSid, $authToken);
            foreach ($lists as $key => $list) {
                $this->sendSms($list,$twilio,$number,$body);
            }
            return response(array(
                'status' => "success",
                'statuscode' => 200,
                'data'=>array('contact_added'=>true),
                'message' =>'Message Sent'), 200);
        } catch (Exception $e) {
            return response(['status' => 'error', 'statuscode' => 500, 'message' => $e->getMessage()], 500);
        }
    }


    private function sendSms($list,$twilio,$number,$body){
        try{
            $contact = str_replace(['(',')','-','_',' '], '', $list->phone);
            if (strpos($contact, '+') !== false) {
            }else{
                $contact = '+1'.$contact;
            }
            $message = $twilio->messages->create($contact,["body" =>$body,"from" => $number]);
        }catch(Exception $ex){
            return true;
        }
    }

}
