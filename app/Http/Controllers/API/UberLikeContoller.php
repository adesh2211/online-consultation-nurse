<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Carbon\Carbon;
use App\Helpers\Helper2;

use App\User;
use App\Notification;
use Illuminate\Support\Facades\Auth;
use Validator,Hash,Mail,DB;
use DateTime,DateTimeZone;
use Redirect,Response,File,Image;
use Illuminate\Support\Facades\URL;
use App\Model\Role,App\Model\CategoryServiceType;
use App\Model\Wallet,App\Model\EnableService;
use App\Model\Package;
use App\Model\Feedback,App\Model\Service;
use App\Model\BookingOrderRequest,App\Model\Category;
use App\Model\ServiceProviderFilterOption;
use App\Model\UserPackage,App\Model\RequestDetail;
use App\Model\Profile;
use App\Model\Transaction;
use App\Model\RequestDate;
use App\Model\Request as RequestData;
use App\Model\Payment;
use App\Model\Card,App\Model\Coupon,App\Model\CouponUsed;
use App\Model\SocialAccount;
use App\Model\SpServiceType;
use Socialite,Exception;
use App\Model\Image as ModelImage;
use Intervention\Image\ImageManager;
use App\Helpers\Helper,Config;
use App\Jobs\RequestReminder;
use App\Jobs\EmergencyRequestProcess;
class UberLikeContoller extends Controller
{
    /**
     * @SWG\Post(
     *     path="/v2/confirm-request",
     *     description="postConfirmRequest version2 to send notification doctors",
     * tags={"Customer"},
     *     security={
     *     {"Bearer": {}},
     *   },
     *  @SWG\Parameter(
     *         name="date",
     *         in="query",
     *         type="string",
     *         description="date e.g YYYY-MM-DD=>2000-02-20",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="time",
     *         in="query",
     *         type="string",
     *         description="time e.g 22:10",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="end_date",
     *         in="query",
     *         type="string",
     *         description="date e.g YYYY-MM-DD=>2000-02-20 for NurseLynx",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="end_time",
     *         in="query",
     *         type="string",
     *         description="time e.g 22:10 for NurseLynx",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="service_id",
     *         in="query",
     *         type="string",
     *         description="main service_id",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="category_id",
     *         in="query",
     *         type="string",
     *         description="main category_id",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="schedule_type",
     *         in="query",
     *         type="string",
     *         description="schedule type instant, schedule,date_time",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="coupon_code",
     *         in="query",
     *         type="string",
     *         description="Coupon Code",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="tier_id",
     *         in="query",
     *         type="number",
     *         description="tier id",
     *         required=false,
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
    public function postConfirmRequest(Request $request) { 
        try{
            $user = Auth::user();
            $validator = $this->validatorConfirmRequest($request);
            if($validator){
                return $validator;
            }
            $input = $request->all();
            $request_data = null;
            $timezone = $request->header('timezone');
            if(!$timezone){
                $timezone = 'Asia/Kolkata';
            }
            $distance = 0;
            $category_id = Category::find($input['category_id']);
            $categoryservicetype_id = CategoryServiceType::where([
                'category_id'=>$category_id->id,
                'service_id'=>$input['service_id']
            ])->first();
            if(!$categoryservicetype_id){
            	return response(['status' => 'error', 'statuscode' => 400, 'message' => 'Price not set for this service please contact to Admin'], 400);
            }
            $spservicetype_id = null;
            $slot_duration = \App\Model\EnableService::where('type','slot_duration')->first();
            $unit_price = \App\Model\EnableService::where('type','unit_price')->first();
            $per_minute = $categoryservicetype_id->price_fixed/$unit_price->value;
            $slot_minutes = $slot_duration->value;
            $add_slot_second = $slot_duration->value * 60;
            if($request_data){
                $total_charges = $request_data->requesthistory->total_charges;
            }else{
                $total_charges = $slot_minutes * $per_minute;
            }

            $total_hours = 0;
            $grand_total = $slot_minutes * $per_minute;
            if(isset($input['end_date']) && isset($input['end_time'])){
                $start  = new Carbon($input['date'].' '.$input['time']);
                $end    = new Carbon($input['end_date'].' '.$input['end_time']);
                $days = $start->diffInDays($end);

                $start  = new Carbon($input['time']);
                $end    = new Carbon($input['end_time']);

                $total_hours = round($end->diffInSeconds($start)/3600,2);

                $total_hours = ($days+1)*$total_hours;
                $total_charges = ($total_hours*60) * $per_minute;
                $grand_total = ($total_hours*60) * $per_minute;
            }

            $distance = 0;
            $distance_price = 0;
            $distance_price_per_km = 0;

            $discount = 0;
            $timezone = $request->header('timezone');
            if(!$timezone){
                $timezone = 'Asia/Kolkata';
            }
            if($request->schedule_type=='schedule' || $request->schedule_type=='date_time'){
                $connect_now_validation_disable = false;
                $datenow = Carbon::parse($request->date.' '.$request->time,$timezone)->setTimezone('UTC')->format('Y-m-d H:i:s');
                if(isset($input['end_date']) && isset($input['end_time'])){
                    $end_time_slot_utcdate = Carbon::parse($input['end_date'].' '.$input['end_time'])->setTimezone('UTC')->format('Y-m-d H:i:s');
                }else{
                    $end_time_slot_utcdate = Carbon::parse($datenow)->addSeconds($add_slot_second)->setTimezone('UTC')->format('Y-m-d H:i:s');
                }
                $user_time_zone_slot = Carbon::parse($datenow)->setTimezone($timezone)->format('h:i a');
                $user_time_zone_date = Carbon::parse($datenow)->setTimezone($timezone)->format('Y-m-d');
            }else{
                $data = [];
                while ($data==false) {
                    $data = $this->checkSlotFullOrNot($slot_duration,$timezone,$add_slot_second,$input,$request_data);
                    $slot_duration->value = $slot_duration->value + 30;
                }
                $user_time_zone_date = $data['user_time_zone_date'];
                $user_time_zone_slot = $data['user_time_zone_slot'];

            }
       
            $tier_charges = 0;
            if(isset($input['tier_id'])){
                $tier = \App\Model\Tier::find($input['tier_id']);
                // $tier_charges = $tier->price;
                // if($total_hours){
                //     $tier_charges = $total_hours * $tier_charges;
                // }
                // $per_minute = $tier->price/60;
                // $grand_total = $grand_total + $tier_charges;
                // $total_charges = $total_charges + $tier_charges;
                // $grand_total = $tier_charges;
                // $total_charges = $tier_charges;
            }
            $service_tax = 0;
            $tax_percantage = 0;
            $transaction_fee = \App\Model\EnableService::where('type','service_charge')->first();
            if($transaction_fee){
                $tax_percantage = $transaction_fee->value;
                $service_tax = round(($total_charges * $tax_percantage)/100,2);
                $grand_total = $service_tax + $total_charges;
            }

            // Coupon Validation
            $coupon_validation = [];
            $coupon = false;
            if(isset($request->coupon_code) && $request_data==null){
                $coupon_validation = Helper2::couponCodeValidation($request->coupon_code,$user,$total_charges,$service_tax);
                if($coupon_validation['status']=='error'){
                   return response($coupon_validation,400);
                }
                if($coupon_validation['status']=='success'){
                    $coupon = true;
                    $grand_total = $coupon_validation['grand_total'];
                    $discount = $coupon_validation['discount'];
                }
            }
            $minimum_balance_value = null;
            $minimum_balance = \App\Model\EnableService::where('type','minimum_balance')->first();
            if($minimum_balance)
                $minimum_balance_value = $minimum_balance->value;
            return response([
                'status' =>"success",
                'statuscode' => 200,'message' => __('Booking confirmed'),
                'data'=>[
                    'tier_charges'=>$tier_charges,
                    'total'=>$total_charges - $tier_charges,
                    'service_tax'=>(int)$service_tax,
                    'tax_percantage'=>round($tax_percantage,2),
                    'discount'=>$discount,
                    'total_hours'=>$total_hours,
                    'grand_total'=>$grand_total,
                    'book_slot_time'=>$user_time_zone_slot,
                    'book_slot_date'=>$user_time_zone_date,
                    'coupon'=>$coupon,
                    'minimum_balance'=>$minimum_balance_value,
                    'distance_price_per_km'=>$distance_price_per_km,
                    'distance_price'=>$distance_price,
                    'distance'=>$distance,
                ]], 200);
            return response(array('status' => "error", 'statuscode' => 400, 'message' =>__($message)), 400);
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage().' '.$ex->getLine()], 500);
        }
    }

    private function validatorConfirmRequest($request){
        $rules = ['schedule_type'=>["required" , "max:255", "in:instant,schedule,date_time"],
        		'category_id'=>'required',
    		];
        $rules['service_id'] = 'required|exists:services,id';
        if(isset($request->schedule_type) && strtolower($request->schedule_type)=='schedule'){
            $rules['date'] = 'required|date|date_format:Y-m-d';
            $rules['time'] = 'required|date_format:H:i';
        }else if(isset($request->schedule_type) && strtolower($request->schedule_type)=='date_time'){
            $rules['date'] = 'required|date|date_format:Y-m-d';
            $rules['time'] = 'required|date_format:H:i';
            $rules['end_date'] = 'required|date|date_format:Y-m-d';
            $rules['end_time'] = 'required|date_format:H:i';
        }
        if(isset($request->coupon_code)){
            $request->merge(['coupon_code' => strtoupper($request->coupon_code)]);
            $rules['coupon_code'] = 'required|exists:coupons,coupon_code';
        }
        if(isset($request->tier_id)){
            $rules['tier_id'] = 'required|exists:tiers,id';
        }
        $validator = Validator::make($request->all(),$rules);
        if ($validator->fails()) {
            return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                $validator->getMessageBag()->first()), 400);
        }else{
            return false;
        }
    }

         /**
     * @SWG\Post(
     *     path="/v2/create-request",
     *     description="postCreateRequest",
     * tags={"Customer"},
     *     security={
     *     {"Bearer": {}},
     *   },
     *  @SWG\Parameter(
     *         name="date",
     *         in="query",
     *         type="string",
     *         description="date e.g YYYY-MM-DD=>2000-02-20",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="time",
     *         in="query",
     *         type="string",
     *         description="date e.g 22:10",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="end_date",
     *         in="query",
     *         type="string",
     *         description="end date e.g YYYY-MM-DD=>2000-02-20",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="end_time",
     *         in="query",
     *         type="string",
     *         description="end time e.g 22:10",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="category_id",
     *         in="query",
     *         type="string",
     *         description="category_id",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="service_id",
     *         in="query",
     *         type="string",
     *         description="main service_id",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="schedule_type",
     *         in="query",
     *         type="string",
     *         description="schedule type instant, schedule,date_time",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="coupon_code",
     *         in="query",
     *         type="string",
     *         description="Coupon Code",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="tier_id",
     *         in="query",
     *         type="string",
     *         description="Tier Id of NurseLynx",
     *         required=false,
     *     ), 
     *  @SWG\Parameter(
     *         name="tier_options",
     *         in="query",
     *         type="string",
     *         description="Tier Options as array [{'id':'option_id','type':'1 for need some help and 2 for need much help'}]",
     *         required=false,
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
    public function postCreateRequest(Request $request) {
        try{
            $user = Auth::user();
            $validator = $this->validatorConfirmRequest($request);
            if($validator){
                return $validator;
            }
            $input = $request->all();
            $total_hours = 0;
            $request_data = null;
            $category_id = Category::find($input['category_id']);
            $categoryservicetype_id = CategoryServiceType::where([
                'category_id'=>$category_id->id,
                'service_id'=>$input['service_id']
            ])->first();
            if(!$categoryservicetype_id){
            	return response(['status' => 'error', 'statuscode' => 400, 'message' => 'Price not set for this service please contact to Admin'], 400);
            }

            $sp_ids = SpServiceType::whereHas('doctor_data.roles', function ($query) {
                           $query->where('name','service_provider');
                        })->where('available','1')->where('category_service_id',$categoryservicetype_id->id)->pluck('sp_id')->toArray();
            if(count($sp_ids)==0){
            	return response([
            		'status' => 'error',
            		'statuscode' => 400,
            		'message' => 'There is no doctor available for this service'
            	], 400);
            }
            $slot_duration = \App\Model\EnableService::where('type','slot_duration')->first();
            $unit_price = \App\Model\EnableService::where('type','unit_price')->first();
            $per_minute = $categoryservicetype_id->price_fixed/$unit_price->value;
            $slot_minutes = $slot_duration->value;
            $add_slot_second = $slot_duration->value * 60;
            $total_charges = $slot_minutes * $per_minute;
            $grand_total= $g_total = $slot_minutes * $per_minute;

            if(isset($input['end_date']) && isset($input['end_time'])){
                $start  = new Carbon($input['date'].' '.$input['time']);
                $end    = new Carbon($input['end_date'].' '.$input['end_time']);
                $days = $start->diffInDays($end);

                $start  = new Carbon($input['time']);
                $end    = new Carbon($input['end_time']);

                $total_hours = round($end->diffInSeconds($start)/3600,2);

                $total_hours = ($days+1)*$total_hours;
                $total_charges = ($total_hours*60) * $per_minute;
                $grand_total = $g_total =  ($total_hours*60) * $per_minute;
            }

            $distance = 0;
            $distance_price = 0;
            $distance_price_per_km = 0;

            $grand_total= $g_total = $total_charges = $grand_total + $distance_price;
            $service_tax = 0;
            $tax_percantage = 0;
            /* For add Tier */
            if(isset($input['tier_id'])){
                $tier = \App\Model\Tier::find($input['tier_id']);
                // $per_minute = $tier->price/60;
                // $tier_charges = $tier->price;
                // if($total_hours){
                //     $tier_charges = $total_hours * $tier_charges;
                // }
                //$grand_total = $grand_total + $tier_charges;
                // $grand_total = $tier->price;
                //$total_charges = $total_charges + $tier_charges;
                // $total_charges =  $tier->price;
            }

