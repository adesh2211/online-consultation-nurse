<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


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
use Config;
class CallerController extends Controller{


    public function startRequest(Request $request){
        $user = Auth::user();
      
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
                    'request_id'=>$sr_request->id,
                    'main_service_type'=>$main_service_type,
                    'action'=>$action,
                    'data'=>array('action'=>$action,'call_id'=>$call_id, 'request_id' => $sr_request->id, 'main_service_type' => $main_service_type),
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
                $calling_type = EnableService::where('type','audio/video')->first();
                if(config('client_connected') && Config::get("client_data")->domain_name == "care_connect_live"){
                    $sr_request->requesthistory->status = "in-progress";
                }
                $action = 'call';
                $calling_type = $calling_type->value;
            }else{
                $sr_request->requesthistory->status = "in-progress";
            }
            $sr_request->requesthistory->calling_type = $calling_type;
            $sr_request->requesthistory->save();
         //return json_encode($sr_request->requesthistory);
            if(strtolower($sr_request->servicetype->type)=='chat'){ 
                if(config('client_connected') && Config::get("client_data")->domain_name == "care_connect_live"){
                   // return $sr_request->from_user;
                    $message = new \App\Model\Message();
                    $message->user_id = $user->id;
                    $message->receiver_id = $sr_request->from_user;
                    $message->request_id = $sr_request->id;
                    $message->message = "Hi patient I am Available for chat lets discuss the problem";
                    $message->save();
                }
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
              
            }else{
                return 'fjkjjjhg';
            }
            return response(
                array(
                'status' =>"success",
                'statuscode' => 200,
                'request_id'=>$sr_request->id,
                'main_service_type'=>$main_service_type,
                'action'=>$action,
                'data'=>array('action'=>$action,'call_id'=>$call_id, 'request_id' => $sr_request->id, 'main_service_type' => $main_service_type),
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


    public static function postCallStausChange(Request $request) { 
        try{
            $user = Auth::user();
            $customer = false;
            if($user->hasrole('customer')){
                $customer = true;
            }
            if(isset($request->status)){
                $request->status = $request->status;
            }
            else
            {
                $request->status = $request->reqstatus;
            }
            $dateznow = new DateTime("now", new DateTimeZone('UTC'));
            $datenow = $dateznow->format('Y-m-d H:i:s');

            $current_date = strtotime($dateznow->format('Y-m-d'));
            $sr_request = \App\Model\Request::where(['id'=>$request->request_id])->first();
            if($sr_request->requesthistory->status!='completed' && $sr_request->requesthistory->status!='failed' && $sr_request->requesthistory->status!='canceled' ){
                if($request->status == 'start' || $request->status == 'reached' || $request->status == 'start_service' || $request->status == 'cancel_service' || $request->status == 'completed'){

                    $dateznow = new DateTime("now", new DateTimeZone('UTC'));
                    $datenow = $dateznow->format('Y-m-d H:i:s');
                    // print_r($datenow);die;
                    if($request->status == 'completed'){
                        $action_ignore = false;
                        if(\Config::get('client_connected') && \Config::get('client_data')->domain_name=='care_connect_live'){
                            //reorder token number
                         
                           $get_data = \App\Model\Request::where('to_user',Auth::user()->id)
                                          // ->where('id','!=',$request->request_id)
                                           ->where('booking_date',$sr_request->booking_date)
                                           ->whereHas('requesthistory', function ($query) {
                                               $query->whereNotIn('status',['failed','completed']);
                                           })->orderby('id','asc')
                                           ->where('token_number', '!=', NULL)->get();

                           $get_selected_data = \App\Model\Request::where('to_user',Auth::user()->id)
                               ->where('id','=',$request->request_id)
                               ->where('booking_date',$sr_request->booking_date)
                               ->whereHas('requesthistory', function ($query) {
                                   $query->whereNotIn('status',['failed','completed']);
                               })
                               ->where('token_number', '!=', NULL)->first();
                          // $i = 1;

                           $current_token_selected = $get_selected_data->token_number;

                           foreach($get_data as $record)
                           {
                              
                               if($record->id == $request->request_id)
                               {
                                   
                                       $update_token_number = Null;
                                 
                               }
                               else
                               {
               

                                   if($current_token_selected != null)
                                   {
                                       $update_token_number = $record->token_number;

                                       if($record->token_number > $current_token_selected)
                                       {
                                           $update_token_number = $record->token_number - 1;
                                       }
                                   }


                                   
                               }
                              
                               $update_token = \App\Model\Request::where('to_user', Auth::user()->id)
                                               ->where('booking_date',$sr_request->booking_date)
                                               ->where('id',$record->id)
                                               ->whereHas('requesthistory', function ($query) {
                                                   $query->whereNotIn('status',['failed','completed']);
                                               })
                                               ->update([
                                                   'token_number'  => $update_token_number
                                                   ]);
                               if($record->id != $request->request_id && $record->token_number > $current_token_selected)
                               {
                                   $status = ucwords(strtolower(str_replace('_', ' ', 'token updated')));
                                   $notification = new Notification();
                                   $notification->sender_id = $user->id;
                                   if($customer){
                                       $notification->receiver_id = $record->to_user;
                                   }else{
                                       $notification->receiver_id = $record->from_user;
                                   }
                                   $notification->module_id = $record->id;
                                   $notification->module ='request';
                                   $notification->notification_type = strtoupper($status);
                                   $notification->message =__('notification.token_update_req', ['token_number' => $update_token_number]);
                                   $notification->save();
                                   $notification->push_notification(
                                       array($notification->receiver_id),
                                       array('pushType'=>strtoupper($status),
                                           'message'=>__('notification.token_update_req', ['token_number' => $update_token_number]),
                                           'request_time'=>$record->booking_date,
                                           'service_type'=>$record->servicetype->type,
                                           'sender_name'=>$user->name,
                                           'sender_image'=>$user->profile_image,
                                           'request_id'=>$record->id,
                                           'call_id'=>'',
                                           'token_number' => $update_token_number
                                       ));
                               }
                          //$i--;   
                           }

                         

                       }
                      
                        $request_date =  \App\Model\RequestDate::where(['request_id'=>$sr_request->id])->orderBy("id","DESC")->first();
                        if($request_date && !$action_ignore){
                            $end_date_time = strtotime($request_date->end_date_time);
                            $c_end_date_time = strtotime($datenow);
                            if($end_date_time>=$c_end_date_time){
                                $message = "you can't mark status complete beofre service end time";
                                return response(array('status' => "error", 'statuscode' => 400, 'message' =>__($message)), 400);
                            }
                        }
                        if(!Helper::chargeFromSP()){
                            $deposit_to = array(
                                'user'=>$sr_request->sr_info,
                                'from_id'=>$sr_request->cus_info->id,
                                'request_id'=>$sr_request->id,
                                'status'=>'succeeded'
                            );
                            \App\Model\Transaction::updateDeposit($deposit_to);
                        }
                    }
                    $sr_request->requesthistory->status = strtolower($request->status);
                    $sr_request->requesthistory->save();
                }
                if(isset($request->lat) && isset($request->long)){
                    $ls = new  \App\Model\LastLocation();
                    $ls->lat = $request->lat;
                    $ls->long = $request->long;
                    $ls->request_id = $request->request_id;
                    $ls->user_id = $sr_request->sr_info->id;
                    $ls->save();
                }
                $status = ucwords(strtolower(str_replace('_', ' ', $request->status)));
                $notification = new Notification();
                $notification->sender_id = $user->id;
                if($customer){
                    $notification->receiver_id = $sr_request->to_user;
                }else{
                    $notification->receiver_id = $sr_request->from_user;
                }
                $category_name = '';
                if($sr_request->sr_info->getCategoryData($sr_request->sr_info->id)){
                    $category_name = $sr_request->sr_info->getCategoryData($sr_request->sr_info->id)->name;
                }
                $input = $request->all();
                $custominfo = new \App\Model\CustomInfo();
                $custominfo->info_type = 'request_status';
                $custominfo->ref_table = 'request';
                $custominfo->ref_table_id = $sr_request->id;
                $custominfo->status = $request->status;
                $custominfo->save();

                $call_id = null;
                if(isset($input['call_id'])){
                    $call_id = $input['call_id'];
                }
                $notification->module_id = $sr_request->id;
                $notification->module ='request';
                $notification->notification_type = strtoupper($request->status);
                $notification->message =__('notification.call_req_text', ['user_name' => $user->name,'call_status'=>$status]);
                $notification->save();
                //$notification->push_notification(
                    // array($notification->receiver_id),
                    // array('pushType'=>strtoupper($request->status),
                    //     'message'=>__('notification.call_req_text', ['user_name' => $user->name,'call_status'=>$status]),
                    //     'request_time'=>$sr_request->booking_date,
                    //     'service_type'=>$sr_request->servicetype->type,
                    //     'sender_name'=>$user->name,
                    //     'sender_image'=>$user->profile_image,
                    //     'vendor_category_name'=>$category_name,
                    //     'request_id'=>$sr_request->id,
                    //     'call_id'=>$call_id,
                    // ));
                    // if(!Helper::chargeFromSP()){
                    //     $deposit_to = array(
                    //         'user'=>$sr_request->sr_info,
                    //         'from_id'=>$sr_request->cus_info->id,
                    //         'request_id'=>$sr_request->id,
                    //         'status'=>'succeeded'
                    //     );
                    //     \App\Model\Transaction::updateDeposit($deposit_to);
                    // }
                return response(['status' => "success",
                    'statuscode' => 200,
                    'message' => __('notification.call_req_text', ['user_name' => $user->name,'call_status'=>$status]),
                    'data'=>['call_id'=>$call_id,'status'=>$request->status]
                ], 200);
            }else{
                $message = "can't change status because status is ".$sr_request->requesthistory->status;
            }
            return response(array('status' => "error", 'statuscode' => 400, 'message' =>__($message)), 400);
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage()], 500);
        }
    }


