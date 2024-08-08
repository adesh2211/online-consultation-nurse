<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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
use App\Model\CustomInfo;
use App\Model\ServiceProviderFilterOption;
use App\Model\UserPackage,App\Model\RequestDetail;
use App\Model\Profile;
use App\Model\Transaction;
use App\Model\RequestDate;
use App\Model\Request as RequestData;
use App\Model\Payment;
use App\Model\Card,App\Model\Coupon,App\Model\CouponUsed;
use App\Model\SocialAccount;
use Socialite,Exception;
use App\Model\Image as ModelImage;
use Intervention\Image\ImageManager;
use Carbon\Carbon;
use App\Helpers\Helper,Config;
use App\Jobs\RequestReminder;
use App\Jobs\EmergencyRequestProcess;
use App\Jobs\RequestSmsEmail;
class CustomerController extends Controller{
	 public $successStatus = 200;
	 /**
     * @SWG\Get(
     *     path="/wallet",
     *     description="Wallet Balance",
     * tags={"Customer"},
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

    public static function getWalletBalance(Request $request) {
    	try{
	    	$user = Auth::user();
	    	$balance = 0;
	    	$wallet = Wallet::where('user_id',$user->id)->first();
	    	if($wallet){
	    		$balance = $wallet->balance;
	    	}
	    	return response(['status' => "success", 'statuscode' => 200,
	                            'message' => __('Wallet Balance'), 'data' =>['balance'=>$balance]], 200);
    	}catch(Exception $ex){
    		return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage()], 500);
    	}
    }
    /**
     * @SWG\Get(
     *     path="/wallet-history",
     *     description="Wallet History",
     * tags={"Customer"},
     *     security={
     *     {"Bearer": {}},
     *   },
     *  @SWG\Parameter(
     *         name="transaction_type",
     *         in="query",
     *         type="string",
     *         description="transaction_type e.g deposit,withdrawal,all default is all",
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
    public static function getWalletHistory(Request $request) {
    	try{
	    	$user = Auth::user();
	    	$payments = [];
            $transaction_type=null;
            if(isset($request->transaction_type) && $request->transaction_type!=='all'){
                $transaction_type = $request->transaction_type;
            }
            $per_page = (isset($request->per_page)?$request->per_page:10);
	    	$payments = Payment::where('to',$user->id)->
            whereHas('transaction', function ($query) use($transaction_type) {
                            if($transaction_type){
                                $query->where('transaction_type', $transaction_type);
                            }
                            $query->where('status','!=','pending');
                        })->orderBy('id', 'desc')->cursorPaginate($per_page);
	    	foreach ($payments as $key => $payment) {
	    		$payment->from = User::select('name','email','id','profile_image')->with('profile')->where('id',$payment->from)->first();
	    		$payment->to = User::select('name','email','id','profile_image')->with('profile')->where('id',$payment->to)->first();
	    		$transaction_type = \App\Model\Transaction::select('amount','transaction_type','status','closing_balance','request_id','payout_message')->where('id',$payment->transaction_id)->first();
                $payment->call_duration = null;
                $payment->service_type = null;
                if($transaction_type->requesthistory){
                    $payment->call_duration = $transaction_type->requesthistory->duration;
                    $payment->service_type = $transaction_type->requesthistory->request->servicetype->type;
                }
                $payment->amount = $transaction_type->amount;
                $payment->payout_message = $transaction_type->payout_message;
	    		$payment->type = $transaction_type->transaction_type;
                $payment->status = $transaction_type->status;
                $payment->closing_balance = $transaction_type->closing_balance;
	    	}
            $after = null;
            if($payments->meta['next']){
                $after = $payments->meta['next']->target;
            }
            $before = null;
            if($payments->meta['previous']){
                $before = $payments->meta['previous']->target;
            }
            $per_page = $payments->perPage();
	    	return response(['status' => "success", 'statuscode' => 200,
	                            'message' => __('Payment History'), 'data' =>['payments'=>$payments->items(),'after'=>$after,'before'=>$before,'per_page'=>$per_page]], 200);
    	}catch(Exception $ex){
    		return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage()], 500);
    	}
    }

    /**
     * @SWG\Get(
     *     path="/cards",
     *     description="Customer Card Listing",
     * tags={"Customer"},
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
    public static function getPaymentCardListing(Request $request) {
        try{
            $user = Auth::user();
            $cards = [];
            $cards = $user->getAttachedCards($user);
            return response(['status' => "success", 'statuscode' => 200,
                                'message' => __('Cards Listing'), 'data' =>['cards'=>$cards]], 200);
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage()], 500);
        }
    }

    /**
     * @SWG\Post(
     *     path="/auto-allocate",
     *     description="postAutoAllocateRequest",
     * tags={"Customer"},
     *     security={
     *     {"Bearer": {}},
     *   },
     *  @SWG\Parameter(
     *         name="category_id",
     *         in="query",
     *         type="number",
     *         description=" Category  Id",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="filter_id",
     *         in="query",
     *         type="number",
     *         description=" Filter  Id",
     *         required=false,
     *     ),
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
     *         description="date e.g 22:10",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="schedule_type",
     *         in="query",
     *         type="string",
     *         description="schedule type  schedule",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="request_id",
     *         in="query",
     *         type="string",
     *         description="request_id ",
     *         required=false,
     *     ),     
     *  @SWG\Parameter(
     *         name="package_id",
     *         in="query",
     *         type="string",
     *         description="package_id ",
     *         required=true,
     *     ),     
     *  @SWG\Parameter(
     *         name="transaction_id",
     *         in="query",
     *         type="string",
     *         description="transaction_id for retry",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="lat",
     *         in="query",
     *         type="string",
     *         description="latitude ",
     *         required=true,
     *     ),     
     *  @SWG\Parameter(
     *         name="long",
     *         in="query",
     *         type="string",
     *         description="longitude ",
     *         required=true,
     *     ),
          *  @SWG\Parameter(
     *         name="first_name",
     *         in="query",
     *         type="string",
     *         description="first_name ",
     *         required=false,
     *     ),     
     *  @SWG\Parameter(
     *         name="last_name",
     *         in="query",
     *         type="string",
     *         description="last_name ",
     *         required=false,
     *     ),     
     *  @SWG\Parameter(
     *         name="service_for",
     *         in="query",
     *         type="string",
     *         description="service_for ",
     *         required=false,
     *     ),     
     *  @SWG\Parameter(
     *         name="home_care_req",
     *         in="query",
     *         type="string",
     *         description="home_care_req ",
     *         required=false,
     *     ),     
     *  @SWG\Parameter(
     *         name="reason_for_service",
     *         in="query",
     *         type="string",
     *         description="longitude ",
     *         required=false,
     *     ),     
     *  @SWG\Parameter(
     *         name="service_address",
     *         in="query",
     *         type="string",
     *         description="service_address ",
     *         required=false,
     *     ),   
     *  @SWG\Parameter(
     *         name="end_date",
     *         in="query",
     *         type="string",
     *         description="end_date ",
     *         required=false,
     *     ),     
     *  @SWG\Parameter(
     *         name="end_time",
     *         in="query",
     *         type="string",
     *         description="end_time ",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="phone_number",
     *         in="query",
     *         type="string",
     *         description="phone_number ",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="country_code",
     *         in="query",
     *         type="string",
     *         description="country_code",
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
    public function postAutoAllocateRequest(Request $request) {
        try{
            $domain_name = 'intely';
            $user = Auth::user();
            // if(!$user->hasrole('customer')){
            //     return response(array('status' => "error", 'statuscode' => 400, 'message' =>'Invalid Valid user role must be role as customer'), 400);
            // }
            $rules = ['schedule_type'=>["required" , "max:255", "in:schedule"]];
            $rules['category_id'] = 'required|exists:categories,id';
            if(isset($request->schedule_type) && strtolower($request->schedule_type)=='schedule'){
                $rules['date'] = 'required|date|date_format:Y-m-d';
                $rules['time'] = 'required|date_format:H:i';
            }
            if(isset($request->filter_id)){
                $rules['filter_id'] = 'required|exists:filter_type_options,id';
            } 
            if(isset($request->package_id)){
                $rules['package_id'] = 'required|exists:packages,id';
            }
            $rules['lat'] = 'required';
            $rules['long'] = 'required';
            if(Config::get('client_connected')&&Config::get('client_data')->domain_name==$domain_name){
                $rules['first_name'] = 'required|string';
                $rules['first_name'] = 'required|string';
                $rules['last_name'] = 'required|string';
                $rules['service_for'] = 'required|string';
                $rules['home_care_req'] = 'required|string';
                $rules['service_address'] = 'required|string';
                $rules['reason_for_service'] = 'required|string';
                if(isset($request->schedule_type) && strtolower($request->schedule_type)=='schedule'){
                    $rules['end_date'] = 'required|date|date_format:Y-m-d';
                    $rules['end_time'] = 'required|date_format:H:i';
                }
            }
            $validator = Validator::make($request->all(),$rules);
            if ($validator->fails()) {
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                    $validator->getMessageBag()->first()), 400);
            }
            $timezone = $request->header('timezone');
            if(!$timezone){
                $timezone = 'Asia/Kolkata';
            }
            $input = $request->all();
            $request_data = null;
            $message = "No Consultant Found";
            $categoryservicetype = CategoryServiceType::where([
                'category_id'=>$input['category_id']
                ])->orderBy('id','ASC')->first();
            $service_providers = null;
            if($categoryservicetype){
                $input['service_id'] = $categoryservicetype->service_id;
                $service_providers = \App\Model\SpServiceType::where(
                    'category_service_id',$categoryservicetype->id
                )->pluck('sp_id')->toArray();

                if(isset($request->filter_id)){
                    $service_providers = \App\Model\ServiceProviderFilterOption::where(
                        'filter_option_id',$request->filter_id
                    )->whereIn('sp_id',$service_providers)->pluck('sp_id')->toArray();
                }
                // if(Config::get('client_connected')&&Config::get('client_data')->domain_name==$domain_name){
                    $sqlDistance = DB::raw("( 111.045 * acos( cos( radians(" . $input['lat'] . ") )* cos( radians(  profiles.lat ) ) * cos( radians( profiles.long ) - radians(" . $input['long']  . ") ) + sin( radians(" . $input['lat']  . ") ) * sin( radians(  profiles.lat ) ) ) )");
                    $service_providers =  DB::table('profiles')
                    ->select('*')
                    ->selectRaw("{$sqlDistance} AS distance")
                    ->havingRaw('distance BETWEEN ? AND ?', [0,isset($request->radius)?$request->radius/100:200/100])
                    ->orderBy('distance',"DESC")
                    ->whereIn('user_id',$service_providers)->pluck('user_id')->toArray();
                // }
            }
            if(count($service_providers)<1){
                return response(array(
                    'status' => "error",
                    'statuscode' => 200,
                    'data'=>['amountNotSufficient'=>false,'doctor_data'=>(object)[]],
                    'message' =>__($message)),
                    200);
            }
            $slot_duration = \App\Model\EnableService::where('type','slot_duration')->first();
            $slot_minutes = $slot_duration->value;
            if(Config::get('client_connected')&&Config::get('client_data')->domain_name==$domain_name){
                $start_date = Carbon::parse($request->date.' '.$request->time,$timezone)->setTimezone('UTC')->format('Y-m-d H:i:s');
                $end_date = Carbon::parse($request->end_date.' '.$request->end_time,$timezone)->setTimezone('UTC')->format('Y-m-d H:i:s');
                $str1 =  strtotime($start_date);
                $str2 =  strtotime($end_date);
                $slot_minutes  = ($str2 - $str1)/60;
            }
            $unit_price = \App\Model\EnableService::where('type','unit_price')->first();
            $add_slot_second = $slot_duration->value * 60;
            $discount = 0;
            $coupon_validation = [];
            $coupon = false;
            $wallet_type = 'user_wallet';
            $user_wallet = $user;
            if($request->schedule_type=='schedule'){
		        $datenow = Carbon::parse($request->date.' '.$request->time,$timezone)->setTimezone('UTC')->format('Y-m-d H:i:s');
                $datenow2 = Carbon::parse($request->date.' '.$request->time,$timezone)->addSeconds(60)->setTimezone('UTC')->format('Y-m-d H:i:s');

                if(Config::get('client_connected')&&Config::get('client_data')->domain_name==$domain_name){
                    $end_time_slot_utcdate = Carbon::parse($request->end_date.' '.$request->end_time,$timezone)->setTimezone('UTC')->format('Y-m-d H:i:s');
                }else{
                    $end_time_slot_utcdate = Carbon::parse($datenow)->addSeconds($add_slot_second)->setTimezone('UTC')->format('Y-m-d H:i:s');
                }
                $user_time_zone_slot = Carbon::parse($datenow)->setTimezone($timezone)->format('h:i a');
                $user_time_zone_date = Carbon::parse($datenow)->setTimezone($timezone)->format('Y-m-d');
                // print_r($datenow);
                $exist = \App\Model\Request::where('from_user',$user->id)
                ->where('booking_date','=',$datenow)
                ->orWhereBetween('booking_end_date',[$datenow2,$end_time_slot_utcdate])
                ->whereHas('requesthistory', function ($query) {
                    $query->where('status','!=','canceled');
                })
                ->get();
                if($exist->count()>0){
                    return response(array(
                        'status' => "error",
                        'statuscode' => 200,
                        'data'=>['amountNotSufficient'=>false,'doctor_data'=>(object)[]],
                        'message' =>__("Your other requests already appointed at this time.")),
                        200);
                }

                $exist = \App\Model\Request::whereIn('to_user',$service_providers)
                ->where('booking_date','=',$datenow)
                ->orWhereBetween('booking_end_date',[$datenow2,$end_time_slot_utcdate])
                ->whereHas('requesthistory', function ($query) {
                    $query->where('status','!=','canceled');
                })
                ->where(function($query2) use ($request_data){
                    if(isset($request_data->id))
                        $query2->where('id','!=',$request_data->id);
                })
                ->get();
                if($exist->count()>0){
                    return response(array(
                        'status' => "error",
                        'statuscode' => 200,
                        'data'=>['amountNotSufficient'=>false,'doctor_data'=>(object)[]],
                        'message' =>__($message)),
                        200);
                }
            }else{                
                $data = [];
                while ($data==false) {
                    $data = $this->checkSlotFullOrNotMulti($slot_duration,$timezone,$add_slot_second,$service_providers,$request_data);
                    $slot_duration->value = $slot_duration->value + 30;
                }
                $user_time_zone_date = $data['user_time_zone_date'];
                $user_time_zone_slot = $data['user_time_zone_slot'];
                $datenow = $data['datenow'];
            }


            $message = 'Something went wrong';
            $input['consultant_id']  = $service_providers[array_rand($service_providers,1)];
            $spservicetype_id = null;
            if($categoryservicetype){
                $spservicetype_id = \App\Model\SpServiceType::where(
                    ['category_service_id'=>$categoryservicetype->id,
                    'sp_id'=>$input['consultant_id']]
                )->first();
            }
            $per_minute = $spservicetype_id->price/$unit_price->value;
            $total_charges = $slot_minutes * $per_minute;
            $grand_total= $g_total = $slot_minutes * $per_minute;
            if(isset($input['package_id'])){
                $subscribe = Helper::isSusbScribe([
                    'user_id'=>$user->id,
                    'package_id'=>$input['package_id']
                ]);
                if(!$subscribe){
                    return response([
                        'status' => "success",
                        'statuscode' => 400,
                        'message' => __('Retry'),
                        'data'=>['retry'=>true]
                    ], 400);
                }else{
                    $grand_total = $g_total = 0;
                    $total_charges = 0;
                    $discount = 0;
                }
            }
            $sr_request = new \App\Model\Request();
            $sr_request->from_user = $user->id;
            $sr_request->booking_date = $datenow;
            $sr_request->to_user = $input['consultant_id'];
            $sr_request->service_id = $input['service_id'];
            $sr_request->sp_service_type_id = ($spservicetype_id)?$spservicetype_id->id:null;
            if($sr_request->save()){
                if(Config::get('client_connected')&&Config::get('client_data')->domain_name==$domain_name){
                    $sr_request->payment = 'pending';
                    $sr_request->booking_end_date = Carbon::parse($request->end_date.' '.$request->end_time,$timezone)->setTimezone('UTC')->format('Y-m-d H:i:s');
                    $sr_request->save();

                }else{
                    $sr_request->booking_end_date = $end_time_slot_utcdate;
                    $sr_request->save();
                }
                $this->insertRequestDetail($sr_request->id,$input);
                $requesthistory = new \App\Model\RequestHistory();
                $requesthistory->duration = 0;
                $requesthistory->total_charges = $grand_total;
                $requesthistory->schedule_type = $request->schedule_type;
                $requesthistory->status = 'pending';
                $requesthistory->request_id = $sr_request->id;
                if(isset($coupon_validation['status']) && $coupon_validation['status']=='success'){
                    $requesthistory->coupon_id = $coupon_validation['coupon_id'];
                    $couponused = new CouponUsed();
                    $couponused->user_id =  $user->id;
                    $couponused->coupon_id =  $coupon_validation['coupon_id'];
                    $couponused->save();
                }
                if($requesthistory->save()){
                    $used_packages = false;
                    if(isset($input['package_id'])){
                        $used_packages = true;
                        $subscribe = Helper::isSusbScribe([
                            'user_id'=>$user->id,
                            'package_id'=>$input['package_id']
                        ]);
                        if(!$subscribe){
                            $subscribepackage = Helper::subscribePackage([
                                'user_id'=>$user->id,
                                'package_id'=>$input['package_id']]);
                            if($subscribepackage){
                                $grand_total = 0;
                                $total_charges = 0;
                                $discount = 0;
                            }else{
                                $used_packages = false;
                            }
                        }else{
                            $grand_total = 0;
                            $total_charges = 0;
                            $discount = 0;
                        }
                    }
                    if(!$used_packages){
                            $withdrawal_to = array(
                                'balance'=>$grand_total,
                                'user'=>$sr_request->cus_info,
                                'from_id'=>$sr_request->sr_info->id,
                                'request_id'=>$sr_request->id,
                                'status'=>'user-pending'
                            );
                            Transaction::createWithdrawal($withdrawal_to);
                            $deposit_to = array(
                                'balance'=>$grand_total,
                                'user'=>$sr_request->sr_info,
                                'from_id'=>$sr_request->cus_info->id,
                                'request_id'=>$sr_request->id,
                                'status'=>'vendor-pending'
                            );
                            Transaction::createDeposit($deposit_to);
                    }else{
                        $requesthistory->module_table = 'packages';
                        $requesthistory->module_id = $input['package_id'];
                        $requesthistory->save();

                        $userpackage  = UserPackage::where([
                            'user_id'=>$user->id,
                            'package_id'=>$input['package_id'],
                        ])->first();
                        $userpackage->decrement('available_requests',1);
                    }
                }
                $doctor_data = User::getDoctorDetail($sr_request->to_user);
                $notification = new Notification();
                $notification->sender_id = $user->id;
                $notification->receiver_id = $input['consultant_id'];
                $notification->module_id = $sr_request->id;
                $notification->module ='request';
                $notification->notification_type ='NEW_REQUEST';
                $notification->message =__('notification.new_req_text', ['user_name' => $user->name]);
                $notification->save();
                $notification->push_notification(array($input['consultant_id']),array('pushType'=>'NEW_REQUEST','request_id'=>$sr_request->id,'message'=>__('notification.new_req_text', ['user_name' => $user->name])));
                return response(['status' => "success", 'statuscode' => 200,'message' => __('New Request Created '),'data'=>['amountNotSufficient'=>false,'doctor_data'=>$doctor_data]], 200);
            }
            
            return response(['status' => "success", 'statuscode' => 200,'message' => __('Booking confirmed'), 'data'=>['amountNotSufficient'=>false,'total'=>$total_charges,'discount'=>0,'grand_total'=>$total_charges,'book_slot_time'=>$user_time_zone_slot,'book_slot_date'=>$user_time_zone_date,'coupon'=>$coupon]], 200);
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage().' '.$ex->getLine()], 500);
        }
    }


    public function insertRequestDetail($request_id,$input){
        $user = Auth::user();
        $requestdetail= RequestDetail::firstOrCreate(['request_id'=>$request_id]);
        if($requestdetail){
            $requestdetail->first_name =  isset($input['first_name'])?$input['first_name']:null;
            $requestdetail->last_name =  isset($input['last_name'])?$input['last_name']:null;
            $requestdetail->service_for =  isset($input['service_for'])?$input['service_for']:null;
            $requestdetail->home_care_req =  isset($input['home_care_req'])?$input['home_care_req']:null;
            $requestdetail->service_address =  isset($input['service_address'])?$input['service_address']:null;
            $requestdetail->lat =  isset($input['lat'])?$input['lat']:null;
            $requestdetail->long =  isset($input['long'])?$input['long']:null;
            $requestdetail->reason_for_service =  isset($input['reason_for_service'])?$input['reason_for_service']:null;
            $requestdetail->country_code =  isset($input['country_code'])?$input['country_code']:null;
            $requestdetail->phone_number =  isset($input['phone_number'])?$input['phone_number']:null;
        }
        if(isset($input['duties'])){
            $duties_raw = [
                "duties"=>explode(",",$input['duties'])
            ];
            $custom_info = new CustomInfo();
            $custom_info->raw_detail = json_encode($duties_raw);
            $custom_info->info_type = 'duties';
            $custom_info->ref_table = 'requests';
            $custom_info->ref_table_id = $request_id;
            $custom_info->status = 'success';
            $custom_info->save();
        }
        if(isset($input['tier_id']) && isset($input['tier_options'])){
            if(!is_array($input['tier_options'])){
                $input['tier_options'] =  json_decode($input['tier_options'],true);
            }
            if(is_array($input['tier_options'])){
                foreach ($input['tier_options'] as $key => $t_op) {
                    $option =  new \App\Model\RequestTierPlan();
                    $option->request_id = $request_id;
                    $option->tier_id = $input['tier_id'];
                    $option->tier_option_id = $t_op['id'];
                    $option->updated_by = $user->id;
                    $option->type = $t_op['type'];
                    $option->save();
                }
            }
        }
        $requestdetail->save();
    }

    /**
     * @SWG\Post(
     *     path="/care-plans",
     *     description="Post Care Plan",
     * tags={"Customer"},
     *     security={
     *     {"Bearer": {}},
     *   },
     *  @SWG\Parameter(
     *         name="request_id",
     *         in="query",
     *         type="number",
     *         description=" Booking Id",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="care_plans",
     *         in="query",
     *         type="number",
     *         description=" Care Plan array [{'title':'abc'},{'title':'abc2'}]",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="question_answers",
     *         in="query",
     *         type="number",
     *         description=" question_answers array [{'question':'abc','answer':'answer'}]",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="type",
     *         in="query",
     *         type="number",
     *         description="type=question_answers",
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
    public function postCarePlan(Request $request) {
        try{
            $input = $request->all();
            $user = Auth::user();
            $rules = [
                'request_id'=>'required|exists:requests,id',
            ];
            if(isset($input['type']) && $input['type']=='question_answers'){
                $rules['question_answers'] = 'required';
            }else{
                $rules['care_plans'] = 'required';
            }
            $validator = Validator::make($request->all(),$rules);
            if ($validator->fails()) {
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                    $validator->getMessageBag()->first()), 400);
            }
            $user = Auth::user();
            // if(!is_array($input['care_plans'])){
            //         $input['care_plans'] = json_decode($input['care_plans']);
            // }
            if(isset($input['care_plans']) && is_array($input['care_plans'])){
                foreach ($input['care_plans'] as $key => $care) {
                    $care_plan = new \App\Model\RequestCarePlan();
                    $care_plan->request_id = $input['request_id'];
                    $care_plan->created_by = $user->id;
                    $care_plan->title = $care['title'];
                    $care_plan->save();
                }
            }
            if(isset($input['question_answers']) && is_array($input['question_answers'])){
                foreach ($input['question_answers'] as $key => $question) {
                    $question1 = new \App\Model\RequestQuestionAnswer();
                    $question1->request_id = $input['request_id'];
                    $question1->created_by = $user->id;
                    $question1->question = $question['question'];
                    $question1->answer = $question['answer'];
                    $question1->save();
                }
            }
            return response([
                'status' => 'success', 
                'statuscode' => 200,
                'message' => __('Added')], 200);

        }catch(Exception $ex){
            return response([
                'status' => 'error', 
                'statuscode' => 500, 
                'message' => $ex->getMessage().' '.$ex->getLine()], 500
            );
        }
    }


    /**
     * @SWG\Post(
     *     path="/update-care-plans",
     *     description="Update Care Plan",
     * tags={"Customer"},
     *     security={
     *     {"Bearer": {}},
     *   },
     *  @SWG\Parameter(
     *         name="id",
     *         in="query",
     *         type="number",
     *         description="Id of Tier Options",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="request_id",
     *         in="query",
     *         type="number",
     *         description="Id of Booking Table",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="status",
     *         in="query",
     *         type="string",
     *         description="status completed",
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
    public function postUpdateCarePlan(Request $request) {
        try{
            $user = Auth::user();
            $rules = [
                'id'=>'required|exists:tier_options,id',
                'request_id'=>'required|exists:requests,id',
                'status'=>'required',
            ];
            $validator = Validator::make($request->all(),$rules);
            if ($validator->fails()) {
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                    $validator->getMessageBag()->first()), 400);
            }
            $input = $request->all();
            $user = Auth::user();
            $care_plan = \App\Model\RequestTierPlan::where(['tier_option_id'=>$input['id'],'request_id'=>$input['request_id']])->first();
            $care_plan->status = isset($input['status'])?$input['status']:$care_plan->status;
            $care_plan->updated_by = $user->id;
            $care_plan->save();
            $rq = RequestData::where('id',$care_plan->request_id)->first();
            $to_user = $rq->to_user;
            if(!$user->hasrole('customer')){
                $to_user = $rq->from_user;
            }
            if(isset($input['status']) && strtolower($input['status'])=='completed'){
                $notification = new Notification();
                $notification->push_notification(array($to_user),array(
                    'pushType'=>'CARE_PLAN_DONE',
                    'request_id'=>$rq->id,
                    'id'=>$care_plan->tier_id,
                    'message'=>__("Care Plan Completed")
                ));
            }
            return response([
                'status' => 'success', 
                'statuscode' => 200,
                'message' => __('Updated')], 200);

        }catch(Exception $ex){
            return response([
                'status' => 'error', 
                'statuscode' => 500, 
                'message' => $ex->getMessage().' '.$ex->getLine()], 500
            );
        }
    }



    /**
     * @SWG\Post(
     *     path="/confirm-auto-allocate",
     *     description="Confirm Auto Allocation",
     * tags={"Customer"},
     *     security={
     *     {"Bearer": {}},
     *   },
     *  @SWG\Parameter(
     *         name="category_id",
     *         in="query",
     *         type="number",
     *         description=" Category  Id",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="filter_id",
     *         in="query",
     *         type="number",
     *         description=" Filter  Id",
     *         required=false,
     *     ),
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
     *         description="date e.g 22:10",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="schedule_type",
     *         in="query",
     *         type="string",
     *         description="schedule type  schedule",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="package_id",
     *         in="query",
     *         type="string",
     *         description="package_id ",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="lat",
     *         in="query",
     *         type="string",
     *         description="latitude ",
     *         required=false,
     *     ),     
     *  @SWG\Parameter(
     *         name="long",
     *         in="query",
     *         type="string",
     *         description="longitude ",
     *         required=false,
     *     ),     
     *  @SWG\Parameter(
     *         name="first_name",
     *         in="query",
     *         type="string",
     *         description="first_name ",
     *         required=false,
     *     ),     
     *  @SWG\Parameter(
     *         name="last_name",
     *         in="query",
     *         type="string",
     *         description="last_name ",
     *         required=false,
     *     ),     
     *  @SWG\Parameter(
     *         name="service_for",
     *         in="query",
     *         type="string",
     *         description="service_for ",
     *         required=false,
     *     ),     
     *  @SWG\Parameter(
     *         name="home_care_req",
     *         in="query",
     *         type="string",
     *         description="home_care_req ",
     *         required=false,
     *     ),     
     *  @SWG\Parameter(
     *         name="reason_for_service",
     *         in="query",
     *         type="string",
     *         description="longitude ",
     *         required=false,
     *     ),     
     *  @SWG\Parameter(
     *         name="service_address",
     *         in="query",
     *         type="string",
     *         description="service_address ",
     *         required=false,
     *     ),   
     *  @SWG\Parameter(
     *         name="end_date",
     *         in="query",
     *         type="string",
     *         description="end_date ",
     *         required=false,
     *     ),     
     *  @SWG\Parameter(
     *         name="end_time",
     *         in="query",
     *         type="string",
     *         description="end_time ",
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
    public function postConfirmAutoAllocateRequest(Request $request) {
        try{
            $domain_name = 'intely';
            $user = Auth::user();
            $rules = ['schedule_type'=>["required" , "max:255", "in:schedule"]];
            $rules['category_id'] = 'required|exists:categories,id';
            if(isset($request->schedule_type) && strtolower($request->schedule_type)=='schedule'){
                $rules['date'] = 'required|date|date_format:Y-m-d';
                $rules['time'] = 'required|date_format:H:i';
            }
            if(isset($request->filter_id)){
                $rules['filter_id'] = 'required|exists:filter_type_options,id';
            } 
            if(isset($request->package_id)){
                $rules['package_id'] = 'required|exists:packages,id';
            }
            if(Config::get('client_connected')&&Config::get('client_data')->domain_name==$domain_name){
                $rules['first_name'] = 'required|string';
                $rules['last_name'] = 'required|string';
                $rules['service_for'] = 'required|string';
                $rules['home_care_req'] = 'required|string';
                $rules['service_address'] = 'required|string';
                $rules['lat'] = 'required';
                $rules['long'] = 'required';
                $rules['reason_for_service'] = 'required|string';
                if(isset($request->schedule_type) && strtolower($request->schedule_type)=='schedule'){
                    $rules['end_date'] = 'required|date|date_format:Y-m-d';
                    $rules['end_time'] = 'required|date_format:H:i';
                }
            }
            $validator = Validator::make($request->all(),$rules);
            if ($validator->fails()) {
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                    $validator->getMessageBag()->first()), 400);
            }
            $timezone = $request->header('timezone');
            if(!$timezone){
                $timezone = 'Asia/Kolkata';
            }
            $input = $request->all();
            $request_data = null;
            $message = "No Consultant Found";
            $categoryservicetype = CategoryServiceType::where([
                'category_id'=>$input['category_id']
                ])->orderBy('id','ASC')->first();
            $service_providers = null;
            if($categoryservicetype){
                $input['service_id'] = $categoryservicetype->id;
                $service_providers = \App\Model\SpServiceType::where(
                    'category_service_id',$categoryservicetype->id
                )->pluck('sp_id')->toArray();

                if(isset($request->filter_id)){
                    $service_providers = \App\Model\ServiceProviderFilterOption::where(
                        'filter_option_id',$request->filter_id
                    )->whereIn('sp_id',$service_providers)->pluck('sp_id')->toArray();
                }
                // $profiles = Profile::selectRaw("id, user_id, lat, long, rating, zone ,
                //      ( 6371000 * acos( cos( radians(?) ) *
                //        cos( radians( latitude ) )
                //        * cos( radians( longitude ) - radians(?)
                //        ) + sin( radians(?) ) *
                //        sin( radians( latitude ) ) )
                //      ) AS distance", [$request->lat, $request->long,$request->lat])->whereIn('user_id', $service_providers)
                // ->having("distance", "<", 400)
                // ->orderBy("distance",'asc')
                // ->get();
                // print_r($profiles);die;

            }
            if(count($service_providers)<1){
                return response(array(
                    'status' => "error",
                    'statuscode' => 400,
                    'message' =>__($message)),
                    400);
            }
            $slot_duration = \App\Model\EnableService::where('type','slot_duration')->first();
            $slot_minutes = $slot_duration->value;
            if(Config::get('client_connected')&&Config::get('client_data')->domain_name==$domain_name){
                $start_date = Carbon::parse($request->date.' '.$request->time,$timezone)->setTimezone('UTC')->format('Y-m-d H:i:s');
                $end_date = Carbon::parse($request->end_date.' '.$request->end_time,$timezone)->setTimezone('UTC')->format('Y-m-d H:i:s');
               $str1 =  strtotime($start_date);
               $str2 =  strtotime($end_date);
               $slot_minutes  = ($str2 - $str1)/60;
            }
            $unit_price = \App\Model\EnableService::where('type','unit_price')->first();
            $add_slot_second = $slot_duration->value * 60;
            $discount = 0;
            $coupon_validation = [];
            $coupon = false;
            $wallet_type = 'user_wallet';
            $user_wallet = $user;
            if($request->schedule_type=='schedule'){
                $datenow = Carbon::parse($request->date.' '.$request->time,$timezone)->setTimezone('UTC')->format('Y-m-d H:i:s');
                $datenow2 = Carbon::parse($request->date.' '.$request->time,$timezone)->addSeconds(60)->setTimezone('UTC')->format('Y-m-d H:i:s');
                $end_time_slot_utcdate2 = null;
                if(Config::get('client_connected')&&Config::get('client_data')->domain_name==$domain_name){
                    $end_time_slot_utcdate = Carbon::parse($request->end_date.' '.$request->end_time,$timezone)->setTimezone('UTC')->format('Y-m-d H:i:s');
                    $end_time_slot_utcdate2 = Carbon::parse($request->end_date.' '.$request->end_time,$timezone)->addSeconds(60)->setTimezone('UTC')->format('Y-m-d H:i:s');
                }else{
                    $end_time_slot_utcdate = Carbon::parse($datenow)->addSeconds($add_slot_second)->setTimezone('UTC')->format('Y-m-d H:i:s');
                }
                $user_time_zone_slot = Carbon::parse($datenow)->setTimezone($timezone)->format('h:i a');
                $user_time_zone_date = Carbon::parse($datenow)->setTimezone($timezone)->format('Y-m-d');
                // print_r($datenow);
                $exist = \App\Model\Request::whereIn('to_user',$service_providers)
                ->whereBetween('booking_date', [$datenow2, $end_time_slot_utcdate])
                ->whereHas('requesthistory', function ($query) {
                    $query->where('status','!=','canceled');
                })
                ->where(function($query2) use ($request_data){
                    if(isset($request_data->id))
                        $query2->where('id','!=',$request_data->id);
                })
                ->where(function($query2) use ($end_time_slot_utcdate2,$end_time_slot_utcdate){
                    if(Config::get('client_connected')&&Config::get('client_data')->domain_name==$domain_name){
                        $query2->whereBetween('booking_end_date', [$end_time_slot_utcdate2, $end_time_slot_utcdate]);
                    }
                })
                ->get();
                if($exist->count()>0){
                    return response(array(
                        'status' =>"error",
                        'statuscode' => 400,
                        'message' =>__($message)), 
                    400);
                }
            }else{                
                $data = [];
                while ($data==false) {
                    $data = $this->checkSlotFullOrNotMulti($slot_duration,$timezone,$add_slot_second,$service_providers,$request_data);
                    $slot_duration->value = $slot_duration->value + 30;
                }
                $user_time_zone_date = $data['user_time_zone_date'];
                $user_time_zone_slot = $data['user_time_zone_slot'];
                $datenow = $data['datenow'];
            }

            $message = 'Something went wrong';
            if($request_data){
                return response(['status' => "success", 'statuscode' => 200,'message' => __('Request Re-Schedule'),
                    'data'=>['amountNotSufficient'=>false]], 200);
            }else{
                $input['consultant_id']  = $service_providers[array_rand($service_providers,1)];
                $spservicetype_id = null;
                if($categoryservicetype){
                    $spservicetype_id = \App\Model\SpServiceType::where(
                        ['category_service_id'=>$categoryservicetype->id,
                        'sp_id'=>$input['consultant_id']]
                    )->first();
                }
                $per_minute = $spservicetype_id->price/$unit_price->value;
                $total_charges = $slot_minutes * $per_minute;
                $grand_total= $g_total = $slot_minutes * $per_minute;
                if(isset($input['package_id'])){
                    $subscribe = Helper::isSusbScribe([
                        'user_id'=>$user->id,
                        'package_id'=>$input['package_id']
                    ]);
                    if(!$subscribe){
                        return response([
                            'status' => "success",
                            'statuscode' => 400,
                            'message' => __('Retry'),
                            'data'=>['retry'=>true]
                        ], 400);
                    }else{
                        $grand_total = $g_total = 0;
                        $total_charges = 0;
                        $discount = 0;
                    }
                }else{
                    // return response(['status' => "success", 'statuscode' => 200,'message' => __('Request Not Created '),'data'=>['amountNotSufficient'=>true,'wallet_type'=>$wallet_type]], 200);
                }
            }
            return response(['status' => "success", 'statuscode' => 200,'message' => __('Booking confirmed'), 'data'=>['amountNotSufficient'=>false,'total'=>$total_charges,'discount'=>0,'grand_total'=>$total_charges,'book_slot_time'=>$user_time_zone_slot,'book_slot_date'=>$user_time_zone_date,'coupon'=>$coupon]], 200);
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage().' '.$ex->getLine()], 500);
        }
    }    


    /**
     * @SWG\Post(
     *     path="/update-request-symptoms",
     *     description="updateRequestSymptoms",
     * tags={"Customer"},
     *     security={
     *     {"Bearer": {}},
     *   },
     *  @SWG\Parameter(
     *         name="request_id",
     *         in="query",
     *         type="string",
     *         description="request_id for update request",
     *         required=false,
     *     ), 
     *  @SWG\Parameter(
     *         name="option_ids",
     *         in="query",
     *         type="string",
     *         description="Symptoms option ids comma seprated",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="symptom_details",
     *         in="query",
     *         type="string",
     *         description="symptom_details limit 155 char",
     *         required=false,
     *     ), 
     *  @SWG\Parameter(
     *         name="image",
     *         in="query",
     *         type="string",
     *         description="file image,pdf",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="type",
     *         in="query",
     *         type="string",
     *         description="file type image,pdf",
     *         required=false,
     *     ), 
     *  @SWG\Parameter(
     *         name="images",
     *         in="query",
     *         type="string",
     *         description="array type images [{'image':'hahba.png','type':'image'}]",
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
     public function updateRequestSymptoms(Request $request) {
        try{
            $user = Auth::user();
            $rules = ['request_id'=>'required|exists:requests,id'];
            $validator = Validator::make($request->all(),$rules);
            if ($validator->fails()) {
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                    $validator->getMessageBag()->first()), 400);
            }
            $request_data = RequestData::where(['id'=>$request->request_id])->first();
            $this->insertRequestSymptoms($request_data,$request->all());
            if(isset($request->symptom_details)){
                $this->freeTextSymptomDetails($request_data,$request->all());
            }
            return response([
                'status' => "success",
                'statuscode' => 200,
                'message' => __('Appointment updated'),
                'data'=>['request'=>['id'=>$request_data->id]]
            ], 200);
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage().' '.$ex->getLine()], 500);
        }
     }


     /**
     * @SWG\Post(
     *     path="/update-request-prefrences",
     *     description="updateRequestPrefrences",
     * tags={"Customer"},
     *     security={
     *     {"Bearer": {}},
     *   },
     *  @SWG\Parameter(
     *         name="request_id",
     *         in="query",
     *         type="string",
     *         description="request_id for update request",
     *         required=false,
     *     ), 
     *     @SWG\Parameter(
     *         name="master_preferences",
     *         in="query",
     *         type="string",
     *         description="master_preferences array",
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
     public function updateRequestPrefrences(Request $request) {
        try{
            $user = Auth::user();
            $rules = ['request_id'=>'required|exists:requests,id'];
            $validator = Validator::make($request->all(),$rules);
            if ($validator->fails()) {
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                    $validator->getMessageBag()->first()), 400);
            }
            $input = $request->all();
            $request_data = RequestData::where(['id'=>$request->request_id])->first();
            if(isset($input['master_preferences'])){
                  if(!is_array($input['master_preferences']))
                    $input['master_preferences'] = json_decode($input['master_preferences']);
                  if(is_array($input['master_preferences'])){
                    foreach ($input['master_preferences'] as $cus_key => $master_preference) {
                        if($master_preference->preference_id){
                            \App\Model\UserMasterPreference::where([
                                'user_id'=>$user->id,
                                'preference_id'=>$master_preference->preference_id,
                                'request_id' => $request_data->id,
                            ])->delete();
                            foreach ($master_preference->option_ids as $option_key => $option) {
                                \App\Model\UserMasterPreference::firstOrCreate([
                                    'user_id'=>$user->id,
                                    'preference_id'=>$master_preference->preference_id,
                                    'preference_option_id'=>$option,
                                    'request_id' => $request_data->id,
                                ]);
                            }
                        }
                    }
                }
            }
            return response([
                'status' => "success",
                'statuscode' => 200,
                'message' => __('Appointment updated'),
                'data'=>['request'=>['id'=>$request_data->id]]
            ], 200);
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage().' '.$ex->getLine()], 500);
        }
     }


     /**
     * @SWG\Post(
     *     path="/request-user-approve",
     *     description="updateUserRequestStatus",
     * tags={"Customer"},
     *     security={
     *     {"Bearer": {}},
     *   },
     *  @SWG\Parameter(
     *         name="request_id",
     *         in="query",
     *         type="string",
     *         description="request_id",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="status",
     *         in="query",
     *         type="string",
     *         description="status approved,declined",
     *         required=false,
     *     ), 
     *  @SWG\Parameter(
     *         name="comment",
     *         in="query",
     *         type="string",
     *         description="comment service time related meesage",
     *         required=false,
     *     ), 
     *  @SWG\Parameter(
     *         name="valid_hours",
     *         in="query",
     *         type="string",
     *         description="valid_hours for approval",
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
     public function updateUserRequestStatus(Request $request) {
        try{
            $user = Auth::user();
            $rules = ['request_id'=>'required|exists:requests,id','status'=>'required|in:approved,declined','comment'=>'required'];
            $validator = Validator::make($request->all(),$rules);
            if ($validator->fails()) {
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                    $validator->getMessageBag()->first()), 400);
            }
            $request_data = RequestData::where(['id'=>$request->request_id])->first();
            if($request_data->user_status=='pending'){
                if(isset($request->valid_hours)){
                    $request_data->user_by_hours = $request->valid_hours;
                }
                $request_data->user_status = $request->status;
                $request_data->user_comment = $request->comment;
                $request_data->save();
            }
            return response([
                'status' => "success",
                'statuscode' => 200,
                'message' => __('Request User Status Updated'),
                'data'=>['request'=>['id'=>$request_data->id]]
            ], 200);
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage().' '.$ex->getLine()], 500);
        }
     } 


     /**
     * @SWG\Post(
     *     path="/extra-payment",
     *     description="appointmentExtraPayment",
     * tags={"Customer"},
     *     security={
     *     {"Bearer": {}},
     *   },
     *  @SWG\Parameter(
     *         name="request_id",
     *         in="query",
     *         type="string",
     *         description="request_id",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="balance",
     *         in="query",
     *         type="string",
     *         description="balance",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="description",
     *         in="query",
     *         type="string",
     *         description="description",
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
     public function appointmentExtraPayment(Request $request) {
        try{
            $user = Auth::user();
            $rules = ['request_id'=>'required|exists:requests,id','balance'=>'required','description'=>'required'];
            $validator = Validator::make($request->all(),$rules);
            if ($validator->fails()) {
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                    $validator->getMessageBag()->first()), 400);
            }
            $input = $request->all();
            $request_data = RequestData::where(['id'=>$request->request_id])->first();
            if($request_data->requesthistory->extra_payment_status!==null){
                return response(['status' => "error", 'statuscode' => 400, 'message' =>'This Appointment has been already requested for extra payment'], 400);    
            }
            $dateznow = new DateTime("now", new DateTimeZone('UTC'));
            $datenow = $dateznow->format('Y-m-d H:i:s');
            $request_data->requesthistory->extra_payment = $input['balance'];
            $request_data->requesthistory->extra_payment_status = 'pending';
            $request_data->requesthistory->extra_payment_description = $input['description'];
            $request_data->requesthistory->extra_payment_datetime = $datenow;
            $request_data->requesthistory->save();
            $sr_info_name = $request_data->sr_info->name;
            $payment = $request_data->requesthistory->extra_payment;
            $notification = new Notification();
            $notification->sender_id = $request_data->from_user;
            $notification->receiver_id = $request_data->to_user;
            $notification->module_id = $request_data->id;
            $notification->module ='extra_payment';
            $notification->notification_type ='REQUEST_EXTRA_PAYMENT';
            $notification->message =__("$sr_info_name has requested for extra payment of $payment");
            $notification->save();
            $notification->push_notification(array($request_data->from_user),array('pushType'=>'REQUEST_EXTRA_PAYMENT','request_id'=>$request_data->id,'message'=>__("$sr_info_name has requested for extra payment of $payment")));

            return response([
                'status' => "success",
                'statuscode' => 200,
                'message' => __('Extra Payment request created'),
                'data'=>['request_id'=>$request_data->id]
            ], 200);
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage().' '.$ex->getLine()], 500);
        }
     }
     /**
     * @SWG\Post(
     *     path="/pay-extra-payment",
     *     description="appointmentExtraPayment",
     * tags={"Customer"},
     *     security={
     *     {"Bearer": {}},
     *   },
     *  @SWG\Parameter(
     *         name="request_id",
     *         in="query",
     *         type="string",
     *         description="request_id",
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
     public function acceptAppointmentExtraPayment(Request $request) {
        try{
            $user = Auth::user();
            $rules = ['request_id'=>'required|exists:requests,id'];
            $validator = Validator::make($request->all(),$rules);
            if ($validator->fails()) {
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                    $validator->getMessageBag()->first()), 400);
            }
            $request_data = RequestData::where(['id'=>$request->request_id])->first();
            if($user->wallet->balance<$request_data->requesthistory->extra_payment){
                    return response([
                                'status' => "success",
                                'statuscode' => 200,
                                'message' => __("Amount not sufficient please add money into your wallet"),
                                'data'=>['amountNotSufficient'=>true,'message'=>"Amount not sufficient please add money into your wallet"]
                            ], 200);
            }
            $input = $request->all();
            $input['payment_type'] = 'extra_payment';
            $input['user'] = $user;
            $input['balance'] = $request_data->requesthistory->extra_payment;
            $status = 'succeeded';
            $withdrawal_to = array(
            'balance'=>$request_data->requesthistory->extra_payment,
            'user'=>$request_data->cus_info,
            'from_id'=>$request_data->sr_info->id,
            'request_id'=>$request_data->id,
            'status'=>$status,
            'module_table'=>'extra_payment',
            'module_id'=>$request_data->id
            );
            Transaction::createWithdrawalExtraPayment($withdrawal_to);
            $deposit_to = array(
                'balance'=>$request_data->requesthistory->extra_payment,
                'user'=>$request_data->sr_info,
                'from_id'=>$request_data->cus_info->id,
                'request_id'=>$request_data->id,
                'status'=>$status,
                'module_table'=>'extra_payment',
                'module_id'=>$request_data->id
            );
            Transaction::createDepositExtraPayment($deposit_to);
            $request_data->requesthistory->extra_payment_status = 'paid';
            $request_data->requesthistory->save();
            return response(['status' => "success", 'statuscode' => 200,'message' => __('Extra payment successfully proceed'),
                'data'=>[
                        'amountNotSufficient'=>false,
                        'total_charges'=>$request_data->requesthistory->extra_payment,
                        'request'=>['id'=>$request_data->id],
            ]], 200);
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage().' '.$ex->getLine()], 500);
        }
     } 


     /**
     * @SWG\Post(
     *     path="/create-request",
     *     description="postCreateRequest",
     * tags={"Customer"},
     *     security={
     *     {"Bearer": {}},
     *   },
     *  @SWG\Parameter(
     *         name="consultant_id",
     *         in="query",
     *         type="number",
     *         description=" Consultant  Id",
     *         required=true,
     *     ),
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
     *         description="date e.g 22:10",
     *         required=false,
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
     *         name="request_id",
     *         in="query",
     *         type="string",
     *         description="request_id for update request",
     *         required=false,
     *     ),     
     *  @SWG\Parameter(
     *         name="package_id",
     *         in="query",
     *         type="string",
     *         description="package_id ",
     *         required=false,
     *     ),     
     *  @SWG\Parameter(
     *         name="payment_type",
     *         in="query",
     *         type="string",
     *         description="payment_type  = subscription for MYPATH APP",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="second_oponion",
     *         in="query",
     *         type="string",
     *         description="second_oponion true or false for curenik",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="title",
     *         in="query",
     *         type="string",
     *         description="title for second_oponion(curenik)",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="record_type",
     *         in="query",
     *         type="string",
     *         description="record_type for second_oponion(curenik)",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="images",
     *         in="query",
     *         type="string",
     *         description="images(comma seprated) for second_oponion(curenik)",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="lat",
     *         in="query",
     *         type="string",
     *         description="lat for Intely APP",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="long",
     *         in="query",
     *         type="string",
     *         description="long for Intely APP",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="first_name",
     *         in="query",
     *         type="string",
     *         description="first_name for Intely APP",
     *         required=false,
     *     ),     
     *  @SWG\Parameter(
     *         name="last_name",
     *         in="query",
     *         type="string",
     *         description="last_name for Intely APP",
     *         required=false,
     *     ),     
     *  @SWG\Parameter(
     *         name="service_for",
     *         in="query",
     *         type="string",
     *         description="service_for for Intely APP",
     *         required=false,
     *     ),     
     *  @SWG\Parameter(
     *         name="home_care_req",
     *         in="query",
     *         type="string",
     *         description="home_care_req for Intely APP",
     *         required=false,
     *     ),     
     *  @SWG\Parameter(
     *         name="reason_for_service",
     *         in="query",
     *         type="string",
     *         description="reason_for_service for Intely APP",
     *         required=false,
     *     ),     
     *  @SWG\Parameter(
     *         name="service_address",
     *         in="query",
     *         type="string",
     *         description="service_address for Intely APP",
     *         required=false,
     *     ), 
     *  @SWG\Parameter(
     *         name="filter_id",
     *         in="query",
     *         type="string",
     *         description="filter option id ",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="option_ids",
     *         in="query",
     *         type="string",
     *         description="Symptoms option ids comma seprated",
     *         required=false,
     *     ), 
     *  @SWG\Parameter(
     *         name="duties",
     *         in="query",
     *         type="string",
     *         description="duties ids comma seprated",
     *         required=false,
     *     ), 
     *  @SWG\Parameter(
     *         name="request_step",
     *         in="query",
     *         type="string",
     *         description="request_step for Intely confirm,create",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="phone_number",
     *         in="query",
     *         type="string",
     *         description="phone_number ",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="country_code",
     *         in="query",
     *         type="string",
     *         description="country_code",
     *         required=false,
     *     ), 
     *  @SWG\Parameter(
     *         name="tier_id",
     *         in="query",
     *         type="string",
     *         description="Tier Id of NurseLynx",
     *         required=false,
     *     ), 
     * *  @SWG\Parameter(
     *         name="from_home",
     *         in="query",
     *         type="string",
     *         description="true",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="tier_options",
     *         in="query",
     *         type="string",
     *         description="Tier Options as array [{'id':'option_id','type':'1 for need some help and 2 for need much help'}]",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="clinic_id",
     *         in="query",
     *         type="string",
     *         description="Clinic ID",
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
            $domain_name = 'intely';
            $user = Auth::user();
            if(isset($request->clinic_id)){
                return $this->clinicCreateRequest($request);
            }
            if(Config::get('client_connected')&&Config::get('client_data')->domain_name=='curenik' && $request->from_home == 'true'){ 
               
                $rules = ['schedule_type'=>["required" , "max:255", "in:instant,schedule"]];
                $rules['category_id'] = 'required|exists:categories,id';
                $rules['service_id'] = 'required|exists:services,id';
               
                if(isset($request->coupon_code)){
                    $request->merge(['coupon_code' => strtoupper($request->coupon_code)]);
                    $rules['coupon_code'] = 'required|exists:coupons,coupon_code';
                }
                if(isset($request->package_id)){
                    $rules['package_id'] = 'required|exists:packages,id';
                }
                 if(isset($request->payment_type)){
                    $rules['payment_type'] = 'required|in:subscription';
                }
                if(isset($request->tier_id)){
                    $rules['tier_id'] = 'required|exists:tiers,id';
                }
                $validator = Validator::make($request->all(),$rules);
                if ($validator->fails()) {
                    return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                        $validator->getMessageBag()->first()), 400);
                }
                $input = $request->all();
                $categoryservicetype_id = CategoryServiceType::where([
                    'category_id'=>$request->category_id,
                    'service_id'=>$request->service_id
                ])->first();
           
                $dateznow = new DateTime("now", new DateTimeZone('UTC'));
                $timenow = $dateznow->format('H:i:s');
                $datenow = $dateznow->format('Y-m-d');

                $getslots = \App\Model\EmergencyTimeSlot::where('category_sevice_type' , $categoryservicetype_id->id)
                                                    ->where([
                                                        ['start_time' , '<' , $timenow],
                                                        ['end_time' , '>' , $timenow]
                                                    ])->first();
           
                
               if($getslots)
               {
                    $total_charges = $getslots->price;
                    $grand_total = $getslots->price;
               }
               else
               {
                return response(['status' => "error", 'statuscode' => 500, 'message' => 'No Emergency Time Slot' ]);
               }
                $discount = 0;
                $user_time_zone_slot =  $timenow;
                $user_time_zone_date = $datenow ;
                $request_data = null;
                
              
            }
            else{
            $validator = $this->validatorCreateRequest($request);
            if($validator){
                return $validator;
            }
            $input = $request->all();
            $total_hours = 0;
            $request_data = null;
            if(isset($request->request_id)){
                $request_data = \App\Model\Request::where('id',$request->request_id)->first();
                $input['consultant_id'] = $request_data->to_user;
                $input['service_id'] = $request_data->service_id;
                $dateznow = new DateTime("now", new DateTimeZone('UTC'));
                $datenow = $dateznow->format('Y-m-d H:i:s');
                $next_hour_time = strtotime($datenow) + 3600;
                if(strtotime($request_data->booking_date)<=$next_hour_time){
                    return response(array('status' => "error", 'statuscode' => 400, 'message' =>__('Request could not Re-Scheduled becuase request going live into next hour')), 400);
                }
            }
            $consult = User::find($input['consultant_id']);
            if(!$consult || !$consult->hasrole('service_provider')){
                return response(array(
                    'status' => "error",
                    'statuscode' => 400,
                    'message' =>__('Consultant not found')),400);
            }
            $category_id = $consult->getCategoryData($input['consultant_id']);
            if(!$category_id){
                return response(array(
                    'status' => "error",
                    'statuscode' => 400,
                    'message' =>__("This Vendor have not assigned any Category")
                ), 400);
            }
            $category = \App\Model\Category::where('id',$category_id->id)->first();
            $categoryservicetype_id = CategoryServiceType::where([
                'category_id'=>$category_id->id,
                'service_id'=>$input['service_id']
            ])->first();
            $spservicetype_id = null;
            if($categoryservicetype_id){
                $spservicetype_id = \App\Model\SpServiceType::where([
                    'category_service_id'=>$categoryservicetype_id->id,
                    'sp_id'=>$input['consultant_id']
                ])->first();
            }
            if(!$spservicetype_id){
                return response(array(
                    'status' => "error",
                    'statuscode' => 400,
                    'message' =>__("Service not found into the $category_id->name category")
                    ), 400);
            }
            $slot_duration = \App\Model\EnableService::where('type','slot_duration')->first();
            $unit_price = \App\Model\EnableService::where('type','unit_price')->first();
            $per_minute = $spservicetype_id->price/$unit_price->value;
            $slot_minutes = $slot_duration->value;
            $add_slot_second = $slot_duration->value * 60;

            if($consult && $consult->slot_duration)
            {
                $slot_minutes = $consult->slot_duration;
                $add_slot_second = $consult->slot_duration * 60;
            }
            if($request_data){
                $total_charges = $request_data->requesthistory->total_charges;
                $grand_total= $g_total = $request_data->requesthistory->total_charges;
            }else{
                /* For Intely Charges Calculation with Hours */
                if($request->has('request_type')){
                    $dates = explode(',',$input['dates']);
                    foreach ($dates as $key => $date) {
                        $start  = new Carbon($date.' '.$input['start_time']);
                        $end    = new Carbon($date.' '.$input['end_time']);
                        // $total_hours = $total_hours + $start->diff($end)->format('%h');
                        $total_hours = $total_hours + ($end->diffInSeconds($start))/3600;
                    }
                    if(Config::get('client_connected')&&Config::get('client_data')->domain_name=='intely' && $categoryservicetype_id->price_fixed!=null){
                        $selected_filter_options = $consult->getSelectedFiltersByCategory($consult->id);
                        if(isset($selected_filter_options[0]) && $selected_filter_options[0]['price']){
                            $per_minute = $selected_filter_options[0]['price']/$unit_price->value;
                        }else{
                            $per_minute = $categoryservicetype_id->price_fixed/$unit_price->value;
                        }
                        $total_charges = ($total_hours*60) * $per_minute;
                        $grand_total= $g_total = ($total_hours*60) * $per_minute;
                    }else{
                        $total_charges = ($total_hours*60) * $per_minute;
                        $grand_total= $g_total = ($total_hours*60) * $per_minute;
                    }
                    if($total_hours<4){
                        return response(array('status' => "error", 'statuscode' => 400, 'message' =>__("Request could not create minimum 4 hours required for booking")), 400);
                    }
                }else{
                    $total_charges = $slot_minutes * $per_minute;
                    $grand_total= $g_total = $slot_minutes * $per_minute;

                    
                    if(isset($input['end_date']) && isset($input['end_time'])){
                        $start  = new Carbon($input['date'].' '.$input['time']);
                        $end    = new Carbon($input['end_date'].' '.$input['end_time']);
                        $total_hours = $end->diffInSeconds($start)/3600;
                        $total_charges = ($total_hours*60) * $per_minute;
                        $grand_total= $g_total = ($total_hours*60) * $per_minute;
                    }
                    if(Config::get('client_connected')&&Config::get('client_data')->domain_name=='healtcaremydoctor' && $categoryservicetype_id->price_fixed!=null){
                        $total_charges = $categoryservicetype_id->price_fixed;
                        $grand_total= $g_total = $categoryservicetype_id->price_fixed;
                    }
                }
            }
        }


            $distance = 0;
            $distance_price = 0;
            $distance_price_per_km = 0;
            if(isset($input['lat']) && isset($input['long']) && $spservicetype_id->km_price){
                $distance_price_per_km = $spservicetype_id->km_price;
                $profile =  DB::table('profiles')
                ->select('*')
                ->where('user_id',$consult->id)->first();
                $distance = Helper::getDistance($input['lat'],$input['long'],$profile->lat,$profile->long);
                if($distance>20){
                    $distance = $distance - 20;
                    $distance_price = $distance*$distance_price_per_km;
                }
            }

            $grand_total= $g_total = $total_charges = $grand_total + $distance_price;
            $service_tax = 0;
            $tax_percantage = 0;
            /* For add Tier */
            if(isset($input['tier_id'])){
                $tier = \App\Model\Tier::find($input['tier_id']);
                $per_minute = $tier->price/60;
                $grand_total = $grand_total + $tier->price;
                // $grand_total = $tier->price;
                $total_charges = $total_charges + $tier->price;
                // $total_charges =  $tier->price;
            }
            if(Config::get('client_connected') && Config::get('client_data')->domain_name=='homedoctor'){
                $tax_percantage = 15;
                $service_tax = round(($total_charges * $tax_percantage)/100,2);
                $grand_total = $service_tax + $total_charges;
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
                $coupon_validation = self::couponCodeValidation($request->coupon_code,$user,$total_charges,$service_tax);
                if($coupon_validation['status']=='error'){
                   return response($coupon_validation,400);
                }
                if($coupon_validation['status']=='success'){
                    $coupon = true;
                    $grand_total= $g_total = $coupon_validation['grand_total'];
                    $discount = $coupon_validation['discount'];
                }
            }
            if(isset($input['package_id'])){
                $subscribe = Helper::isSusbScribe([
                    'user_id'=>$user->id,
                    'package_id'=>$input['package_id']
                ]);
                if(!$subscribe){
                    $package = Package::where('id',$input['package_id'])->first();
                    if($package){
                        if($category->payment_type=='cash'){
                            $userpackage  = UserPackage::firstOrCreate([
                                'user_id'=>$user->id,
                                'package_id'=>$input['package_id']
                            ]);
                            if($userpackage){
                                $userpackage->increment('available_requests',$package->total_requests);
                            }
                            $grand_total = $g_total = 0;
                            $total_charges = 0;
                            $discount = 0;
                        }else{
                            $g_total = $package->price;
                            $grand_total = 0;
                            $total_charges = $package->price;
                            $discount = 0;
                        }
                    }
                }else{
                    $grand_total = $g_total = 0;
                    $total_charges = 0;
                    $discount = 0;
                }
            }

            if(isset($input['payment_type'])){
                $grand_total = $g_total = 0;
                $total_charges = 0;
                $discount = 0;
            }
            $free_request = \App\Model\MonthlyRequest::checkFreeRequest($user->id);
            if($free_request){
                $grand_total = 0;
                $discount = $total_charges;
            }
            $wallet_type = 'user_wallet';
            $user_wallet = $user;
            $user_time_zone_slot = '';
            $user_time_zone_date = '';
        if(!$request->from_home){ 
            if($request->schedule_type=='schedule' || $request->schedule_type=='date_time'){
                    if($request->has('request_type')){
                        $dates = explode(',',$input['dates']);
                        $datenow = Carbon::parse($dates[0].' '.$request->start_time,$timezone)->setTimezone('UTC')->format('Y-m-d H:i:s');
                        foreach ($dates as $key => $r_date) {
                            $slot_data = $this->isSlotBooked($r_date,$request->start_time,$request->end_time,$timezone,$input['consultant_id']);
                            if($slot_data){
                                // $datenow = Carbon::parse($slot_data->start_date_time,$timezone)->setTimezone('UTC')->format('Y-m-d H:i:s');
                                return response(array('status' => "error", 'statuscode' => 400, 'message' =>__("Request could not create $r_date $request->start_time slot already full or gap duration")), 400);
                            }
                        }
                    }else{
                        $connect_now_validation_disable = false;
                        if(Config::get('client_connected') && (Config::get('client_data')->domain_name=='mp2r' || Config::get('client_data')->domain_name=='food')){
                            $connect_now_validation_disable = true;
                        }
                        $datenow = Carbon::parse($request->date.' '.$request->time,$timezone)->setTimezone('UTC')->format('Y-m-d H:i:s');
                        $datenow2 = Carbon::parse($request->date.' '.$request->time,$timezone)->addSeconds(60)->setTimezone('UTC')->format('Y-m-d H:i:s');
                        if(isset($input['end_date']) && isset($input['end_time'])){
                            $end_time_slot_utcdate = Carbon::parse($input['end_date'].' '.$input['end_time'])->setTimezone('UTC')->format('Y-m-d H:i:s');
                            $end_time_slot_utcdate2 = Carbon::parse($input['end_date'].' '.$input['end_time'])->addSeconds(-1)->setTimezone('UTC')->format('Y-m-d H:i:s');

                        }else{
                            $end_time_slot_utcdate = Carbon::parse($datenow)->addSeconds($add_slot_second)->setTimezone('UTC')->format('Y-m-d H:i:s');
                            $end_time_slot_utcdate2 = Carbon::parse($datenow)->addSeconds($add_slot_second-1)->setTimezone('UTC')->format('Y-m-d H:i:s');
                        }
                        $user_time_zone_slot = Carbon::parse($datenow)->setTimezone($timezone)->format('h:i a');
                        $user_time_zone_date = Carbon::parse($datenow)->setTimezone($timezone)->format('Y-m-d');
                        if(Config::get('client_connected') && (Config::get('client_data')->domain_name=='care_connect_live'))
                        {
                           
                            $max_slot = '5';
                            $exist = \App\Model\Request::where('to_user',$input['consultant_id'])
                            ->whereBetween('booking_date', [$datenow, $end_time_slot_utcdate2])
                                ->whereHas('requesthistory', function ($query) use($connect_now_validation_disable) {
                                    $query->where('status','!=','canceled');
                                    $query->where('status','!=','failed');
                                    if($connect_now_validation_disable)
                                        $query->where('schedule_type','!=','instant');
                
                                })
                                ->where(function($query2) use ($request_data){
                                    if(isset($request_data->id))
                                        $query2->where('id','!=',$request_data->id);
                                })
                            ->get();
                            if($exist->count()>=5){
                                return response(array('status' => "error", 'statuscode' => 400, 'message' =>__("Request could not create $request->time slot already full")), 400);
                            }
                        }
                        else
                        {
                            
                            $exist = \App\Model\Request::where('to_user',$input['consultant_id'])
                            ->whereBetween('booking_date', [$datenow2, $end_time_slot_utcdate2])
                            ->whereHas('requesthistory', function ($query) use($connect_now_validation_disable) {
                                $query->where('status','!=','canceled');
                                $query->where('status','!=','failed');
                                if($connect_now_validation_disable)
                                    $query->where('schedule_type','!=','instant');
                            })
                            ->where(function($query2) use ($request_data){
                                if(isset($request_data->id))
                                    $query2->where('id','!=',$request_data->id);
                            })
                            ->get();
                            if($exist->count()>0){
                                return response(array('status' => "error", 'statuscode' => 400, 'message' =>__("Request could not create $request->time slot already full")), 400);
                            }
                        }
                    }
            }else{
                $data = [];
                while ($data==false) {
                    $data = $this->checkSlotFullOrNot($slot_duration,$timezone,$add_slot_second,$input,$request_data);
                    $slot_duration->value = $slot_duration->value + $slot_minutes;
                }
                $user_time_zone_date = $data['user_time_zone_date'];
                $user_time_zone_slot = $data['user_time_zone_slot'];
                $end_time_slot_utcdate = $data['end_time_slot_utcdate'];
                $datenow = $data['datenow'];
            }
        }
       
            if($request->has('request_step') && $request->request_step=='confirm'){
                return response([
                    'status' => "success",
                    'statuscode' => 200,
                    'message' => __('Booking Confirmed'),
                    'data'=>[
                        'total'=>$total_charges,
                        'discount'=>$discount,
                        'grand_total'=>$grand_total,
                        'service_tax'=>$service_tax,
                        'tax_percantage'=>$tax_percantage,
                        'coupon'=>$coupon]
                    ], 200);
            }else if($request->has('request_step') && $request->request_step=='create'){
                    $input['balance'] = $grand_total;
                    $input['grand_total'] = $grand_total;
                    $input['total_hours'] = $total_hours;
                    $input['total_charges'] = $total_charges + $service_tax;
                    $input['discount'] = $discount;
                    $input['per_minute'] = $per_minute;
                    $input['user'] = $user;
                    $input['coupon_validation'] = $coupon_validation;
                    if(Config::get('client_connected') && Config::get('client_data')->domain_name=='homedoctor'){
                        if($user_wallet->wallet->balance<$grand_total){
                                return response([
                                    'status' => "success",
                                    'statuscode' => 200,
                                    'message' => __('Request Not Created '),
                                    'data'=>['amountNotSufficient'=>true,
                                    'wallet_type'=>$wallet_type]
                                ], 200);
                        }
                        $response =  Helper::createPaymentByAlRajahiBank($input,$user);
                    }else{
                        $response =  Helper::createPayment($input,$user);
                    }
                    return response($response,$response['statuscode']);
            }else{
                if(!$request->has('request_type')){
                    if(Config::get('client_connected') && (Config::get('client_data')->domain_name=='mp2r' || Config::get('client_data')->domain_name=='food')){

                    }else{
                        // print_r($user_wallet->wallet);die;
                        $minimum_balance = \App\Model\EnableService::where('type','minimum_balance')->first();
                        if($user_wallet->wallet->balance<$grand_total && !$minimum_balance && $category->payment_type=='online'){
                            $amnt = $grand_total - $user_wallet->wallet->balance;
                            if($request_data==null)
                                return response([
                                    'status' => "success",
                                    'statuscode' => 200,
                                    'message' => __("Request could not be created, need to add money $amnt"),
                                    'data'=>['amountNotSufficient'=>true,
                                    'wallet_type'=>$wallet_type,'minimum_balance'=>null,'message'=>"Request could not be created, need to add money $amnt"]
                                ], 200);
                        }
                        if($minimum_balance && $minimum_balance->value && $user_wallet->wallet->balance<($minimum_balance->value + $grand_total)){
                            $amnt = ($minimum_balance->value + $grand_total) - $user_wallet->wallet->balance;
                            $currency = \App\Model\EnableService::where('type','currency')->first();
                            if($request_data==null)
                                return response([
                                    'status' => "success",
                                    'statuscode' => 200,
                                    'message' => __("Request could not be created, need to add money $amnt to maintain balance $minimum_balance->value"),
                                    'data'=>['amountNotSufficient'=>true,
                                    'wallet_type'=>$wallet_type,'minimum_balance'=>$minimum_balance->value,'message'=>"Request could not be created, need to add money $amnt $currency->value"]
                                ], 200);
                        }
                    }
                }
            }
            // }
            $message = 'Something went wrong';
            if($request_data){
                if($request->has('filter_id')){
                    $request_data->request_category_type = 'filter_option';
                    $request_data->request_category_type_id = $input['filter_id'];
                }
                $request_data->booking_date = $datenow;
                $request_data->requesthistory->schedule_type = $request->schedule_type;
                $request_data->requesthistory->save();
                $request_data->save();
                $notification = new Notification();
                $notification->sender_id = $user->id;
                $notification->receiver_id = $input['consultant_id'];
                $notification->module_id = $request_data->id;
                $notification->module ='request';
                $notification->notification_type ='RESCHEDULED_REQUEST';
                $notification->message =__('notification.rescheduled_text', ['user_name' => $user->name]);
                $notification->save();
                if(Config::get('client_connected')&&Config::get('client_data')->domain_name=='care_connect_live'){ 
                $push_data = ["template_name" => 'booking', "consultant_id" =>$input['consultant_id'], "user_id" => $user->id, "request_id"=>$sr_request->id, "service_id" => $input['service_id'], "category_id" => $request->category_id ];
                 

                $job = (new RequestSmsEmail($push_data));
                dispatch($job);

                }
                $notification->push_notification(array($input['consultant_id']),array('pushType'=>'RESCHEDULED_REQUEST','request_id'=>$request_data->id,'message'=>__('notification.rescheduled_text', ['user_name' => $user->name])));
                return response(['status' => "success", 'statuscode' => 200,'message' => __('Request Re-Scheduled'),'data'=>['amountNotSufficient'=>false]], 200);
            }else{  
               
                $second_oponion = false;
                if(Config::get('client_connected')&&Config::get('client_data')->domain_name=='curenik' && $request->from_home == 'true'){ 
                    //$user = Auth::user();
                    $dateznow = new DateTime("now", new DateTimeZone('UTC'));
                    $timenow = $dateznow->format('H:i:s');
                    $datenow = $dateznow->format('Y-m-d');
                    $user_time_zone_slot =  $timenow;
                    $user_time_zone_date = $datenow ;
                    $booking_date = $dateznow->format('Y-m-d H:i:s');
                    $emr_request = new \App\Model\EmergancyRequest();
                    $emr_request->from_user = $user->id;
                    $emr_request->booking_date = $booking_date;
                    $emr_request->to_user = isset($input['consultant_id']) ? $input['consultant_id'] : '';
                    $emr_request->service_id =isset($input['service_id']) ? $input['service_id'] : '';
                    $emr_request->status = 'pending';
                    $emr_request->request_type = 'emergency';
                    $emr_request->save();

                    if(isset($coupon_validation['status']) && $coupon_validation['status']=='success'){
                        $requesthistory->coupon_id = $coupon_validation['coupon_id'];
                        $couponused = new CouponUsed();
                        $couponused->user_id =  $user->id;
                        $couponused->coupon_id =  $coupon_validation['coupon_id'];
                        $couponused->save();
                    }
                    $service_type = \App\Model\Service::where('id',$input['service_id'])->first();
                  
                    $push_data = ["username" => $user->name, "userid" => $user->id, "id"=>$emr_request->id, "time" => $user_time_zone_slot, "category_service_id" => $categoryservicetype_id->id, "category_id" => $request->category_id, "service_id" => $service_type->id, "service_type" => $service_type->type ];
                 

                    $job = (new EmergencyRequestProcess($push_data));
                    dispatch($job);
                    
                    
                    return response(['status' => "success", 'statuscode' => 200,'message' => __('New Emergency Request Created '),'data'=>[
                        'amountNotSufficient'=>false,
                        'total_charges'=>$total_charges,
                        'service_tax'=>$service_tax,
                        'tax_percantage'=>$tax_percantage,
                        'book_slot_time'=>$user_time_zone_slot,
                        'book_slot_date'=>$user_time_zone_date,
                        'is_second_oponion'=>$second_oponion,
                        'request'=>['id'=>$emr_request->id],
                    ]], 200);
                    
               }
               else
               {
                    $sr_request = new \App\Model\Request();
                    $sr_request->from_user = $user->id;
                    $sr_request->booking_date = $datenow;
                    $sr_request->to_user = isset($input['consultant_id']) ? $input['consultant_id'] : '';
                    $sr_request->service_id =isset($input['service_id']) ? $input['service_id'] : '';;
                    $sr_request->sp_service_type_id = isset($spservicetype_id)?$spservicetype_id->id:null;
                    
                    if($request->has('request_type')){
                        $sr_request->request_type = $input['request_type'];
                        $sr_request->total_hours = $total_hours;
                        $sr_request->payment = 'pending';
                    }
                    if($request->has('filter_id')){
                        $sr_request->request_category_type = 'filter_option';
                        $sr_request->request_category_type_id = $input['filter_id'];
                    }
                }
                if($sr_request->save()){
                    $sr_request->booking_end_date = $end_time_slot_utcdate;
                    $sr_request->save();
                    if($request->has('option_ids')){
                        $this->insertRequestSymptoms($sr_request,$input);
                    }

                    if(Config::get('client_connected') && Config::get('client_data')->domain_name=='healtcaremydoctor'){
                        \Log::channel('custom')->info('booking==========',["ttt"=>$sr_request->booking_date]);
                        $time = new DateTime($sr_request->booking_date);
                        $time->modify("-300 second");
                        $time->format('Y-m-d H:i:s');
                        \Log::channel('custom')->info('afetr time==========',["uttt"=>$time]);
                        $name = $sr_request->cus_info->name;
                        $message = "You have a appointment in few minutes with $name";
                        $push_data = [
                            "request_id"=>$sr_request->id,
                            "to"=>$sr_request->to_user,
                            "message"=>$message
                        ];
                        $job = (new RequestReminder($push_data))->delay($time);
                        dispatch($job);

                        $name = $sr_request->sr_info->name;
                        $message = "You have a appointment in few minutes with $name";
                        $push_data = [
                            "request_id"=>$sr_request->id,
                            "to"=>$sr_request->from_user,
                            "message"=>$message
                        ];
                        $job = (new RequestReminder($push_data))->delay($time);
                        dispatch($job);
                    }

                    if(isset($request->second_oponion) && ($request->second_oponion==='true'||$request->second_oponion===true)){
                        $second_oponion = true;
                        $sr_request->request_type = 'second_oponion';
                        $sr_request->save();
                        $this->addSecondOponion($sr_request,$input);
                    }
                    $this->insertRequestDetail($sr_request->id,$input);
                    /* Requests Dates Saving... */
                    if($request->has('request_type')){
                        $dates = explode(',',$input['dates']);
                        foreach ($dates as $key => $date) {
                            $start_time_multi = Carbon::parse($date.' '.$input['start_time'],$timezone)->setTimezone('UTC')->format('Y-m-d H:i:s');
                            $end_time_multi = Carbon::parse($date.' '.$input['end_time'],$timezone)->setTimezone('UTC')->format('Y-m-d H:i:s');
                            $requestdate  = new RequestDate();
                            $requestdate->request_id = $sr_request->id;
                            $requestdate->start_date_time = $start_time_multi;
                            $requestdate->end_date_time = $end_time_multi;
                            $requestdate->save();
                        }
                    }
                    $requesthistory = new \App\Model\RequestHistory();
                    $requesthistory->duration = 0;
                    $requesthistory->discount = $discount;
                    $requesthistory->service_tax = $service_tax;
                    $requesthistory->tax_percantage = $tax_percantage;
                    $requesthistory->without_discount = $total_charges;
                    $requesthistory->total_charges = $grand_total;
                    $requesthistory->schedule_type = $request->schedule_type;
                    $requesthistory->status = 'pending';
                    $requesthistory->request_id = $sr_request->id;
                    $requesthistory->total_distance = $distance;
                    $requesthistory->total_distance_price_per_km = $distance_price_per_km;
                    $requesthistory->total_distance_price = $distance_price;

                    if(isset($coupon_validation['status']) && $coupon_validation['status']=='success'){
                        $requesthistory->coupon_id = $coupon_validation['coupon_id'];
                        $couponused = new CouponUsed();
                        $couponused->user_id =  $user->id;
                        $couponused->coupon_id =  $coupon_validation['coupon_id'];
                        $couponused->save();
                    }
                    if($requesthistory->save()){
                        $used_packages = $subscribe_plan =false;
                        if(isset($input['package_id'])){
                            $used_packages = true;
                            $subscribe = Helper::isSusbScribe([
                                'user_id'=>$user->id,
                                'package_id'=>$input['package_id']
                            ]);
                            if(!$subscribe){
                                $subscribepackage = Helper::subscribePackage([
                                    'user_id'=>$user->id,
                                    'package_id'=>$input['package_id']]);
                                if($subscribepackage){
                                    $grand_total = 0;
                                    $total_charges = 0;
                                    $discount = 0;
                                }else{
                                    $used_packages = false;
                                }
                            }else{
                                $grand_total = 0;
                                $total_charges = 0;
                                $discount = 0;
                            }
                        }
                        if(isset($input['payment_type'])){
                            $subscribe_plan = true;
                            $grand_total = 0;
                            $total_charges = 0;
                            $discount = 0;
                        }
                        if($used_packages){
                            $requesthistory->module_table = 'packages';
                            $requesthistory->module_id = $input['package_id'];
                            $requesthistory->save();

                            $userpackage  = UserPackage::where([
                                'user_id'=>$user->id,
                                'package_id'=>$input['package_id'],
                            ])->first();
                            $userpackage->decrement('available_requests',1);
                        }else if($subscribe_plan){
                            $requesthistory->module_table = 'subscribe_plans';
                            $requesthistory->module_id = null;
                            $requesthistory->save();
                        }else{
                            if($free_request){
                                $requesthistory->module_table = 'monthly_requests';
                                $requesthistory->module_id = $free_request->id;
                                $requesthistory->save();
                                $free_request->decrement('available_requests',1);
                            }
                            /* If Tier */
                            if(isset($input['tier_id'])){
                                $requesthistory->module_table = 'tier';
                                $requesthistory->module_id = $input['tier_id'];
                                $requesthistory->save();
                            }



                            if($wallet_type=='vendor_wallet'){
                                $withdrawal_to = array(
                                    'balance'=>$grand_total,
                                    'user'=>$sr_request->cus_info,
                                    'sp'=>$sr_request->sr_info,
                                    'from_id'=>1,
                                    'request_id'=>$sr_request->id,
                                    'status'=>'succeeded'
                                );
                                Transaction::createWithdrawalFromSP($withdrawal_to);
                            }else{
                                $status = 'succeeded';
                                if($request->has('request_type')){
                                    $status = 'user-pending';
                                }
                                $withdrawal_to = array(
                                    'balance'=>$grand_total,
                                    'user'=>$sr_request->cus_info,
                                    'from_id'=>$sr_request->sr_info->id,
                                    'request_id'=>$sr_request->id,
                                    'status'=>$status,
                                    // 'category_payment'=>$category->payment_type,
                                );
                                Transaction::createWithdrawal($withdrawal_to);
                                $vendor_sent_money = $total_charges;
                                if($total_charges >0){
                                    $vendor_sent_money = $total_charges - $service_tax;
                                    if($vendor_sent_money<0){
                                        $vendor_sent_money = 0;
                                    }
                                }
                                $ad_percantage = null;
                                $admin_percentage = \App\Model\EnableService::where('type','admin_percentage')->first();
                                if($vendor_sent_money>0){
                                    $admin_percentage_type = 'admin';
                                    if($category->enable_percentage && $category->percentage){
                                        $admin_percentage_type = 'category';
                                        $ad_percantage = $category->percentage;
                                        $sr_request->request_category_type_id = $category->id;
                                        $sr_request->save();
                                    }elseif ($admin_percentage) {
                                        $ad_percantage = $admin_percentage->value;
                                    }
                                    if($ad_percantage){
                                        $admin_cut = round(($vendor_sent_money * $ad_percantage)/100,2);
                                        $vendor_sent_money = $vendor_sent_money - $admin_cut;
                                        $requesthistory->admin_cut = $admin_cut;
                                        $requesthistory->admin_cut_percentage = $ad_percantage;
                                        $requesthistory->admin_percentage_type = $admin_percentage_type;
                                        $requesthistory->save();
                                    }
                                }
                                $deposit_to = array(
                                    'balance'=>$vendor_sent_money,
                                    'user'=>$sr_request->sr_info,
                                    'from_id'=>$sr_request->cus_info->id,
                                    'request_id'=>$sr_request->id,
                                    'status'=>'vendor-pending'
                                );
                                Transaction::createDeposit($deposit_to);
                            }
                        }
                    }
                    $service_type = \App\Model\Service::where('id',$input['service_id'])->first();
                    $notification = new Notification();
                    $notification->sender_id = $user->id;
                    $notification->receiver_id = $input['consultant_id'];
                    $notification->module_id = $sr_request->id;
                    $notification->module ='request';
                    $notification->notification_type ='NEW_REQUEST';
                    $message = __('notification.new_req_text', ['user_name' => $user->name,'service_type'=>($service_type)?($service_type->type):'']);
                    $notification->message =$message;
                    $notification->save();
                    $notification->push_notification(array($input['consultant_id']),array(
                        'request_id'=>$sr_request->id,
                        'pushType'=>'NEW_REQUEST',
                        'is_second_oponion'=>$second_oponion,
                        'message'=>$message
                    ));
                    return response(['status' => "success", 'statuscode' => 200,'message' => __('New Request Created '),'data'=>[
                        'amountNotSufficient'=>false,
                        'total_charges'=>$total_charges,
                        'service_tax'=>$service_tax,
                        'tax_percantage'=>$tax_percantage,
                        'book_slot_time'=>$user_time_zone_slot,
                        'book_slot_date'=>$user_time_zone_date,
                        'is_second_oponion'=>$second_oponion,
                        'request'=>['id'=>$sr_request->id],
                        'distance_price_per_km'=>$distance_price_per_km,
                        'distance_price'=>$distance_price,
                        'distance'=>$distance,
                    ]], 200);
                }
            }
            // return response(['status' => "success", 'statuscode' => 200,'message' => __('Booking confirmed'), 'data'=>['amountNotSufficient'=>false,'total'=>$total_charges,'discount'=>0,'grand_total'=>$total_charges,'book_slot_time'=>$user_time_zone_slot,'book_slot_date'=>$user_time_zone_date,'coupon'=>$coupon]], 200);
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage().' '.$ex->getLine()], 500);
        }
    }

    public function clinicCreateRequest($request) {
        try{
            $domain_name = 'intely';
            $user = Auth::user();
            $validator = $this->validatorClinicConfirmRequest($request);
            if($validator){
                return $validator;
            }
            $input = $request->all();
            $total_hours = 0;
            $request_data = null;
            if(isset($request->request_id)){
                if(\App\Model\Request::rescheduleRequest($request->request_id)){
                    return response(array(
                        'status' => 'error',
                        'statuscode' => 400,
                        'message' =>__('Request could not Re-Scheduled becuase request going live into next hour')), 400);
                }
                $request_data = \App\Model\Request::find($request->request_id);
                $input['consultant_id'] = $request_data->to_user;
                $input['service_id'] = $request_data->service_id;
            }
            $office = \App\Model\Office::find($input['clinic_id']);
            if(!$office){
                 return response(array(
                        'status' => 'error',
                        'statuscode' => 400,
                        'message' =>__('Clinic Not Found')), 400);
            }
            $input['consultant_id'] = $office->sp_id;
            $input['service_id'] = $office->service_id;
            $consult = User::find($input['consultant_id']);
            if(!$consult || !$consult->hasrole('service_provider')){
                return response(array(
                    'status' => "error",
                    'statuscode' => 400,
                    'message' =>__('Consultant not found')),400);
            }
            $category_id = $consult->getCategoryData($input['consultant_id']);
            if(!$category_id){
                return response(array(
                    'status' => "error",
                    'statuscode' => 400,
                    'message' =>__("This Vendor have not assigned any Category")
                ), 400);
            }
            $category = \App\Model\Category::where('id',$category_id->id)->first();
            $categoryservicetype_id = CategoryServiceType::where([
                'category_id'=>$category_id->id,
                'service_id'=>$input['service_id']
            ])->first();
            $spservicetype_id = null;
            $slot_duration = \App\Model\EnableService::where('type','slot_duration')->first();
            $unit_price = \App\Model\EnableService::where('type','unit_price')->first();
            $per_minute = $office->price/$unit_price->value;
            $slot_minutes = $slot_duration->value;
            $add_slot_second = $slot_duration->value * 60;

            if($consult && $consult->slot_duration)
            {
                $slot_minutes = $consult->slot_duration;
                $add_slot_second = $consult->slot_duration * 60;
            }
            if($request_data){
                $total_charges = $request_data->requesthistory->total_charges;
                $grand_total= $g_total = $request_data->requesthistory->total_charges;
            }else{
                $total_charges = $slot_minutes * $per_minute;
                $grand_total= $g_total = $slot_minutes * $per_minute;
            }


            $distance = 0;
            $distance_price = 0;
            $distance_price_per_km = 0;

            $grand_total= $g_total = $total_charges = $grand_total + $distance_price;
            $service_tax = 0;
            $tax_percantage = 0;

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
                $coupon_validation = self::couponCodeValidation($request->coupon_code,$user,$total_charges,$service_tax);
                if($coupon_validation['status']=='error'){
                   return response($coupon_validation,400);
                }
                if($coupon_validation['status']=='success'){
                    $coupon = true;
                    $grand_total= $g_total = $coupon_validation['grand_total'];
                    $discount = $coupon_validation['discount'];
                }
            }
            
            $wallet_type = 'user_wallet';
            $user_wallet = $user;
            $user_time_zone_slot = '';
            $user_time_zone_date = '';
            if($request->schedule_type=='schedule' || $request->schedule_type=='date_time'){
                    
                $connect_now_validation_disable = false;
                $datenow = Carbon::parse($request->date.' '.$request->time,$timezone)->setTimezone('UTC')->format('Y-m-d H:i:s');
                $datenow2 = Carbon::parse($request->date.' '.$request->time,$timezone)->addSeconds(60)->setTimezone('UTC')->format('Y-m-d H:i:s');
                if(isset($input['end_date']) && isset($input['end_time'])){
                    $end_time_slot_utcdate = Carbon::parse($input['end_date'].' '.$input['end_time'])->setTimezone('UTC')->format('Y-m-d H:i:s');
                    $end_time_slot_utcdate2 = Carbon::parse($input['end_date'].' '.$input['end_time'])->addSeconds(-1)->setTimezone('UTC')->format('Y-m-d H:i:s');

                }else{
                    $end_time_slot_utcdate = Carbon::parse($datenow)->addSeconds($add_slot_second)->setTimezone('UTC')->format('Y-m-d H:i:s');
                    $end_time_slot_utcdate2 = Carbon::parse($datenow)->addSeconds($add_slot_second-1)->setTimezone('UTC')->format('Y-m-d H:i:s');
                }
                $user_time_zone_slot = Carbon::parse($datenow)->setTimezone($timezone)->format('h:i a');
                $user_time_zone_date = Carbon::parse($datenow)->setTimezone($timezone)->format('Y-m-d');
                $exist = \App\Model\Request::checkBookingExist($input,$datenow2,$end_time_slot_utcdate2);
                if($exist->count()>0){
                    return response(array('status' => "error", 'statuscode' => 400, 'message' =>__("Request could not create $request->time slot already full")), 400);
                }
            }else{
                $data = [];
                while ($data==false) {
                    $data = $this->checkSlotFullOrNot($slot_duration,$timezone,$add_slot_second,$input,$request_data);
                    $slot_duration->value = $slot_duration->value + $slot_minutes;
                }
                $user_time_zone_date = $data['user_time_zone_date'];
                $user_time_zone_slot = $data['user_time_zone_slot'];
                $end_time_slot_utcdate = $data['end_time_slot_utcdate'];
                $datenow = $data['datenow'];
            }
            
            // print_r($user_wallet->wallet);die;
            $minimum_balance = \App\Model\EnableService::where('type','minimum_balance')->first();
            if($user_wallet->wallet->balance<$grand_total && !$minimum_balance && $category->payment_type=='online'){
                $amnt = $grand_total - $user_wallet->wallet->balance;
                if($request_data==null)
                    return response([
                        'status' => "success",
                        'statuscode' => 200,
                        'message' => __("Request could not be created, need to add money $amnt"),
                        'data'=>['amountNotSufficient'=>true,
                        'wallet_type'=>$wallet_type,'minimum_balance'=>null,'message'=>"Request could not be created, need to add money $amnt"]
                    ], 200);
            }
            if($minimum_balance && $minimum_balance->value && $user_wallet->wallet->balance<($minimum_balance->value + $grand_total)){
                $amnt = ($minimum_balance->value + $grand_total) - $user_wallet->wallet->balance;
                $currency = \App\Model\EnableService::where('type','currency')->first();
                if($request_data==null)
                    return response([
                        'status' => "success",
                        'statuscode' => 200,
                        'message' => __("Request could not be created, need to add money $amnt to maintain balance $minimum_balance->value"),
                        'data'=>['amountNotSufficient'=>true,
                        'wallet_type'=>$wallet_type,'minimum_balance'=>$minimum_balance->value,'message'=>"Request could not be created, need to add money $amnt $currency->value"]
                    ], 200);
            }
            $message = 'Something went wrong';
            if($request_data){
                if($request->has('filter_id')){
                    $request_data->request_category_type = 'filter_option';
                    $request_data->request_category_type_id = $input['filter_id'];
                }
                $request_data->booking_date = $datenow;
                $request_data->requesthistory->schedule_type = $request->schedule_type;
                $request_data->requesthistory->save();
                $request_data->save();
                $notification = new Notification();
                $notification->sender_id = $user->id;
                $notification->receiver_id = $input['consultant_id'];
                $notification->module_id = $request_data->id;
                $notification->module ='request';
                $notification->notification_type ='RESCHEDULED_REQUEST';
                $notification->message =__('notification.rescheduled_text', ['user_name' => $user->name]);
                $notification->save();
                $notification->push_notification(array($input['consultant_id']),array('pushType'=>'RESCHEDULED_REQUEST','request_id'=>$request_data->id,'message'=>__('notification.rescheduled_text', ['user_name' => $user->name])));
                return response(['status' => "success", 'statuscode' => 200,'message' => __('Request Re-Scheduled'),'data'=>['amountNotSufficient'=>false]], 200);
            }else{  
                $second_oponion = false;
                $sr_request = new \App\Model\Request();
                $sr_request->from_user = $user->id;
                $sr_request->booking_date = $datenow;
                $sr_request->to_user = isset($input['consultant_id']) ? $input['consultant_id'] : '';
                $sr_request->service_id =isset($input['service_id']) ? $input['service_id'] : '';;
                $sr_request->sp_service_type_id = isset($spservicetype_id)?$spservicetype_id->id:null;
                if($request->has('clinic_id')){
                    $sr_request->request_category_type = 'clinic';
                    $sr_request->request_category_type_id = $input['clinic_id'];
                }
                
                if($sr_request->save()){
                    $sr_request->booking_end_date = $end_time_slot_utcdate;
                    $sr_request->save();
                    if($request->has('option_ids')){
                        $this->insertRequestSymptoms($sr_request,$input);
                    }
                    if(isset($request->second_oponion) && ($request->second_oponion==='true'||$request->second_oponion===true)){
                        $second_oponion = true;
                        $sr_request->request_type = 'second_oponion';
                        $sr_request->save();
                        $this->addSecondOponion($sr_request,$input);
                    }
                    $this->insertRequestDetail($sr_request->id,$input);
                    $requesthistory = new \App\Model\RequestHistory();
                    $requesthistory->duration = 0;
                    $requesthistory->discount = $discount;
                    $requesthistory->service_tax = $service_tax;
                    $requesthistory->tax_percantage = $tax_percantage;
                    $requesthistory->without_discount = $total_charges;
                    $requesthistory->total_charges = $grand_total;
                    $requesthistory->schedule_type = $request->schedule_type;
                    $requesthistory->status = 'pending';
                    $requesthistory->request_id = $sr_request->id;
                    $requesthistory->total_distance = $distance;
                    $requesthistory->total_distance_price_per_km = $distance_price_per_km;
                    $requesthistory->total_distance_price = $distance_price;

                    if(isset($coupon_validation['status']) && $coupon_validation['status']=='success'){
                        $requesthistory->coupon_id = $coupon_validation['coupon_id'];
                        $couponused = new CouponUsed();
                        $couponused->user_id =  $user->id;
                        $couponused->coupon_id =  $coupon_validation['coupon_id'];
                        $couponused->save();
                    }
                    if($requesthistory->save()){
                        $used_packages = $subscribe_plan =false;
                        $status = 'succeeded';
                        if($request->has('request_type')){
                            $status = 'user-pending';
                        }
                        $withdrawal_to = array(
                            'balance'=>$grand_total,
                            'user'=>$sr_request->cus_info,
                            'from_id'=>$sr_request->sr_info->id,
                            'request_id'=>$sr_request->id,
                            'status'=>$status,
                            // 'category_payment'=>$category->payment_type,
                        );
                        Transaction::createWithdrawal($withdrawal_to);
                        $vendor_sent_money = $total_charges;
                        if($total_charges >0){
                            $vendor_sent_money = $total_charges - $service_tax;
                            if($vendor_sent_money<0){
                                $vendor_sent_money = 0;
                            }
                        }
                        $admin_percentage = \App\Model\EnableService::where('type','admin_percentage')->first();
                        if($admin_percentage && $vendor_sent_money>0){
                            $ad_percantage = $admin_percentage->value;
                            $admin_cut = round(($vendor_sent_money * $ad_percantage)/100,2);
                            $vendor_sent_money = $vendor_sent_money - $admin_cut;
                            $requesthistory->admin_cut = $admin_cut;
                            $requesthistory->admin_cut_percentage = $ad_percantage;
                            $requesthistory->save();
                        }
                        $deposit_to = array(
                            'balance'=>$vendor_sent_money,
                            'user'=>$sr_request->sr_info,
                            'from_id'=>$sr_request->cus_info->id,
                            'request_id'=>$sr_request->id,
                            'status'=>'vendor-pending'
                        );
                        Transaction::createDeposit($deposit_to);
                    }
                    $service_type = \App\Model\Service::where('id',$input['service_id'])->first();
                    $notification = new Notification();
                    $notification->sender_id = $user->id;
                    $notification->receiver_id = $input['consultant_id'];
                    $notification->module_id = $sr_request->id;
                    $notification->module ='request';
                    $notification->notification_type ='NEW_REQUEST';
                    $message = __('notification.new_req_text', ['user_name' => $user->name,'service_type'=>($service_type)?($service_type->type):'']);
                    $notification->message =$message;
                    $notification->save();
                    $notification->push_notification(array($input['consultant_id']),array(
                        'request_id'=>$sr_request->id,
                        'pushType'=>'NEW_REQUEST',
                        'is_second_oponion'=>$second_oponion,
                        'message'=>$message
                    ));
                    return response(['status' => "success", 'statuscode' => 200,'message' => __('New Request Created '),'data'=>[
                        'amountNotSufficient'=>false,
                        'total_charges'=>$total_charges,
                        'service_tax'=>$service_tax,
                        'tax_percantage'=>$tax_percantage,
                        'book_slot_time'=>$user_time_zone_slot,
                        'book_slot_date'=>$user_time_zone_date,
                        'is_second_oponion'=>$second_oponion,
                        'request'=>['id'=>$sr_request->id],
                        'distance_price_per_km'=>$distance_price_per_km,
                        'distance_price'=>$distance_price,
                        'distance'=>$distance,
                    ]], 200);
                }
            }
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage().' '.$ex->getLine()], 500);
        }
    }

    /**
     * @SWG\Post(
     *     path="/iedu-create-request",
     *     description="postIeduCreateRequest",
     * tags={"Customer"},
     *     security={
     *     {"Bearer": {}},
     *   },
     *  @SWG\Parameter(
     *         name="consultant_id",
     *         in="query",
     *         type="number",
     *         description=" Consultant  Id",
     *         required=true,
     *     ),
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
     *         description="date e.g 22:10",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="booking_type",
     *         in="query",
     *         type="string",
     *         description="Booking Type Course,Emsat",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="booking_type_id",
     *         in="query",
     *         type="number",
     *         description="Booking Type Id like Course ID, Emsat Id",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="schedule_type",
     *         in="query",
     *         type="string",
     *         description="schedule type instant, schedule",
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
     *         name="request_id",
     *         in="query",
     *         type="string",
     *         description="request_id for update request",
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
    public function postIeduCreateRequest(Request $request) {
        try{
            $domain_name = 'intely';
            $user = Auth::user();
            $validator = $this->validatorIeduCreateRequest($request);
            if($validator){
                return $validator;
            }
            $input = $request->all();
            $total_hours = 0;
            $request_data = null;
            if(isset($request->request_id)){
                $request_data = \App\Model\Request::where('id',$request->request_id)->first();
                $input['consultant_id'] = $request_data->to_user;
                $input['service_id'] = $request_data->service_id;
                $dateznow = new DateTime("now", new DateTimeZone('UTC'));
                $datenow = $dateznow->format('Y-m-d H:i:s');
                $next_hour_time = strtotime($datenow) + 3600;
                if(strtotime($request_data->booking_date)<=$next_hour_time){
                    return response(array('status' => "error", 'statuscode' => 400, 'message' =>__('Request could not Re-Scheduled becuase request going live into next hour')), 400);
                }
            }
            $consult = User::find($input['consultant_id']);
            if(!$consult || !$consult->hasrole('service_provider')){
                return response(array(
                    'status' => "error",
                    'statuscode' => 400,
                    'message' =>__('Consultant not found')),400);
            }
            $spservicetype_id = \App\Model\SpServiceType::where([
                'sp_id'=>$input['consultant_id']
            ])->first();

            if(!$spservicetype_id){
                return response(array(
                    'status' => "error",
                    'statuscode' => 400,
                    'message' =>__("Service not found")
                    ), 400);
            }
            $categoryservicetype_id = CategoryServiceType::where([
                'id'=>$spservicetype_id->category_service_id
            ])->first();
            $input['service_id'] = $categoryservicetype_id->service_id;
            $category = \App\Model\Category::where('id',$categoryservicetype_id->category_id)->first();
            // print_r($category);die;
            if(!$category){
                return response(array(
                    'status' => "error",
                    'statuscode' => 400,
                    'message' =>__("This Vendor have not assigned any Category")
                ), 400);
            }
            $slot_duration = \App\Model\EnableService::where('type','slot_duration')->first();
            $unit_price = \App\Model\EnableService::where('type','unit_price')->first();
            $per_minute = $spservicetype_id->price/$unit_price->value;
            $slot_minutes = $slot_duration->value;
            $add_slot_second = $slot_duration->value * 60;


            if($request_data){
                $total_charges = $request_data->requesthistory->total_charges;
                $grand_total= $g_total = $request_data->requesthistory->total_charges;
            }else{
                $total_charges = $slot_minutes * $per_minute;
                $grand_total= $g_total = $slot_minutes * $per_minute;
                if($input['booking_type']=='emsat'){
                       $emsat = \App\Model\Emsat::where('id',$input['booking_type_id'])->first();
                       if(!$emsat){
                            return response(array(
                                'status' => "error",
                                'statuscode' => 400,
                                'message' =>__("Emsat not found")
                            ), 400);
                       }
                       $sp_emsat = \App\Model\SpEmsat::where(['emsat_id'=>$emsat->id,'sp_id'=>$input['consultant_id']])->first();
                        $total_charges = $sp_emsat->price;
                        $grand_total= $g_total = $sp_emsat->price;
                }
            }
            $service_tax = 0;
            $tax_percantage = 0;
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
                $coupon_validation = self::couponCodeValidation($request->coupon_code,$user,$total_charges,$service_tax);
                if($coupon_validation['status']=='error'){
                   return response($coupon_validation,400);
                }
                if($coupon_validation['status']=='success'){
                    $coupon = true;
                    $grand_total= $g_total = $coupon_validation['grand_total'];
                    $discount = $coupon_validation['discount'];
                }
            }
            $wallet_type = 'user_wallet';
            $user_wallet = $user;
            $user_time_zone_slot = '';
            $user_time_zone_date = '';
            if($request->schedule_type=='schedule'){
                $connect_now_validation_disable = false;
                $datenow = Carbon::parse($request->date.' '.$request->time,$timezone)->setTimezone('UTC')->format('Y-m-d H:i:s');
                $datenow2 = Carbon::parse($request->date.' '.$request->time,$timezone)->addSeconds(60)->setTimezone('UTC')->format('Y-m-d H:i:s');
                $end_time_slot_utcdate = Carbon::parse($datenow)->addSeconds($add_slot_second)->setTimezone('UTC')->format('Y-m-d H:i:s');
                $end_time_slot_utcdate2 = Carbon::parse($datenow)->addSeconds($add_slot_second-1)->setTimezone('UTC')->format('Y-m-d H:i:s');
                $user_time_zone_slot = Carbon::parse($datenow)->setTimezone($timezone)->format('h:i a');
                $user_time_zone_date = Carbon::parse($datenow)->setTimezone($timezone)->format('Y-m-d');
                $exist = \App\Model\Request::where('to_user',$input['consultant_id'])
                ->whereBetween('booking_date', [$datenow, $end_time_slot_utcdate2])
                ->whereHas('requesthistory', function ($query) use($connect_now_validation_disable) {
                    $query->where('status','!=','canceled');
                    $query->where('status','!=','failed');
                    if($connect_now_validation_disable)
                        $query->where('schedule_type','!=','instant');
                })
                ->where(function($query2) use ($request_data){
                    if(isset($request_data->id))
                        $query2->where('id','!=',$request_data->id);
                })
                ->get();
                // print_r($datenow2);die;
                if($exist->count()>0){
                    return response(array('status' => "error", 'statuscode' => 400, 'message' =>__("Request could not create $request->time slot already full")), 400);
                }
            }else{
                $data = [];
                while ($data==false) {
                    $data = $this->checkSlotFullOrNot($slot_duration,$timezone,$add_slot_second,$input,$request_data);
                    $slot_duration->value = $slot_duration->value + $slot_minutes;
                }
                $user_time_zone_date = $data['user_time_zone_date'];
                $user_time_zone_slot = $data['user_time_zone_slot'];
                $end_time_slot_utcdate = $data['end_time_slot_utcdate'];
                $datenow = $data['datenow'];
            }
            $minimum_balance = \App\Model\EnableService::where('type','minimum_balance')->first();
            if($minimum_balance && $minimum_balance->value && $user_wallet->wallet->balance<($minimum_balance->value + $grand_total)){
                $amnt = ($minimum_balance->value + $grand_total) - $user_wallet->wallet->balance;
                $currency = \App\Model\EnableService::where('type','currency')->first();
                if($request_data==null)
                    return response([
                        'status' => "success",
                        'statuscode' => 200,
                        'message' => __("Request could not be created, need to add money $amnt to maintain balance $minimum_balance->value"),
                        'data'=>['amountNotSufficient'=>true,
                        'wallet_type'=>$wallet_type,'minimum_balance'=>$minimum_balance->value,'message'=>"Request could not be created, need to add money $amnt $currency->value"]
                    ], 200);
            }
            $message = 'Something went wrong';
            if($request_data){
                $request_data->booking_date = $datenow;
                $request_data->requesthistory->schedule_type = $request->schedule_type;
                $request_data->requesthistory->save();
                $request_data->save();
                $notification = new Notification();
                $notification->sender_id = $user->id;
                $notification->receiver_id = $input['consultant_id'];
                $notification->module_id = $request_data->id;
                $notification->module ='request';
                $notification->notification_type ='RESCHEDULED_REQUEST';
                $notification->message =__('notification.rescheduled_text', ['user_name' => $user->name]);
                $notification->save();
                $notification->push_notification(array($input['consultant_id']),array('pushType'=>'RESCHEDULED_REQUEST','request_id'=>$request_data->id,'message'=>__('notification.rescheduled_text', ['user_name' => $user->name])));
                return response(['status' => "success", 'statuscode' => 200,'message' => __('Request Re-Scheduled'),'data'=>['amountNotSufficient'=>false]], 200);
            }else{  
                $second_oponion = false;
                $sr_request = new \App\Model\Request();
                $sr_request->from_user = $user->id;
                $sr_request->booking_date = $datenow;
                $sr_request->request_category_type = $input['booking_type'];
                $sr_request->request_category_type_id = $input['booking_type_id'];
                $sr_request->to_user = isset($input['consultant_id']) ? $input['consultant_id'] : '';
                $sr_request->service_id =isset($input['service_id']) ? $input['service_id'] : '';;
                $sr_request->sp_service_type_id = isset($spservicetype_id)?$spservicetype_id->id:null;
                if($sr_request->save()){
                    $sr_request->booking_end_date = $end_time_slot_utcdate;
                    $sr_request->save();
                    $this->insertRequestDetail($sr_request->id,$input);
                    $requesthistory = new \App\Model\RequestHistory();
                    $requesthistory->duration = 0;
                    $requesthistory->discount = $discount;
                    $requesthistory->service_tax = $service_tax;
                    $requesthistory->tax_percantage = $tax_percantage;
                    $requesthistory->without_discount = $total_charges;
                    $requesthistory->total_charges = $grand_total;
                    $requesthistory->schedule_type = $request->schedule_type;
                    $requesthistory->status = 'pending';
                    $requesthistory->request_id = $sr_request->id;
                    if(isset($coupon_validation['status']) && $coupon_validation['status']=='success'){
                        $requesthistory->coupon_id = $coupon_validation['coupon_id'];
                        $couponused = new CouponUsed();
                        $couponused->user_id =  $user->id;
                        $couponused->coupon_id =  $coupon_validation['coupon_id'];
                        $couponused->save();
                    }
                    if($requesthistory->save()){
                    $used_packages = $subscribe_plan =false;
                    $status = 'succeeded';
                    if($request->has('request_type')){
                        $status = 'user-pending';
                    }
                    $withdrawal_to = array(
                        'balance'=>$grand_total,
                        'user'=>$sr_request->cus_info,
                        'from_id'=>$sr_request->sr_info->id,
                        'request_id'=>$sr_request->id,
                        'status'=>$status,
                        // 'category_payment'=>$category->payment_type,
                    );
                    Transaction::createWithdrawal($withdrawal_to);
                    $deposit_to = array(
                        'balance'=>$total_charges,
                        'user'=>$sr_request->sr_info,
                        'from_id'=>$sr_request->cus_info->id,
                        'request_id'=>$sr_request->id,
                        'status'=>'vendor-pending'
                    );
                    Transaction::createDeposit($deposit_to);
                    $service_type = \App\Model\Service::where('id',$input['service_id'])->first();
                    $notification = new Notification();
                    $notification->sender_id = $user->id;
                    $notification->receiver_id = $input['consultant_id'];
                    $notification->module_id = $sr_request->id;
                    $notification->module ='request';
                    $notification->notification_type ='NEW_REQUEST';
                    $message = __('notification.new_req_text', ['user_name' => $user->name,'service_type'=>($service_type)?($service_type->type):'']);
                    $notification->message =$message;
                    $notification->save();
                    $notification->push_notification(array($input['consultant_id']),array(
                        'request_id'=>$sr_request->id,
                        'pushType'=>'NEW_REQUEST',
                        'is_second_oponion'=>$second_oponion,
                        'message'=>$message
                    ));
                }
            }
        }
            return response(['status' => "success", 'statuscode' => 200,'message' => __('New Request Created '),'data'=>[
                'amountNotSufficient'=>false,
                'total_charges'=>$total_charges,
                'service_tax'=>$service_tax,
                'tax_percantage'=>$tax_percantage,
                'book_slot_time'=>$user_time_zone_slot,
                'book_slot_date'=>$user_time_zone_date,
                'is_second_oponion'=>$second_oponion,
                'request'=>['id'=>$sr_request->id],
            ]], 200);
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage().' '.$ex->getLine()], 500);
        }
    }

    private function validatorCreateRequest($request){
        $domain_name = 'intely';
        $user = Auth::user();
        $rules = ['schedule_type'=>["required" , "max:255", "in:instant,schedule,date_time"]];
        if(!isset($request->request_id)){
            $rules['consultant_id'] = 'required|exists:users,id';
            $rules['service_id'] = 'required|exists:services,id';
        }else{
            $rules['request_id'] ='required|exists:requests,id';
        }
        if(isset($request->payment_type)){
            $rules['payment_type'] = 'required|in:subscription';
        }
        if(isset($request->coupon_code)){
            $request->merge(['coupon_code' => strtoupper($request->coupon_code)]);
            $rules['coupon_code'] = 'required|exists:coupons,coupon_code';
        }
        if(isset($request->package_id)){
            $rules['package_id'] = 'required|exists:packages,id';
        }
        if(isset($request->filter_id)){
            $rules['filter_id'] = 'required|exists:filter_type_options,id'; 
        }
        if(isset($request->request_step)){
            $rules['request_step'] = 'required|in:confirm,create'; 
        }
        if(isset($request->tier_id)){
            $rules['tier_id'] = 'required|exists:tiers,id';
        }

        /* Required for Nurse Intely APP */
        if(Config::get('client_connected')&&Config::get('client_data')->domain_name==$domain_name){
            if(isset($request->request_step) && $request->request_step=='create'){
                $rules['card_id'] = 'required|exists:cards,id';
            }
            // $rules['request_step'] = 'required|in:confirm,create';
            $rules['request_type'] = 'required|in:multiple';
            $rules['dates'] = 'required';
            $rules['start_time'] = 'required|date_format:H:i';
            $rules['end_time'] = 'required|date_format:H:i';
            $rules['first_name'] = 'required|string';
            $rules['last_name'] = 'required|string';
            $rules['service_for'] = 'required|string';
            $rules['home_care_req'] = 'required|string';
            $rules['service_address'] = 'required|string';
            $rules['duties'] = 'required';
        }else if(isset($request->schedule_type) && strtolower($request->schedule_type)=='schedule'){
            $rules['date'] = 'required|date|date_format:Y-m-d';
            $rules['time'] = 'required|date_format:H:i';
        }else if(isset($request->schedule_type) && strtolower($request->schedule_type)=='date_time'){
            $rules['date'] = 'required|date|date_format:Y-m-d';
            $rules['time'] = 'required|date_format:H:i';
            $rules['end_date'] = 'required|date|date_format:Y-m-d';
            $rules['end_time'] = 'required|date_format:H:i';
        }
        $validator = Validator::make($request->all(),$rules);
        if ($validator->fails()) {
            return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                $validator->getMessageBag()->first()), 400);
        }else{
            return false;
        }
    }
    private function validatorIeduCreateRequest($request){
        $domain_name = 'intely';
        $user = Auth::user();
        $rules = ['schedule_type'=>["required" , "max:255", "in:instant,schedule"],
                'booking_type'=>["required" , "max:255", "in:course,emsat"],
                'booking_type_id'=>'required',
        ];

        if(!isset($request->request_id)){
            $rules['consultant_id'] = 'required|exists:users,id';
        }else{
            $rules['request_id'] ='required|exists:requests,id';
        }
        
        if(isset($request->coupon_code)){
            $request->merge(['coupon_code' => strtoupper($request->coupon_code)]);
            $rules['coupon_code'] = 'required|exists:coupons,coupon_code';
        }
        if(isset($request->schedule_type) && strtolower($request->schedule_type)=='schedule'){
            $rules['date'] = 'required|date|date_format:Y-m-d';
            $rules['time'] = 'required|date_format:H:i';
        }
        $validator = Validator::make($request->all(),$rules);
        if ($validator->fails()) {
            return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                $validator->getMessageBag()->first()), 400);
        }else{
            return false;
        }
    }

    private function isSlotBooked($date,$start_time,$end_time,$timezone,$consultant_id){
        $start_date_time = Carbon::parse($date.' '.$start_time,$timezone)->addHours(159)->setTimezone('UTC')->format('Y-m-d H:i:s');
        $end_date_time = Carbon::parse($date.' '.$end_time,$timezone)->addHours(159)->setTimezone('UTC')->format('Y-m-d H:i:s');
        // print_r($start_date_time);
        // print_r($end_date_time);
        $exist = \App\Model\RequestDate::where(function($query) use($start_date_time,$end_date_time){
            $query->whereBetween('start_date_time',[$start_date_time,$end_date_time])
            ->orWhereBetween('end_date_time',[$start_date_time,$end_date_time]);
        })->whereHas('requesthistory', function ($query){
            $query->where('status','!=','canceled');
            $query->where('status','!=','failed');
        })->whereHas('request', function ($query) use($consultant_id) {
            $query->where('to_user',$consultant_id);
        })->first();
        if($exist){
            return $exist;
        }
        return false;
    }

    private function addSecondOponion($sr_request,$input){
        $second_oponion_array = [
            "title"=>isset($input['title'])?$input['title']:"",
            "record_type"=>isset($input['record_type'])?$input['record_type']:"",
            "images"=>isset($input['images'])?$input['images']:"",
        ];
        $custom_info = new CustomInfo();
        $custom_info->raw_detail = json_encode($second_oponion_array);
        $custom_info->info_type = 'secondoponion';
        $custom_info->ref_table = 'requests';
        $custom_info->ref_table_id = $sr_request->id;
        $custom_info->status = 'success';
        $custom_info->save();
        return true;
    }  

    private function insertRequestSymptoms($sr_request,$input){
        $option_ids = [];
        if(isset($input['option_ids'])){
            $option_ids = explode(',', $input['option_ids']);
            \App\Model\UserMasterPreference::where(['request_id'=>$sr_request->id])->delete();
            foreach ($option_ids as $key => $option_id) {
                $MasterPreferencesOption = \App\Model\MasterPreferencesOption::where('id',$option_id)->first();
                if($MasterPreferencesOption){
                    $usermasterpreference = new \App\Model\UserMasterPreference();
                    $usermasterpreference->preference_id = $MasterPreferencesOption->preference_id;
                    $usermasterpreference->preference_option_id = $MasterPreferencesOption->id;
                    $usermasterpreference->user_id = $sr_request->from_user;
                    $usermasterpreference->request_id = $sr_request->id;
                    $usermasterpreference->save();
                }

            }
        }
        $type = isset($input['type'])?$input['type']:'image';
        if(isset($input['image'])){
            ModelImage::where([
                'module_table'=>'request_symptoms',
                'module_table_id'=>$sr_request->id
            ])->delete();
            $modelimage = new ModelImage();
            $modelimage->image_name = $input['image'];
            $modelimage->module_table = 'request_symptoms';
            $modelimage->module_table_id = $sr_request->id;
            $modelimage->type = $type;
            $modelimage->save();
        }
        if(isset($input['images'])){
            if(!is_array($input['images'])){
                $input['images'] = json_decode($input['images']);
            }
            if(is_array($input['images'])){
                ModelImage::where([
                    'module_table'=>'request_symptoms',
                    'module_table_id'=>$sr_request->id
                ])->delete();
                foreach ($input['images'] as $key => $image) {
                    $type = isset($image['type'])?$image['type']:'image';
                    if(isset($image['image'])){
                        $modelimage = new ModelImage();
                        $modelimage->image_name = $image['image'];
                        $modelimage->module_table = 'request_symptoms';
                        $modelimage->module_table_id = $sr_request->id;
                        $modelimage->type = $type;
                        $modelimage->save();
                    }
                }
            }
        }
        return true;
    }

    private function freeTextSymptomDetails($sr_request,$input){
        $symptom_details_array = [
            "symptom_details"=>isset($input['symptom_details'])?$input['symptom_details']:""
        ];
        $custom_info = new CustomInfo();
        $custom_info->raw_detail = json_encode($symptom_details_array);
        $custom_info->info_type = 'symptom_details';
        $custom_info->ref_table = 'requests';
        $custom_info->ref_table_id = $sr_request->id;
        $custom_info->status = 'success';
        $custom_info->save();
        return true;
    }

     /**
     * @SWG\Post(
     *     path="/cancel-request",
     *     description="postCancelRequest",
     * tags={"Customer"},
     *     security={
     *     {"Bearer": {}},
     *   },
     *  @SWG\Parameter(
     *         name="request_id",
     *         in="query",
     *         type="string",
     *         description="request_id ",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="cancel_reason",
     *         in="query",
     *         type="string",
     *         description="cancel_reason when canceled ",
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
    public static function postCancelRequest(Request $request) {
        try{
            $user = Auth::user();
            $customer = false;
            if($user->hasrole('customer')){
                $customer = true;
            }
            // print_r(Config::get('client_data')->domain_name);die;
            $rules = ['request_id'=>'required|exists:requests,id'];
            $validator = Validator::make($request->all(),$rules);
            if ($validator->fails()) {
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                    $validator->getMessageBag()->first()), 400);
            }
            $input = $request->all();
            $request_data = null;
            $request_data = \App\Model\Request::where('id',$request->request_id)->first();
            $input['consultant_id'] = $request_data->to_user;
            $input['customer_id'] = $request_data->from_user;
            $input['service_id'] = $request_data->service_id;
            $dateznow = new DateTime("now", new DateTimeZone('UTC'));
            $datenow = $dateznow->format('Y-m-d H:i:s');
            $next_hour_time = strtotime($datenow) + 3600;
            if($request_data->requesthistory->status!='pending' && $request_data->requesthistory->status!='accept'){
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>__('Request could not Cancel becuase request status is '.$request_data->requesthistory->status)), 400);
            }
            if(Config::get('client_connected')&&Config::get('client_data')->domain_name=='healtcaremydoctor'){
            }else{
                if(Config::get('client_connected')&&Config::get('client_data')->domain_name=='intely'){
                    $next_hour_time = strtotime($datenow) + (3600*4);
                    if(strtotime($request_data->booking_date)<=$next_hour_time){
                        return response(array('status' => "error", 'statuscode' => 400, 'message' =>__('Request could not Cancel becuase request going live into 4 hours')), 400);
                    }
                }else{
                    if(strtotime($request_data->booking_date)<=$next_hour_time){
                        return response(array('status' => "error", 'statuscode' => 400, 'message' =>__('Request could not Cancel becuase request going live into next hour')), 400);
                    }
                }
            }
            if($request_data->requesthistory->total_charges){

                if(Helper::chargeFromSP()){
                    if($customer){
                        $deposit_to = array(
                            'balance'=>$request_data->requesthistory->total_charges,
                            'user'=>$request_data->cus_info,
                            'sp'=>$request_data->sr_info,
                            'from_id'=>1,
                            'request_id'=>$request_data->id,
                            'status'=>'succeeded'
                        );
                        \App\Model\Transaction::createRefundForSP($deposit_to);
                    }
                }else{
                    // die;
                    if(Config::get('client_connected')&&Config::get('client_data')->domain_name=='intely'){
                        $hour_24time = strtotime($datenow) + (3600*24);
                        $charges_full = true;
                        if(strtotime($request_data->booking_date)<=$hour_24time){
                            $charges_full = false;
                        }
                        $per_hour = $request_data->requesthistory->total_charges/$request_data->total_hours;
                        \App\Model\Transaction::createRefundForStripe($request_data,$customer,$charges_full,$per_hour);
                    }else{
                        $deposit_to = array(
                            'balance'=>$request_data->requesthistory->total_charges,
                            'user'=>$request_data->cus_info,
                            'from_id'=>$request_data->sr_info->id,
                            'request_id'=>$request_data->id,
                            'status'=>'succeeded'
                        );
                        \App\Model\Transaction::createRefund($deposit_to);
                    }
                }
                
            }
            $message = __('notification.can_req_text', ['user_name' => $user->name]);
            if(Config::get('client_connected')&&Config::get('client_data')->domain_name=='intely' && !$customer){
                $message = 'The nurse has declined please book another nurse';
            }
            if(isset($input['cancel_reason'])){
                $request_data->requesthistory->cancel_reason = $input['cancel_reason'];
            }
            if(\Config::get('client_connected') && \Config::get('client_data')->domain_name=='care_connect_live'){
                //reorder token number
             
               $get_data = \App\Model\Request::where('to_user',Auth::user()->id)
                              // ->where('id','!=',$request->request_id)
                               ->where('booking_date',$request_data->booking_date)
                               ->whereHas('requesthistory', function ($query) {
                                   $query->whereNotIn('status',['failed','completed']);
                               })->orderby('id','asc')
                               ->where('token_number', '!=', NULL)->get();
                $get_selected_data = \App\Model\Request::where('to_user',Auth::user()->id)
                ->where('id','=',$request->request_id)
                ->where('booking_date',$request_data->booking_date)
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
                                   ->where('booking_date',$request_data->booking_date)
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
            $request_data->requesthistory->status = 'canceled';
            $request_data->requesthistory->save();
            // Request Log for status change
            $request_log = new \App\Model\RequestLog();
            $request_log->request_id = $request_data->id;
            $request_log->type = 'status_change';
            $request_log->request_status = 'canceled';
            $request_log->updated_by = $user->id;
            $request_log->role = 'service_provider';
            if($customer){
                $request_log->role = 'customer';
            }
            $request_log->save();

            $notification = new Notification();
            $notification->sender_id = $user->id;
            if($customer){
                $notification->receiver_id = $input['consultant_id'];
            }else{
                $notification->receiver_id = $input['customer_id'];
            }
            $notification->module_id = $request_data->id;
            $notification->module ='request';
            $notification->notification_type ='CANCELED_REQUEST';
            $notification->message =$message;
            $notification->save();
            $notification->push_notification(array($notification->receiver_id),array('pushType'=>'CANCELED_REQUEST','request_id'=>$request_data->id,'message'=>$message));
            return response(['status' => "success", 'statuscode' => 200,'message' => $message,'data'=>['amountNotSufficient'=>false]], 200);
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage().' '.$ex->getLine()], 500);
        }
    }

    /**
     * @SWG\Get(
     *     path="/request-check",
     *     description="check Request Created or Not",
     * tags={"Customer"},
     *     security={
     *     {"Bearer": {}},
     *   },
     *  @SWG\Parameter(
     *         name="transaction_id",
     *         in="query",
     *         type="string",
     *         description="Payment transaction_id to check request created or not",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="transaction_type",
     *         in="query",
     *         type="string",
     *         description="Payment transaction_type to check wallet,request",
     *         required=false,
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK isRequestCreated true and false",
     *     ),
     *     @SWG\Response(
     *         response=400,
     *         description="Please provide all data"
     *     )
     * )
     */
    public function checkRequestCreated(Request $request) {
        try{
            $user = Auth::user();
            $customer = false;
            if($user->hasrole('customer')){
                $customer = true;
            }
            $rules = ['transaction_id'=>'required|exists:transactions,transaction_id'];
            $validator = Validator::make($request->all(),$rules);
            if ($validator->fails()) {
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                    $validator->getMessageBag()->first()), 400);
            }
            $input = $request->all();
            $trans = Transaction::where('transaction_id',$input['transaction_id'])->first();
            if($trans){
                if(isset($request->transaction_type) && isset($request->transaction_type)=="wallet"){
                    if ($trans->status=='pending') {
                        return response([
                            'status' => "success",
                            'statuscode' => 200,
                            'message' => __('Payment Processing Please Wait'),
                            'data'=>['transactionCompleted'=>false]
                        ], 200);
                    }else if ($trans->status=='success') {
                        return response([
                            'status' => "success",
                            'statuscode' => 200,
                            'message' => __('Payment successfully done'),
                            'data'=>['transactionCompleted'=>true]
                        ], 200);
                    }else{
                        return response([
                            'status' => "success",
                            'statuscode' => 200,
                            'message' => __('Payment Processing Failed'),
                            'data'=>['transactionCompleted'=>true]
                        ], 200);
                    }
                }

                if($trans->request_id){
                    return response([
                        'status' => "success",
                        'statuscode' => 200,
                        'message' => __('Appointment has been successfully created'),
                        'data'=>['isRequestCreated'=>true,'request_id'=>$trans->request_id]
                    ], 200);
                }elseif ($trans->status=='pending') {
                    return response([
                        'status' => "success",
                        'statuscode' => 200,
                        'message' => __('Payment Processing Please Wait'),
                        'data'=>['isRequestCreated'=>false,'request_id'=>null]
                    ], 200);
                }else{
                     return response([
                        'status' => "error",
                        'statuscode' => 400,
                        'message' => __('Payment Processing Failed'),
                    ], 400);
                }
            }
            return response(['status' => "error", 'statuscode' => 400,'message' => __('Transaction ID Is Invalid')], 200);
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage().' '.$ex->getLine()], 500);
        }
    }
    /**
     * @SWG\Post(
     *     path="/confirm-request",
     *     description="postConfirmRequest",
     * tags={"Customer"},
     *     security={
     *     {"Bearer": {}},
     *   },
     *  @SWG\Parameter(
     *         name="consultant_id",
     *         in="query",
     *         type="number",
     *         description=" Consultant  Id",
     *         required=true,
     *     ),
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
     *         name="request_id",
     *         in="query",
     *         type="string",
     *         description="request_id ",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="package_id",
     *         in="query",
     *         type="string",
     *         description="package_id ",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="payment_type",
     *         in="query",
     *         type="string",
     *         description="payment_type  = subscription for MYPATH APP",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="tier_id",
     *         in="query",
     *         type="number",
     *         description="tier id",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="clinic_id",
     *         in="query",
     *         type="number",
     *         description="Clinic ID",
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
            if(!$user->hasrole('customer')){
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>'Invalid Valid user role must be role as customer'), 400);
            }

            if(isset($request->clinic_id)){
                return $this->clinicConfirmRequest($request);
            }


            if(Config::get('client_connected')&&Config::get('client_data')->domain_name=='curenik' && $request->from_home == 'true'){ 
               
                $rules = ['schedule_type'=>["required" , "max:255", "in:instant,schedule"]];
                $rules['category_id'] = 'required|exists:categories,id';
                $rules['service_id'] = 'required|exists:services,id';
               
                if(isset($request->coupon_code)){
                    $request->merge(['coupon_code' => strtoupper($request->coupon_code)]);
                    $rules['coupon_code'] = 'required|exists:coupons,coupon_code';
                }
                if(isset($request->package_id)){
                    $rules['package_id'] = 'required|exists:packages,id';
                }
                 if(isset($request->payment_type)){
                    $rules['payment_type'] = 'required|in:subscription';
                }
                if(isset($request->tier_id)){
                    $rules['tier_id'] = 'required|exists:tiers,id';
                }
                $validator = Validator::make($request->all(),$rules);
                if ($validator->fails()) {
                    return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                        $validator->getMessageBag()->first()), 400);
                }
                $categoryservicetype_id = CategoryServiceType::where([
                    'category_id'=>$request->category_id,
                    'service_id'=>$request->service_id
                ])->first();
             
                $dateznow = new DateTime("now", new DateTimeZone('UTC'));
                $timenow = $dateznow->format('H:i:s');
                $datenow = $dateznow->format('Y-m-d');

                $getslots = \App\Model\EmergencyTimeSlot::where('category_sevice_type' , $categoryservicetype_id)
                                                    ->where([
                                                        ['start_time' , '<' , $timenow],
                                                        ['end_time' , '>' , $timenow]
                                                    ])->first();
                //return $getslots;
                $total_charges = $getslots->price;
                $grand_total = $getslots->price;
                $discount = 0;
                $user_time_zone_slot =  $timenow;
                $user_time_zone_date = $datenow ;
                
              
            }
            else{
            $validator = $this->validatorConfirmRequest($request);
            if($validator){
                return $validator;
            }
            $input = $request->all();
            $request_data = null;
            if(isset($request->request_id)){
                $request_data = \App\Model\Request::where('id',$request->request_id)->first();
                $dateznow = new DateTime("now", new DateTimeZone('UTC'));
                $datenow = $dateznow->format('Y-m-d H:i:s');
                $next_hour_time = strtotime($datenow) + 3600;
                if(strtotime($request_data->booking_date)<=$next_hour_time){
                    return response(array('status' => "error", 'statuscode' => 400, 'message' =>__('Request could not Re-Scheduled becuase request going live into next hour')), 400);
                }
                $input['consultant_id'] = $request_data->to_user;
                $input['service_id'] = $request_data->service_id;
            }

            $timezone = $request->header('timezone');
            if(!$timezone){
                $timezone = 'Asia/Kolkata';
            }
 
            $consult = User::find($input['consultant_id']);
            // print_r($consult);die;
            if(!$consult || !$consult->hasrole('service_provider')){
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>__('Consultant not found')), 400);
            }
            $distance = 0;
            $category_id = $consult->getCategoryData($input['consultant_id']);
            

            $categoryservicetype_id = CategoryServiceType::where([
                'category_id'=>$category_id->id,
                'service_id'=>$input['service_id']
            ])->first();


           
            // print_r($categoryservicetype_id);die;
            $spservicetype_id = null;
            if($categoryservicetype_id){
                $spservicetype_id = \App\Model\SpServiceType::where([
                    'category_service_id'=>$categoryservicetype_id->id,
                    'sp_id'=>$input['consultant_id']
                ])->first();
            }
            if(!$spservicetype_id){
                return response(array(
                    'status' => "error",
                    'statuscode' => 400,
                    'message' =>__("Service not found into the $category_id->name category")), 400);
            }
            $slot_duration = \App\Model\EnableService::where('type','slot_duration')->first();
            $unit_price = \App\Model\EnableService::where('type','unit_price')->first();
            $per_minute = $spservicetype_id->price/$unit_price->value;
            $slot_minutes = $slot_duration->value;
            $add_slot_second = $slot_duration->value * 60;
            if($consult && $consult->slot_duration)
            {
                $slot_minutes = $consult->slot_duration;
                $add_slot_second = $consult->slot_duration * 60;
            }
            if($request_data){
                $total_charges = $request_data->requesthistory->total_charges;
            }else{
                $total_charges = $slot_minutes * $per_minute;
                if(Config::get('client_connected')&&Config::get('client_data')->domain_name=='healtcaremydoctor' && $categoryservicetype_id->price_fixed!=null){
                    $total_charges = $categoryservicetype_id->price_fixed;
                }
            }

            $total_hours = 0;
            $grand_total = $slot_minutes * $per_minute;
            if(isset($input['end_date']) && isset($input['end_time'])){
                $start  = new Carbon($input['date'].' '.$input['time']);
                $end    = new Carbon($input['end_date'].' '.$input['end_time']);
                $total_hours = $end->diffInSeconds($start)/3600;
                $total_charges = ($total_hours*60) * $per_minute;
                $grand_total = ($total_hours*60) * $per_minute;
            }

            $distance = 0;
            $distance_price = 0;
            $distance_price_per_km = 0;
            if(isset($input['lat']) && isset($input['long']) && $spservicetype_id->km_price){
                $distance_price_per_km = $spservicetype_id->km_price;
                $profile =  DB::table('profiles')
                ->select('*')
                ->where('user_id',$consult->id)->first();
                $distance = Helper::getDistance($input['lat'],$input['long'],$profile->lat,$profile->long);
                if($distance>20){
                    $distance_price = ($distance -20)*$spservicetype_id->km_price;
                }
            }
            $grand_total = $grand_total + $distance_price;

            // HealthCare MyDoctor
            if(Config::get('client_connected')&&Config::get('client_data')->domain_name=='healtcaremydoctor' && $categoryservicetype_id->price_fixed!=null){
                $grand_total = $categoryservicetype_id->price_fixed;
            }

            $discount = 0;
            $timezone = $request->header('timezone');
            if(!$timezone){
                $timezone = 'Asia/Kolkata';
            }
            if($request->schedule_type=='schedule' || $request->schedule_type=='date_time'){
                $connect_now_validation_disable = false;
                $datenow = Carbon::parse($request->date.' '.$request->time,$timezone)->setTimezone('UTC')->format('Y-m-d H:i:s');
                $datenow2 = Carbon::parse($request->date.' '.$request->time,$timezone)->addSeconds(60)->setTimezone('UTC')->format('Y-m-d H:i:s');
                if(isset($input['end_date']) && isset($input['end_time'])){
                    $end_time_slot_utcdate = Carbon::parse($input['end_date'].' '.$input['end_time'])->setTimezone('UTC')->format('Y-m-d H:i:s');
                }else{
                    $end_time_slot_utcdate = Carbon::parse($datenow)->addSeconds($add_slot_second)->setTimezone('UTC')->format('Y-m-d H:i:s');
                }
                $user_time_zone_slot = Carbon::parse($datenow)->setTimezone($timezone)->format('h:i a');
                $user_time_zone_date = Carbon::parse($datenow)->setTimezone($timezone)->format('Y-m-d');
                if(Config::get('client_connected') && (Config::get('client_data')->domain_name=='care_connect_live')){
                    $max_slot = '5';
                    $exist = \App\Model\Request::where('to_user',$input['consultant_id'])
                       ->whereBetween('booking_date', [$datenow, $end_time_slot_utcdate])
                        ->whereHas('requesthistory', function ($query) use($connect_now_validation_disable) {
                            $query->where('status','!=','canceled');
                            if($connect_now_validation_disable)
                                $query->where('schedule_type','!=','instant');

                        })
                        ->where(function($query2) use ($request_data){
                            if(isset($request_data->id))
                                $query2->where('id','!=',$request_data->id);
                        })
                     ->get();
                     if($exist->count()>=5)
                     {
                        return response(array('status' => "error", 'statuscode' => 400, 'message' =>__("Request could not create $request->time slot already full")), 400); 
                     }
                }else{
                    $exist = \App\Model\Request::where('to_user',$input['consultant_id'])
                    ->whereBetween('booking_date', [$datenow2, $end_time_slot_utcdate])
                    ->whereHas('requesthistory', function ($query) use($connect_now_validation_disable) {
                        $query->where('status','!=','canceled');
                        if($connect_now_validation_disable)
                            $query->where('schedule_type','!=','instant');

                    })
                    ->where(function($query2) use ($request_data){
                        if(isset($request_data->id))
                            $query2->where('id','!=',$request_data->id);
                    })
                    ->get();
                
                if($exist->count()>0){
                    return response(array('status' => "error", 'statuscode' => 400, 'message' =>__("Request could not create $request->time slot already full")), 400);
                }
            }
            }else{
                $data = [];
                while ($data==false) {
                    $data = $this->checkSlotFullOrNot($slot_duration,$timezone,$add_slot_second,$input,$request_data);
                    $slot_duration->value = $slot_duration->value + 30;
                }
                $user_time_zone_date = $data['user_time_zone_date'];
                $user_time_zone_slot = $data['user_time_zone_slot'];

            }
        }
       
            $tier_charges = 0;
            if(isset($input['tier_id'])){
                $tier = \App\Model\Tier::find($input['tier_id']);
                $tier_charges = $tier->price;       
                $per_minute = $tier->price/60;
                $grand_total = $grand_total + $tier_charges;
                $total_charges = $total_charges + $tier_charges;
                // $grand_total = $tier_charges;
                // $total_charges = $tier_charges;
            }

            // Health care mydoctor
            $service_tax = 0;
            $tax_percantage = 15;
            if(Config::get('client_connected') && Config::get('client_data')->domain_name=='homedoctor'){
                $tax_percantage = 15;
                $service_tax = round(($total_charges * $tax_percantage)/100,2);
                $grand_total = $service_tax + $total_charges;
            }

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
                $coupon_validation = self::couponCodeValidation($request->coupon_code,$user,$total_charges,$service_tax);
                if($coupon_validation['status']=='error'){
                   return response($coupon_validation,400);
                }
                if($coupon_validation['status']=='success'){
                    $coupon = true;
                    $grand_total = $coupon_validation['grand_total'];
                    $discount = $coupon_validation['discount'];
                }
            }
            
            // Package Subscription Plan
            if(isset($input['package_id'])){
                $subscribe = Helper::isSusbScribe([
                    'user_id'=>$user->id,
                    'package_id'=>$input['package_id']
                ]);
                if(!$subscribe){
                    $package = Package::where('id',$input['package_id'])->first();
                    if($package){
                        $grand_total = $package->price;
                        $total_charges = $package->price;
                        $discount = 0;
                    }
                }else{
                    $grand_total = 0;
                    $total_charges = 0;
                    $discount = 0;
                }
            }

            // Free Request Package
            $res = \App\Model\MonthlyRequest::checkFreeRequest($user->id);
            if($res){
                $grand_total = 0;
                $discount = $total_charges;
            }
            if(isset($input['payment_type'])){
                $grand_total = 0;
                $total_charges = 0;
                $discount = 0;
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
                    'total'=>$total_charges,
                    'service_tax'=>round($service_tax,2),
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


    public function clinicConfirmRequest(Request $request) { 
        try{
            $user = Auth::user();
            $validator = $this->validatorClinicConfirmRequest($request);
            if($validator){
                return $validator;
            }
            $input = $request->all();
            $request_data = null;
            if(isset($request->request_id)){
                if(\App\Model\Request::rescheduleRequest($request->request_id)){
                    return response(array(
                        'status' => 'error',
                        'statuscode' => 400,
                        'message' =>__('Request could not Re-Scheduled becuase request going live into next hour')), 400);
                }
            }
            $office = \App\Model\Office::find($input['clinic_id']);
            if(!$office){
                 return response(array(
                        'status' => 'error',
                        'statuscode' => 400,
                        'message' =>__('Clinic Not Found')), 400);
            }
            $input['consultant_id'] = $office->sp_id;
            $input['service_id'] = $office->service_id;

            $timezone = $request->header('timezone');
            if(!$timezone){
                $timezone = 'Asia/Kolkata';
            }
 
            $consult = User::find($input['consultant_id']);
            // print_r($consult);die;
            if(!$consult || !$consult->hasrole('service_provider')){
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>__('Consultant not found')), 400);
            }
            $distance = 0;
            $category_id = $consult->getCategoryData($input['consultant_id']);

            $slot_duration = \App\Model\EnableService::where('type','slot_duration')->first();
            $unit_price = \App\Model\EnableService::where('type','unit_price')->first();

            $per_minute = $office->price/$unit_price->value;
            $slot_minutes = $slot_duration->value;
            $add_slot_second = $slot_duration->value * 60;
            if($consult && $consult->slot_duration)
            {
                $slot_minutes = $consult->slot_duration;
                $add_slot_second = $consult->slot_duration * 60;
            }
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
                $total_hours = $end->diffInSeconds($start)/3600;
                $total_charges = ($total_hours*60) * $per_minute;
                $grand_total = ($total_hours*60) * $per_minute;
            }
            if($request->schedule_type=='schedule' || $request->schedule_type=='date_time'){
                $connect_now_validation_disable = false;
                $datenow = Carbon::parse($request->date.' '.$request->time,$timezone)->setTimezone('UTC')->format('Y-m-d H:i:s');
                $datenow2 = Carbon::parse($request->date.' '.$request->time,$timezone)->addSeconds(60)->setTimezone('UTC')->format('Y-m-d H:i:s');
                if(isset($input['end_date']) && isset($input['end_time'])){
                    $end_time_slot_utcdate = Carbon::parse($input['end_date'].' '.$input['end_time'])->setTimezone('UTC')->format('Y-m-d H:i:s');
                }else{
                    $end_time_slot_utcdate = Carbon::parse($datenow)->addSeconds($add_slot_second)->setTimezone('UTC')->format('Y-m-d H:i:s');
                }
                $user_time_zone_slot = Carbon::parse($datenow)->setTimezone($timezone)->format('h:i a');
                $user_time_zone_date = Carbon::parse($datenow)->setTimezone($timezone)->format('Y-m-d');
                $exist = \App\Model\Request::checkBookingExist($input,$datenow2,$end_time_slot_utcdate);
                if($exist->count()>0){
                    return response(array('status' =>'error',
                        'statuscode' => 400,
                        'message' =>__("Request could not create $request->time slot already full")), 400);
                }
            }else{
                $data = [];
                while ($data==false) {
                    $data = $this->checkSlotFullOrNot($slot_duration,$timezone,$add_slot_second,$input,$request_data);
                    $slot_duration->value = $slot_duration->value + 30;
                }
                $user_time_zone_date = $data['user_time_zone_date'];
                $user_time_zone_slot = $data['user_time_zone_slot'];

            }

            // Health care mydoctor
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
                $coupon_validation = self::couponCodeValidation($request->coupon_code,$user,$total_charges,$service_tax);
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
                    'tier_charges'=>0,
                    'total'=>$total_charges,
                    'service_tax'=>(int)$service_tax,
                    'tax_percantage'=>round($tax_percantage,2),
                    'discount'=>0,
                    'total_hours'=>$total_hours,
                    'grand_total'=>$grand_total,
                    'book_slot_time'=>$user_time_zone_slot,
                    'book_slot_date'=>$user_time_zone_date,
                    'coupon'=>$coupon,
                    'minimum_balance'=>$minimum_balance_value,
                    'distance_price_per_km'=>0,
                    'distance_price'=>0,
                    'distance'=>0,
                ]], 200);
            return response(array('status' => "error", 'statuscode' => 400, 'message' =>__($message)), 400);
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage().' '.$ex->getLine()], 500);
        }
    }

    /**
     * @SWG\Post(
     *     path="/iedu-confirm-request",
     *     description="postIeduConfirmRequest",
     * tags={"Customer"},
     *     security={
     *     {"Bearer": {}},
     *   },
     *  @SWG\Parameter(
     *         name="consultant_id",
     *         in="query",
     *         type="number",
     *         description=" Consultant  Id",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="booking_type",
     *         in="query",
     *         type="string",
     *         description="Booking Type Course,Emsat",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="booking_type_id",
     *         in="query",
     *         type="number",
     *         description="Booking Type Id like Course ID, Emsat Id",
     *         required=true,
     *     ),
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
     *         name="schedule_type",
     *         in="query",
     *         type="string",
     *         description="schedule type instant, schedule",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="coupon_code",
     *         in="query",
     *         type="string",
     *         description="Coupon Code",
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
    public function postIeduConfirmRequest(Request $request) { 
        try{
            $user = Auth::user();
            $rules = [
                'schedule_type'=>["required" , "max:255", "in:instant,schedule"],
                'booking_type'=>["required" , "max:255", "in:course,emsat"],
                'booking_type_id'=>'required',
            ];
            if(!isset($request->request_id)){
                $rules['consultant_id'] = 'required|exists:users,id';
            }else{
                $rules['request_id'] ='required|exists:requests,id';
            }
            if(isset($request->schedule_type) && strtolower($request->schedule_type)=='schedule'){
                $rules['date'] = 'required|date|date_format:Y-m-d';
                $rules['time'] = 'required|date_format:H:i';
            }
            if(isset($request->coupon_code)){
                $request->merge(['coupon_code' => strtoupper($request->coupon_code)]);
                $rules['coupon_code'] = 'required|exists:coupons,coupon_code';
            }
            $validator = Validator::make($request->all(),$rules);
            if ($validator->fails()) {
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                    $validator->getMessageBag()->first()), 400);
            }
            $input = $request->all();
            $request_data = null;

            $timezone = $request->header('timezone');
            if(!$timezone){
                $timezone = 'Asia/Kolkata';
            }
 
            $consult = User::find($input['consultant_id']);
            // print_r($consult);die;
            if(!$consult || !$consult->hasrole('service_provider')){
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>__('Teacher not found')), 400);
            }
            // $category_id = $consult->getCategoryData($input['consultant_id']);
            $spservicetype_id = \App\Model\SpServiceType::where([
                'sp_id'=>$input['consultant_id']
            ])->first();
            if(!$spservicetype_id){
                return response(array(
                    'status' => "error",
                    'statuscode' => 400,
                    'message' =>__("Service not found into this course or emsat")), 400);
            }
            $categoryservicetype_id = CategoryServiceType::where([
                'id'=>$spservicetype_id->id
            ])->first();
            $slot_duration = \App\Model\EnableService::where('type','slot_duration')->first();
            $unit_price = \App\Model\EnableService::where('type','unit_price')->first();
            $per_minute = $spservicetype_id->price/$unit_price->value;
            $slot_minutes = $slot_duration->value;
            $add_slot_second = $slot_duration->value * 60;

            $total_charges = $slot_minutes * $per_minute;
            $grand_total = $slot_minutes * $per_minute;

            if($input['booking_type']=='emsat'){
                   $emsat = \App\Model\Emsat::where('id',$input['booking_type_id'])->first();
                   if(!$emsat){
                        return response(array(
                            'status' => "error",
                            'statuscode' => 400,
                            'message' =>__("Emsat not found")
                        ), 400);
                   }
                   $sp_emsat = \App\Model\SpEmsat::where(['emsat_id'=>$emsat->id,'sp_id'=>$input['consultant_id']])->first();
                    $total_charges = $sp_emsat->price;
                    $grand_total= $g_total = $sp_emsat->price;
            }

            $discount = 0;
            $timezone = $request->header('timezone');
            if(!$timezone){
                $timezone = 'Asia/Kolkata';
            }
            if($request->schedule_type=='schedule'){
                $connect_now_validation_disable = false;
                    $datenow = Carbon::parse($request->date.' '.$request->time,$timezone)->setTimezone('UTC')->format('Y-m-d H:i:s');
                    $datenow2 = Carbon::parse($request->date.' '.$request->time,$timezone)->addSeconds(60)->setTimezone('UTC')->format('Y-m-d H:i:s');
                    $end_time_slot_utcdate = Carbon::parse($datenow)->addSeconds($add_slot_second)->setTimezone('UTC')->format('Y-m-d H:i:s');
                    $user_time_zone_slot = Carbon::parse($datenow)->setTimezone($timezone)->format('h:i a');
                    $user_time_zone_date = Carbon::parse($datenow)->setTimezone($timezone)->format('Y-m-d');
    
                    $exist = \App\Model\Request::where('to_user',$input['consultant_id'])
                    ->whereBetween('booking_date', [$datenow2, $end_time_slot_utcdate])
                    ->whereHas('requesthistory', function ($query) use($connect_now_validation_disable) {
                        $query->where('status','!=','canceled');
                        if($connect_now_validation_disable)
                            $query->where('schedule_type','!=','instant');

                    })
                    ->where(function($query2) use ($request_data){
                        if(isset($request_data->id))
                            $query2->where('id','!=',$request_data->id);
                    })
                    ->get();
                
                if($exist->count()>0){
                    return response(array('status' => "error", 'statuscode' => 400, 'message' =>__("Request could not create $request->time slot already full")), 400);
                }
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
                $coupon_validation = self::couponCodeValidation($request->coupon_code,$user,$total_charges,$service_tax);
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
                    'total'=>$total_charges,
                    'service_tax'=>(int)$service_tax,
                    'tax_percantage'=>round($tax_percantage,2),
                    'discount'=>$discount,
                    'grand_total'=>$grand_total,
                    'book_slot_time'=>$user_time_zone_slot,
                    'book_slot_date'=>$user_time_zone_date,
                    'coupon'=>$coupon,
                    'minimum_balance'=>$minimum_balance_value
                ]], 200);
            return response(array('status' => "error", 'statuscode' => 400, 'message' =>__($message)), 400);
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage().' '.$ex->getLine()], 500);
        }
    }

    private function validatorConfirmRequest($request){
        $rules = ['schedule_type'=>["required" , "max:255", "in:instant,schedule,date_time"]];
        if(isset($request->clinic_id)){
            $rules['clinic_id'] ='required|exists:offices,id';
        }
        if(!isset($request->request_id)){
            $rules['consultant_id'] = 'required|exists:users,id';
            $rules['service_id'] = 'required|exists:services,id';
        }else{
            $rules['request_id'] ='required|exists:requests,id';
        }
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
        if(isset($request->package_id)){
            $rules['package_id'] = 'required|exists:packages,id';
        }
         if(isset($request->payment_type)){
            $rules['payment_type'] = 'required|in:subscription';
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

    private function validatorClinicConfirmRequest($request){
        $rules = ['schedule_type'=>["required" , "max:255", "in:instant,schedule,date_time"]];
        $rules['clinic_id'] ='required|exists:offices,id';
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
        if(isset($request->package_id)){
            $rules['package_id'] = 'required|exists:packages,id';
        }
        $validator = Validator::make($request->all(),$rules);
        if ($validator->fails()) {
            return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                $validator->getMessageBag()->first()), 400);
        }else{
            return false;
        }
    }


    public function checkSlotFullOrNot($slot_duration,$timezone,$add_slot_second,$input,$request_data){
        if(Config::get('client_connected') && (Config::get('client_data')->domain_name=='mp2r')){
            $dateznow = new DateTime("now", new DateTimeZone('UTC'));
            $date = self::roundToNearestMinuteInterval($dateznow,1);
            $datenow = $date->format('Y-m-d H:i:s');
            $user_time_zone_slot = Carbon::parse($datenow)->setTimezone($timezone)->format('h:i a');
            $user_time_zone_date = Carbon::parse($datenow)->setTimezone($timezone)->format('Y-m-d');
            $end_time_slot_utcdate = Carbon::parse($datenow)->addSeconds($add_slot_second)->setTimezone('UTC')->format('Y-m-d H:i:s');
            return array(
                'user_time_zone_slot'=>$user_time_zone_slot,
                'count'=>0,
                'user_time_zone_date'=>$user_time_zone_date,
                'datenow'=>$datenow,
                'end_time_slot_utcdate'=>$end_time_slot_utcdate,
            );
        }
        $dateznow = new DateTime("now", new DateTimeZone('UTC'));
        $date = self::roundToNearestMinuteInterval($dateznow,$slot_duration->value);
        $datenow = $date->format('Y-m-d H:i:s');
        $user_time_zone_slot = Carbon::parse($datenow)->setTimezone($timezone)->format('h:i a');
        $user_time_zone_date = Carbon::parse($datenow)->setTimezone($timezone)->format('Y-m-d');
        $end_time_slot_utcdate = Carbon::parse($datenow)->addSeconds($add_slot_second)->setTimezone('UTC')->format('Y-m-d H:i:s');
      
        if(Config::get('client_connected') && (Config::get('client_data')->domain_name == 'care_connect_live')){
            $max_slot = '5';
            $exist = \App\Model\Request::where('to_user',$input['consultant_id'])
            ->whereBetween('booking_date', [$datenow, $end_time_slot_utcdate])
            ->whereHas('requesthistory', function ($query) {
                $query->where('status','!=','canceled');
            })
            ->where(function($query2) use ($request_data){
                if(isset($request_data->id))
                    $query2->where('id','!=',$request_data->id);
            })
            ->get();
            
             if($exist->count()>=5){
                return false;
            }else{
                return array('user_time_zone_slot'=>$user_time_zone_slot,'count'=>$exist->count(),'user_time_zone_date'=>$user_time_zone_date,'datenow'=>$datenow,'end_time_slot_utcdate'=>$end_time_slot_utcdate);

            }
          
        }
        else
        {
            $exist = \App\Model\Request::where('to_user',$input['consultant_id'])
            ->whereBetween('booking_date', [$datenow, $end_time_slot_utcdate])
            ->whereHas('requesthistory', function ($query) {
                $query->where('status','!=','canceled');
            })
            ->where(function($query2) use ($request_data){
                if(isset($request_data->id))
                    $query2->where('id','!=',$request_data->id);
            })
            ->get();
        
            if($exist->count()>0){
                return false;
            }else{
                return array('user_time_zone_slot'=>$user_time_zone_slot,'count'=>$exist->count(),'user_time_zone_date'=>$user_time_zone_date,'datenow'=>$datenow,'end_time_slot_utcdate'=>$end_time_slot_utcdate);

            }
        }
        
    }

    public function checkSlotFullOrNotMulti($slot_duration,$timezone,$add_slot_second,$service_providers,$request_data){
        $dateznow = new DateTime("now", new DateTimeZone('UTC'));
        $date = self::roundToNearestMinuteInterval($dateznow,$slot_duration->value);
        $datenow = $date->format('Y-m-d H:i:s');
        $user_time_zone_slot = Carbon::parse($datenow)->setTimezone($timezone)->format('h:i a');
        $user_time_zone_date = Carbon::parse($datenow)->setTimezone($timezone)->format('Y-m-d');
        $end_time_slot_utcdate = Carbon::parse($datenow)->addSeconds($add_slot_second)->setTimezone('UTC')->format('Y-m-d H:i:s');
        $exist = \App\Model\Request::whereIn('to_user',$service_providers)
        ->whereBetween('booking_date', [$datenow, $end_time_slot_utcdate])
        ->whereHas('requesthistory', function ($query) {
            $query->where('status','!=','canceled');
        })
        ->where(function($query2) use ($request_data){
            if(isset($request_data->id))
                $query2->where('id','!=',$request_data->id);
        })
        ->get();
        if($exist->count()>0){
            return false;
        }else{
            return array('user_time_zone_slot'=>$user_time_zone_slot,'count'=>$exist->count(),'user_time_zone_date'=>$user_time_zone_date,'datenow'=>$datenow);

        }
        
    }


    /**
     * Round minutes to the nearest interval of a DateTime object.
     * 
     * @param \DateTime $dateTime
     * @param int $minuteInterval
     * @return \DateTime
     */
    public static function roundToNearestMinuteInterval(\DateTime $dateTime, $minuteInterval = 30)
    {
        return $dateTime->setTime(
        $dateTime->format('H'),
            ceil($dateTime->format('i') / $minuteInterval) * $minuteInterval,
            0
        );
    }

    /**
     * Round minutes to the nearest interval of a DateTime object.
     * 
     * @param \DateTime $dateTime
     * @param int $minuteInterval
     * @return \DateTime
     */
    public static function couponCodeValidation($coupon_code,$user,$total_charges,$service_tax=0)
    {
        $total_charges = $total_charges + $service_tax;
        $dateznow = new DateTime("now", new DateTimeZone('UTC'));
        $current_date = $dateznow->format('Y-m-d');
        $coupon = Coupon::where('end_date','>=',$current_date)->where('coupon_code',strtoupper($coupon_code))->first();
        if(!$coupon){
            return array('status' => "error", 'statuscode' => 400, 'message' =>__("Applied Coupon Code was Expired"));
        }
        if($total_charges<$coupon->minimum_value){
            return array('status' => "error", 'statuscode' => 400, 'message' =>__("Coupon code not APPLIED required minimum price value is $coupon->minimum_value and your cart price is $total_charges"));
        }
        $used = CouponUsed::where(['user_id'=>$user->id,'coupon_id'=>$coupon->id])->first();
        if($used){
            return array('status' => "error", 'statuscode' => 400, 'message' =>__("Coupon Code Already Used"));
        }
        $used_count = CouponUsed::where(['coupon_id'=>$coupon->id])->get();
        if($used_count->count() >= $coupon->limit){
            return array('status' => "error", 'statuscode' => 400, 'message' =>__("Used Coupon limit full"));
        }
        $discount = 0;
        if($coupon->percent_off){
            $discount = ($total_charges * $coupon->percent_off)/100;
            if($discount<0){
                $discount = 0;
            }
        }
        if($coupon->value_off){
            $discount =  $coupon->value_off;
            if($discount<0){
                $discount = 0;
            }
        }
        if($discount>$coupon->maximum_discount_amount){
            $discount = $coupon->maximum_discount_amount;
        }
        $total_charges = $total_charges -  $discount;
        if($total_charges<0){
            $total_charges = 0;
        }
        return array('status' => "success",'discount'=>round($discount,2),'grand_total'=>round($total_charges,2),'coupon_id'=>$coupon->id);
    }

    /**
     * @SWG\Get(
     *     path="/get-user-slots",
     *     description="get User Slots",
     * tags={"Customer"},
     *     security={
     *     {"Bearer": {}},
     *   },
     *  @SWG\Parameter(
     *         name="date",
     *         in="query",
     *         type="string",
     *         description="date 2010-01-20",
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
    public static function getSlotsByDates(Request $request) {
        try{
            $user = Auth::user();
            $rules = [
                'date' => 'required|date_format:Y-m-d',
            ];
            $validator = Validator::make($request->all(),$rules);
            if ($validator->fails()) {
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                    $validator->getMessageBag()->first()), 400);
            }
            $input = $request->all();
            $timezone = $request->header('timezone');
            if(!$timezone){
                $timezone = 'Asia/Kolkata';
            }
            $weekMap = ['SU'=>0,'MO'=>1,'TU'=>2,'WE'=>3,'TH'=>4,'FR'=>5,'SA'=>6];
            $sp_slot_array = [];
            $day = strtoupper(substr(Carbon::parse($input['date'])->format('l'), 0, 2));
            $day_number = $weekMap[$day];
            $sp_slots = [['start_time'=>Carbon::parse('09:00',$timezone)->setTimezone("UTC"),'end_time'=>Carbon::parse('18:30',$timezone)->setTimezone("UTC")]];
            // print_r($sp_slots);die;
            $dateznow = new DateTime("now", new DateTimeZone($timezone));
            $datenow = $dateznow->format('Y-m-d H:i:s');
            $current_date = $dateznow->format('Y-m-d');
            $currentTime    = strtotime ($datenow);
            $slot_duration = EnableService::where('type','slot_duration')->first();
            $add_mins  = 30 * 60;
            if($slot_duration){
                $add_mins = $slot_duration->value * 60;
            }
            // print_r($add_mins);die;
            // echo " current time $currentTime \n";
            $array_of_time = array ();
            if(count($sp_slots)>0){
                foreach ($sp_slots as $key => $sp_slot) {
                    $start_time_date = Carbon::parse($sp_slot['start_time'],'UTC')->setTimezone($timezone);
                    $start_time = $start_time_date->isoFormat('h:mm a');
                    $end_time_date = Carbon::parse($sp_slot['end_time'],'UTC')->setTimezone($timezone);
                    $end_time = $end_time_date->isoFormat('h:mm a');
                    $starttime    = strtotime ($start_time); //change to strtotime
                    $endtime      = strtotime ($end_time); //change to strtotime
                    while ($starttime < $endtime) // loop between time
                    {
                       $time = date ("h:i a", $starttime);
                       $starttime_slot = date ("H:i:s", $starttime);
                       $starttime += $add_mins; // to check endtie=me
                       $endtime_slot = date ("H:i:s", $starttime);
                       $start_time_slot_utcdate = Carbon::parse($input['date'].' '.$starttime_slot,$timezone)->setTimezone('UTC');
                       $end_time_slot_utcdate = Carbon::parse($input['date'].' '.$endtime_slot,$timezone)->setTimezone('UTC');
                       // print_r($start_time_slot_utcdate);
                       // print_r($end_time_slot_utcdate);die;
                       $exist = \App\Model\Request::where('from_user',$user->id)
                       ->where('booking_date','>',$start_time_slot_utcdate)
                       ->where('booking_date','<=',$end_time_slot_utcdate)
                       ->whereHas('requesthistory', function ($query) {
                            $query->where('status','!=','canceled');
                        })
                       // ->whereBetween('booking_date', [$start_time_slot_utcdate, $end_time_slot_utcdate])
                       ->get();
                       // print_r($exist);die;
                       $available = true;
                       if($exist->count()>0){
                            $available = false;
                       }
                        // print_r($input['date']);die;
                       if($current_date==$input['date'] && $starttime>=$currentTime){
                            $time = date ("h:i a", $starttime);
                            $array_of_time[] = ["time"=>$time,"available"=>$available];
                       }else if($input['date'] > $current_date){
                            $time = date ("h:i a", $starttime);
                            $array_of_time[] = ["time"=>$time,"available"=>$available];
                       }
                    }
                    $sp_slot_array[] = array('start_time'=>$start_time,'end_time'=>$end_time);
                }
            }
            // die;
            return response(['status' => "success", 'statuscode' => 200,'message' => __('Slot List '), 'data' =>['slots'=>$sp_slot_array,'interval'=>$array_of_time,'date'=>$input['date']]], 200);
            
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage().' '.$ex->getLine()], 500);
        }
    }

    /**
     * @SWG\Post(
     *     path="/add-review",
     *     description="add review",
     * tags={"Customer"},
     *     security={
     *     {"Bearer": {}},
     *   },
     *  @SWG\Parameter(
     *         name="consultant_id",
     *         in="query",
     *         type="number",
     *         description=" Consultant  Id",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="request_id",
     *         in="query",
     *         type="number",
     *         description=" Request ID (Session ID)",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="review",
     *         in="query",
     *         type="string",
     *         description="review",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="rating",
     *         in="query",
     *         type="string",
     *         description="add rating out of 5",
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
    public static function postAddReview(Request $request) {
        try{
            $user = Auth::user();
            // if(!$user->hasrole('customer')){
            //     return response(array('status' => "error", 'statuscode' => 400, 'message' =>'Invalid Valid user role must be role as customer'), 400);
            // }
            $rules = ['consultant_id' => 'required',
                        'request_id'=>'required'  ];
            if($request->rating){
                $rules['rating'] = "required|max:5";
            }
            $validator = Validator::make($request->all(),$rules);
            if ($validator->fails()) {
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                    $validator->getMessageBag()->first()), 400);
            }
            $message = 'Something went wrong';
            $consultant = User::find($request->consultant_id);
            if($consultant){
                $feeback = new \App\Model\Feedback();
                $feeback->from_user = $user->id;
                $feeback->consultant_id = $request->consultant_id;
                $feeback->request_id = $request->request_id;
                $feeback->rating = (isset($request->rating)?$request->rating:0.5);
                $feeback->comment = isset($request->review)?$request->review:null;
                if($feeback->save()){
                    \App\Model\Feedback::updateReview($request->consultant_id);
                }
                return response(['status' => "success", 'statuscode' => 200,'message' => __('Review added '),'data'=>[]], 200);
            }else{
                $message = 'Doctor not Found';
            }
            return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                    $validator->getMessageBag()->first()), 400);
            
            return response(array('status' => "error", 'statuscode' => 400, 'message' =>__($message)), 400);
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage()], 500);
        }
    }

    /**
     * @SWG\Get(
     *     path="/requests-cs",
     *     description="Get All Requests By Customer",
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
     *         name="service_type",
     *         in="query",
     *         type="string",
     *         description="service_type chat,call,all",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="second_oponion",
     *         in="query",
     *         type="string",
     *         description="second_oponion true or false",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="service_id",
     *         in="query",
     *         type="string",
     *         description="service_id id for call,chat etc",
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
    public static function getRequestByCustomer(Request $request) {
        try{
            $user = Auth::user();
            $from_date = null;
            $end_date = null;
            if(isset($request->date)){
                $rules = ['date' => 'required|date|date_format:Y-m-d'];
                $validator = Validator::make($request->all(),$rules);
                if ($validator->fails()) {
                    return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                        $validator->getMessageBag()->first()), 400);
                }
            }
            $timezone = $request->header('timezone');
            if(!$timezone){
                $timezone = 'Asia/Kolkata';
            }
            $requests = [];
            $service_type = isset($request->service_type)?$request->service_type:'all';
            $service_id = isset($request->service_id)?$request->service_id:null;
            $per_page = (isset($request->per_page)?$request->per_page:10);
            if(Config::get('client_connected') && (Config::get('client_data')->domain_name=='care_connect_live')){
                $requests = \App\Model\Request::select('id','service_id','from_user','to_user','booking_date','created_at','booking_date as bookingDateUTC','request_type','token_number')
                ->whereHas('servicetype', function($query) use ($service_type,$service_id){
                    if($service_type!=='all')
                        return $query->where('type', $service_type);
                    if($service_id)
                        return $query->where('id', $service_id);
                })
                ->when('request_type', function($query) use ($request){
                    if(isset($request->second_oponion) && ($request->second_oponion===true || $request->second_oponion==='true')){
                        return $query->where('request_type','second_oponion');
                    }else{
                        return $query->where('request_type','!=','second_oponion');
                    }
                })
                ->when('booking_date', function($query) use ($request){
                    if(isset($request->date)){
                        $from_date = $request->date.' 00:00:00';
                        $end_date = $request->date.' 23:59:59';
                        $fromUTC = Carbon::parse($from_date, $timezone)->setTimezone('UTC');
                        $toUTC = Carbon::parse($end_date, $timezone)->setTimezone('UTC');
                        return $query->whereBetween('booking_date', [$fromUTC, $toUTC]);
                    }
                })
                ->where('from_user',$user->id)->orderBy('id', 'desc')->cursorPaginate($per_page);
            }
            else
            {
                $requests = \App\Model\Request::select('id','service_id','from_user','to_user','booking_date','created_at','booking_date as bookingDateUTC','request_type','booking_end_date')
                ->whereHas('servicetype', function($query) use ($service_type,$service_id){
                    if($service_type!=='all')
                        return $query->where('type', $service_type);
                    if($service_id)
                        return $query->where('id', $service_id);
                })
                ->when('request_type', function($query) use ($request){
                    if(isset($request->second_oponion) && ($request->second_oponion===true || $request->second_oponion==='true')){
                        return $query->where('request_type','second_oponion');
                    }else{
                        return $query->where('request_type','!=','second_oponion');
                    }
                })
                ->when('booking_date', function($query) use ($request){
                    if(isset($request->date)){
                        $from_date = $request->date.' 00:00:00';
                        $end_date = $request->date.' 23:59:59';
                        $fromUTC = Carbon::parse($from_date, $timezone)->setTimezone('UTC');
                        $toUTC = Carbon::parse($end_date, $timezone)->setTimezone('UTC');
                        return $query->whereBetween('booking_date', [$fromUTC, $toUTC]);
                    }
                })
                ->where('from_user',$user->id)->orderBy('id', 'desc')->cursorPaginate($per_page);
            }
            foreach ($requests as $key => $request_status) {
                $request_status->is_second_oponion = false;
                if($request_status->request_type=='second_oponion'){
                    $request_status->is_second_oponion = true;
                    $request_status->second_oponion = $request_status->getSecondOponion($request_status);
                }
                $request_status->is_prescription = false;    
                if($request_status->prescription){
                    $request_status->is_prescription = true;
                    unset($request_status->prescription);    
                }
                $date = Carbon::parse($request_status->booking_date,'UTC')->setTimezone($timezone);
                $request_status->booking_date = $date->isoFormat('D MMMM YYYY, h:mm:ss a');
                $request_status->time = $date->isoFormat('h:mm a');
                $dateznow = new DateTime("now", new DateTimeZone('UTC'));
                $datenow = $dateznow->format('Y-m-d H:i:s');
                if(\Config::get('client_connected') && (\Config::get('client_data')->domain_name=='healtcaremydoctor')){
                    $next_hour_time = strtotime($datenow);
                }else{
                    $next_hour_time = strtotime($datenow) + 3600;
                }
                $request_history = $request_status->requesthistory;
                if($request_history){
                    $request_status->duration = $request_history->duration;
                    $request_status->price = $request_history->total_charges;
                    $request_status->status = $request_history->status;
                    $request_status->schedule_type = $request_history->schedule_type;
                }
                $request_status->extra_detail = RequestData::getExtraRequestInfo($request_status->id,$timezone);
                if(strtotime($request_status->bookingDateUTC)>=$next_hour_time && $request_status->status=='pending'){
                    $request_status->canReschedule = true;
                    $request_status->canCancel = true;
                }else{
                    $request_status->canReschedule = false;
                    $request_status->canCancel = false;
                }
                $request_status->service_type = $request_status->servicetype->type;
                $request_status->from_user = User::select('name','email','id','profile_image','phone','country_code')->with(['profile'])->where('id',$request_status->from_user)->first();
                $request_status->to_user = User::select('name','email','id','profile_image','phone','country_code')->with(['profile'])->with('profile')->where('id',$request_status->to_user)->first();
                $request_status->to_user->categoryData = $request_status->to_user->getCategoryData($request_status->to_user->id);
                unset($request_status->requesthistory);
                unset($request_status->servicetype);
                $request_status = RequestData::getMoreData($request_status);
            }
            $after = null;
            if($requests->meta['next']){
                $after = $requests->meta['next']->target;
            }
            $before = null;
            if($requests->meta['previous']){
                $before = $requests->meta['previous']->target;
            }
            $per_page = $requests->perPage();
            return response(['status' => "success", 'statuscode' => 200,
                                'message' => __('All Request from Customer'), 'data' =>['requests'=>$requests->items(),'after'=>$after,'before'=>$before,'per_page'=>$per_page]], 200);
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage()], 500);
        }
    }

}