            $transaction_fee = \App\Model\EnableService::where('type','service_charge')->first();
            if($transaction_fee){
                $tax_percantage = $transaction_fee->value;
                $service_tax = round(($total_charges * $tax_percantage)/100,2);
                $grand_total = $service_tax + $total_charges;
            }

            $discount = 0;
            $timezone = $request->header('timezone');
            if(!$timezone){
                $timezone = 'Asia/Kolkata';
            }
            $input['timezone'] = $timezone;
            $input['service_tax'] = $service_tax;
            $input['tax_percantage'] = $tax_percantage;
            $coupon_validation = [];
            $coupon = false;
            if(isset($request->coupon_code) && $request_data==null){
                $coupon_validation = Helper2::couponCodeValidation($request->coupon_code,$user,$total_charges,$service_tax);
                if($coupon_validation['status']=='error'){
                   return response($coupon_validation,400);
                }
                if($coupon_validation['status']=='success'){
                    $coupon = true;
                    $grand_total= $g_total = $coupon_validation['grand_total'];
                    $discount = $coupon_validation['discount'];
                }
            }
            $input['coupon_validation'] = $coupon_validation;
            $wallet_type = 'user_wallet';
            $user_wallet = $user;
            if($user_wallet->wallet->balance<$grand_total){
                $amnt = $grand_total - $user_wallet->wallet->balance;
                    return response([
                        'status' => "success",
                        'statuscode' => 200,
                        'message' => __("Request could not be created, need to add money $amnt"),
                        'data'=>[
                        'amountNotSufficient'=>true,
                        'minimum_balance'=>null,
                        'message'=>"Request could not be created, need to add money $amnt"]
                    ], 200);
            }
            $input['total_hours'] = $total_hours;
            $input['discount'] = $discount;
            $input['grand_total'] = $grand_total;
            $input['total_charges'] = $total_charges;
            $input['user'] = $user;
            $input['sp_ids'] = $sp_ids;
            return Helper2::createOrderTempRequest($input);
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage().' '.$ex->getLine()], 500);
        }
    }

    /**
     * @SWG\Get(
     *     path="/v2/pendig-requests",
     *     description="getPendingRequest",
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
    public function getPendingRequest(Request $request) {
        try{
            $user = Auth::user();
            $per_page = (isset($request->per_page)?$request->per_page:10);
            $booking_requests = BookingOrderRequest::where([
            	'sp_id'=>$user->id,
            	'status'=>'pending'
            ])->whereHas('bookingorder',function($query){
            	return $query->where('main_status','pending');
            })->orderBy('id', 'desc')->cursorPaginate($per_page);
            foreach ($booking_requests as $key => $booking_request) {
            	$booking_request = BookingOrderRequest::getDetailOrder($booking_request);
            }
            $after = null;
            if($booking_requests->meta['next']){
                $after = $booking_requests->meta['next']->target;
            }
            $before = null;
            if($booking_requests->meta['previous']){
                $before = $booking_requests->meta['previous']->target;
            }
            $per_page = $booking_requests->perPage();
            return response(['status' => "success", 'statuscode' => 200,
                                'message' => __('Booking Order Listing'), 'data' =>['pending_requests'=>$booking_requests->items(),'after'=>$after,'before'=>$before,'per_page'=>$per_page]], 200);
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage().' '.$ex->getLine()], 500);
        }
    }

    /**
     * @SWG\Post(
     *     path="/v2/accept-request",
     *     description="postAcceptRequest",
     * tags={"Service Provider"},
     *     security={
     *     {"Bearer": {}},
     *   },
     *  @SWG\Parameter(
     *         name="id",
     *         in="query",
     *         type="number",
     *         description="Request Id",
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
    public  function postAcceptRequest(Request $request) {
    	try{
	    	$user = Auth::user();
	    	$rules = ['id' => 'required'];
            $validator = Validator::make($request->all(),$rules);
            if ($validator->fails()) {
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                    $validator->getMessageBag()->first()), 400);
            }

            $booking_request = BookingOrderRequest::where(['sp_id'=>$user->id,'status'=>'pending'])->whereHas('bookingorder',function($query){
            	return $query->where('main_status','pending');
            })->where('id',$request->id)->first();

            if(!$booking_request){
            	return response(array(
            		'status' =>'error',
            		'statuscode' => 400,
            		'message' =>__("Request may be accepted by someone else or timeout")), 400);
            }

            $data = Helper2::createRequest($booking_request,$user);
            $sr_request = $data['data']['sr_request'];
    		$re_history = \App\Model\RequestHistory::where('request_id',$sr_request->id)->first();
    		$re_history->status = 'accept';
    		$re_history->save();
    		$booking_request->bookingorder->main_status = 'success';
    		$booking_request->bookingorder->module_table_id = $sr_request->id;
    		$booking_request->bookingorder->save();
    		$booking_request->status='success';
    		$booking_request->save();
            $notification = new Notification();
            $notification->sender_id = $sr_request->to_user;
            $notification->receiver_id = $sr_request->from_user;
            $notification->module_id = $sr_request->id;
            $notification->module ='request';
            $notification->notification_type ='REQUEST_ACCEPTED';
            $notification->message =__('notification.accept_req_text', ['vendor_name' => $sr_request->sr_info->name]);;
            $notification->save();
            $notification->push_notification(array($sr_request->from_user),array('pushType'=>'REQUEST_ACCEPTED','request_id'=>$sr_request->id,'message'=>__('notification.accept_req_text', ['vendor_name' => $sr_request->sr_info->name])));
            return response(['status' => "success", 'statuscode' => 200,'message' => __('Request Accepted '),'data'=>[
            	'request_detail'=>['id'=>$sr_request->id]]
            	], 200);

    	}catch(Exception $ex){
    		return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage()], 500);
    	}
    }

    /**
     * @SWG\Post(
     *     path="/v2/cancel-request",
     *     description="postCancelRequest",
     * tags={"Service Provider"},
     *     security={
     *     {"Bearer": {}},
     *   },
     *  @SWG\Parameter(
     *         name="id",
     *         in="query",
     *         type="number",
     *         description="Request Id",
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
    public function postCancelRequest(Request $request){
    	try{
	    	$user = Auth::user();
	    	$rules = ['id' => 'required'];
	        $validator = Validator::make($request->all(),$rules);
	        if ($validator->fails()) {
	            return response(array('status' => "error", 'statuscode' => 400, 'message' =>
	                $validator->getMessageBag()->first()), 400);
	        }

	        $booking_request = BookingOrderRequest::where(['sp_id'=>$user->id,'status'=>'pending'])->where('id',$request->id)->first();
	        if(!$booking_request){
	        	return response(array(
	        		'status' =>'error',
	        		'statuscode' => 200,
	        		'message' =>__("request already accepted")), 200);
	        }
	        $booking_request->status='canceled';
	    	$booking_request->save();
	    	return response(['status' => "success", 'statuscode' => 200,'message' => __('Request Accepted '),'data'=>[
	            	'request_detail'=>['id'=>$booking_request->id]]
	            	], 200);
    	}catch(Exception $ex){
    		return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage()], 500);
    	}

    }

    /**
     * @SWG\Get(
     *     path="/v2/doctor-list",
     *     description="getDoctorList",
     * tags={"Service Provider"},
     *  @SWG\Parameter(
     *         name="service_type",
     *         in="query",
     *         type="string",
     *         description="service_type e.g chat,call,all,consult_online, home_care, clinic_appointment, emergency",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="service_id",
     *         in="query",
     *         type="string",
     *         description="service_id",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="category_id",
     *         in="query",
     *         type="string",
     *         description="service provider category id",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="search",
     *         in="query",
     *         type="string",
     *         description="search name of vendor",
     *         required=false,
     *     ),  
     *  @SWG\Parameter(
     *         name="lat",
     *         in="query",
     *         type="string",
     *         description="lattitude ",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="long",
     *         in="query",
     *         type="string",
     *         description="longitude",
     *         required=false,
     *     ),   
     *  @SWG\Parameter(
     *         name="address_id",
     *         in="query",
     *         type="string",
     *         description="address_id of preference_option_id intely",
     *         required=false,
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
    public  function getDoctorList(Request $request,SpServiceType $subscription) {
        try{
            $doctors = [];
            $service_id = null;
            $service_ids = null;
            $service_type = null;
            $per_page = (isset($request->per_page)?$request->per_page:10);
            $cursor_Paginate =  true;
            $request->radius = 50;
            $set_radius = \App\Model\EnableService::where('type','set_radius')->first();
            if($set_radius)
                $request->radius = $set_radius->value;

            $pageNumber = (isset($request->page)?$request->page:1);
            $service_id = isset($request->service_id)?$request->service_id:null;
            $request->service_type = isset($request->service_type)?$request->service_type:'all';
            $input = $request->all();
            $subscription = $subscription->newQuery();
            $state_id = null;
            $state_name = null;
            /* for Consultant Listing */
            $consultant_ids = User::whereHas('roles', function ($query) {
                           $query->where('name','service_provider');
                        })->orderBy('id','DESC')->pluck('id')->toArray();
           
            if($request->service_type!='all'){
                if($request->service_type=='home_care'){
                    $request->service_type = 'Home Appointment';
                }else if($request->service_type=='clinic_appointment'){
                    $request->service_type = 'Clinic Appointment';
                }
                $service_type = Service::select('id')
                ->where(function($q) use($request) {
                              $q->where('type', $request->service_type)
                                ->orWhere('service_type', $request->service_type);
                })->first();
                if($service_type){
                    $service_ids[] = $service_type->id;
                }
                if($request->service_type=='consult_online'){
                   $service_ids = Service::select('id')->whereIn('type',['video call','call','audio','Call','Video Call','Audio'])->pluck('id')->toArray();
                }
                if(strtolower($request->service_type)=='emergency'){
                   $service_ids = Service::select('id')->whereIn('id',$service_ids)->pluck('id')->toArray();
                   
                }
            }
            if($request->service_type!=='all'){
                $categoryservicetypeids = [];
                if(is_array($service_ids) && count($service_ids)>0){
                    $categoryservicetypeids = CategoryServiceType::whereIn('service_id',$service_ids)->pluck('id');
                }
                $subscription->whereIn('category_service_id',$categoryservicetypeids);
                //return json_encode($categoryservicetypeids);
            }

            if($service_id!=null){
                $categoryservicetypeids = [];
                $categoryservicetypeids = CategoryServiceType::where('service_id',$service_id)->pluck('id');
                $subscription->whereIn('category_service_id',$categoryservicetypeids);
                
            }

            if($service_ids!=null){
                $categoryservicetypeids = [];
                $categoryservicetypeids = CategoryServiceType::whereIn('service_id',$service_ids)->pluck('id');
                $subscription->whereIn('category_service_id',$categoryservicetypeids);
                
            }

            $available = true;
            if($request->has('search')){
                if($request->search){
                    $available = false;
                    $consultant_ids = User::whereLike('name',$request->search)->whereIn('id',$consultant_ids)->groupBy('id')->pluck('id');
                }
            }

            $lat_long = false;
            if($request->service_type=='home_visit' || $request->service_type=='clinic_visit'){
                $lat_long = true;
            }
            if($request->has('lat') && $request->has('long') && isset($input['lat']) && isset($input['long']) &&  $input['lat']!==null && $input['long']!==null){


            	$sqlDistance = \DB::raw("( 111.045 * acos( cos( radians(" . $input['lat'] . ") )* cos( radians(custom_infos.lat) ) * cos( radians(custom_infos.long) - radians(" . $input['long']  . ") ) + sin( radians(" . $input['lat']  . ") ) * sin( radians(  custom_infos.lat ) ) ) )");

            	$ref_table_ids =  \DB::table('custom_infos')
                    ->select('*')
                    ->selectRaw("{$sqlDistance} AS distance")
                    ->havingRaw('distance BETWEEN ? AND ?', [0,isset($request->radius)?$request->radius/100:50/100])
                    ->orderBy('distance',"DESC")->pluck('ref_table_id')->toArray();
                $subscription = $subscription->whereIn('id',$ref_table_ids);
            }
            $timezone = $request->header('timezone');
            if(!$timezone){
                $timezone = 'Asia/Kolkata';
            }
            if ($request->has('category_id')) {

                $subscription->whereHas('categoryServiceProvider', function($q) use($request){
                    if(isset($request->category_id)){
                        $q->where('category_id',$request->category_id);
                    }
                });

            }

            $subscription->whereIn('sp_id',$consultant_ids);
            $subscription->where('available','1')->with('doctor_data')->groupBy('sp_id')
            ->whereHas('doctor_data', function($query){
                    return $query->where('account_verified','!=',null);
            })
            ->whereHas('doctor_data.roles', function($query){
                    return $query->where('name','service_provider');
            });
            if(is_object($consultant_ids))
                $consultant_ids = $consultant_ids->toArray();
            if(!$cursor_Paginate){
                $subscription->join('profiles as pp', 'pp.user_id', '=', 'sp_service_types.sp_id');
                $subscription->select('sp_service_types.*','pp.id AS profile_id');
                $subscription->orderBy('pp.rating', 'DESC');
                $subscription->orderByRaw('FIELD(sp_id,'.implode(",", $consultant_ids).')');
                $doctors = $subscription->paginate($per_page, ['*'], 'page', $pageNumber);
            }else{
               $doctors = $subscription->orderBy('id','asc')->cursorPaginate($per_page);
            }
            $unit_price = EnableService::where('type','unit_price')->first();
            $slot_duration = EnableService::where('type','slot_duration')->first();
            foreach ($doctors as $key => $doctor) {
            	$doctor->address_data;
                $user_table = User::find($doctor->doctor_data->id);
                $doctor->doctor_data->filters = $user_table->getFilters($user_table->id);
                $doctor->doctor_data->selected_filter_options = $user_table->getSelectedFiltersByCategory($user_table->id);
                $doctor->unit_price = $unit_price->value * 60; 
                $user_table->profile; 
                $doctor->doctor_data->categoryData = $user_table->getCategoryData($doctor->doctor_data->id);
                $doctor->doctor_data->additionals = $user_table->getAdditionals($doctor->doctor_data->id);
                $doctor->doctor_data->insurances = $user_table->getInsurnceData($doctor->doctor_data->id);
                $doctor->doctor_data->subscriptions = $user_table->getSubscription($user_table);
                $doctor->doctor_data->custom_fields = $user_table->getCustomFields($user_table->id);
                $doctor->doctor_data->patientCount = User::getTotalRequestDone($doctor->doctor_data->id);
                $doctor->doctor_data->reviewCount = Feedback::reviewCountByConsulatant($user_table->id);
                $doctor->doctor_data->account_verified = ($user_table->account_verified)?true:false;
                $doctor->doctor_data->totalRating = 0;
                if(isset($doctor->category_service_type) && isset($doctor->category_service_type->service)){
                    $doctor->service_type = $doctor->category_service_type->service->type;
                    $doctor->main_service_type = $doctor->category_service_type->service->service_type;
                    unset($doctor->category_service_type);
                }
                if($user_table->profile){
                    $doctor->doctor_data->profile->bio = $user_table->profile->about;
                    $doctor->doctor_data->totalRating = $user_table->profile->rating;
                    $doctor->doctor_data->profile->location = ["name"=>$user_table->profile->location_name,"lat"=>$user_table->profile->lat,"long"=>$user_table->profile->long];
                }
                $doctor->doctor_data = Helper::getMoreData($doctor->doctor_data);
            }
            $after = null;
            $before = null;
            $next_page = null;
            $pre_page = null;
            if(!$cursor_Paginate){
                if($doctors->hasMorePages()){
                    $next_page = $doctors->currentPage() + 1;
                }
                $pre_page = $doctors->currentPage() - 1;
                $per_page = $doctors->perPage();
                return response([
                    'status' => "success",
                    'statuscode' => 200,
                    'message' => __('Doctor List '),
                    'data' =>[
                        'doctors'=>$doctors->items(),
                        'after'=>$after,
                        'before'=>$before,
                        'per_page'=>$per_page,
                        'next_page'=>$next_page,
                        'pre_page'=>$pre_page
                    ]], 200);
            }else{
                if($doctors->meta['next']){
                    $after = $doctors->meta['next']->target;
                }
                if($doctors->meta['previous']){
                    $before = $doctors->meta['previous']->target;
                }
                $per_page = $doctors->perPage();

                $next_page_url = null;

                if($doctors->hasMorePages()){
                    $next_page = $doctors->currentPage()->target + 1;
                }

                $prev_page_url = null;

                if($doctors->meta['previous']){
                    $pre_page = $request->get('page') - 1;
                }

                return response(['status' => "success", 'statuscode' => 200,
                                    'message' => __('Doctor List '), 'data' =>[
                                        'doctors'=>$doctors->items(),
                                        'after'=>$after,
                                        'before'=>$before,
                                        'per_page'=>$per_page,
                                        'next_page'=>$next_page,
                                        'pre_page'=>$pre_page,
                                    ]], 200);
            }
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage().' '.$ex->getLine()], 500);
        }
    } 
}