    public function getjistitest($request_id, $service_type, Request $request)
    {
        

        // 
        // check request id exists
        $user = Auth::user();
        if($request_id)
        {
            $req_id_exist_or_not = \App\Model\Request::where('id',$request_id)->first();

            if($req_id_exist_or_not)
            {

                // currently logged in user is present in from or to user
                if(Auth::user()->hasRole('service_provider'))
                {
                    $check_logged_user = \App\Model\Request::where('id',$request_id)
                        ->where('to_user' , Auth::user()->id)
                    ->first();
                    $check_logged_user->self_user = User::select('id', 'name', 'email','phone','profile_image')->with('profile')->where('id',$check_logged_user->to_user)->first();
                    $check_logged_user->display_user = User::select('id', 'name', 'email','phone','profile_image')->where('id',$check_logged_user->from_user)->first();
                    $redirect_url = '/user/requests';
                }
                else if(Auth::user()->hasRole('customer'))
                {
                    $check_logged_user = \App\Model\Request::where('id',$request_id)
                        ->where('from_user' , Auth::user()->id)
                    ->first();
                    $check_logged_user->self_user = User::select('id', 'name', 'email','phone','profile_image')->where('id',$check_logged_user->from_user)->first();
                    $check_logged_user->display_user = User::select('id', 'name', 'email','phone','profile_image')->where('id',$check_logged_user->to_user)->first();
                    $redirect_url =  '/user/appointments';
                }else{
                  $check_logged_user = \App\Model\Request::where('id',$request_id)->first();
                    $check_logged_user->self_user = User::select('id', 'name', 'email','phone','profile_image')->where('id',$check_logged_user->from_user)->first();
                    $check_logged_user->display_user = User::select('id', 'name', 'email','phone','profile_image')->where('id',$check_logged_user->to_user)->first();
                    $redirect_url =  '/user/appointments';
                }
              
               
                if($check_logged_user)
                {
                 
                    // generate room id
                    $jistiid = '202001';

                    $room_id = 'Call_'.$jistiid.'_'.$request_id;
                    $id = $check_logged_user->id;
                    $request = \App\Model\Request::where('id',$request_id)->first();
                    $sender_id = $request->to_user;
                    $receiver_id = $request->from_user;
                    // $last_message = \App\Model\Message::getLastMessage($check_logged_user);
                     //$check_logged_user->unReadCount = \App\Model\Message::getUnReadCount($check_logged_user,$user->id);
                     $date = Carbon::parse($check_logged_user->booking_date,'UTC')->setTimezone('Asia/Kolkata');
                     $check_logged_user->booking_date = $date->isoFormat('D MMMM YYYY, h:mm:ss a');
                     $check_logged_user->time = $date->isoFormat('h:mm a');
                     $check_logged_user->requesthistory = $check_logged_user->requesthistory;
                     //$check_logged_user->last_message = $last_message;
                     $check_logged_user->duration = $check_logged_user->duration;
                     $check_logged_user->status = $check_logged_user->status;
                     if(config('client_connected') && Config::get("client_data")->domain_name == "care_connect_live")
                     {
                        return view('vendor.care_connect_live.jisttest')->with('redirect_url',$redirect_url)->with('request_id',$request_id)->with('sender_id',$sender_id)->with('receiver_id',$receiver_id)->with('room_id', $room_id)->with('userdata',$check_logged_user)->with('call_type',$service_type);
                     }
                     else
                     {
                        return view('vendor.iedu.jisttest')->with('check_logged_user',$check_logged_user)->with('redirect_url',$redirect_url)->with('sender_id',$sender_id)->with('receiver_id',$receiver_id)->with('request_id',$request_id)->with('room_id', $room_id)->with('userdata',$check_logged_user)->with('call_type',$service_type);
                     }
                   
                     // limit features
                   
                }
                else
                {
                    return response(['status'=>'error', 'message' => "Logged User not Exist"]);
                }

            }
        }
        else
        {
            return response(['status'=>'error', 'message' => "Request Doesn't Exist"]);
        }
         
      
    }
  

}