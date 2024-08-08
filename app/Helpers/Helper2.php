<?php

namespace App\Helpers;

use Illuminate\Support\Str;
use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use FCM;
use Illuminate\Encryption\Encrypter;
use AWS;
use Aws\S3\S3Client;
use GuzzleHttp\Client;
use Cartalyst\Stripe\Stripe;
use Image,File,Storage;
use Config,Exception;
use App\Model\Transaction,App\Model\Payment;
use App\Model\GodPanel\FeatureType;
use App\Model\UserInsurance;
use App\Model\Category,App\Model\SpServiceType;
use App\Model\CategoryServiceType;
use App\Model\EnableService;
use App\Model\Card,App\Model\BookingOrderRequest;
use App\Model\Coupon,App\Model\BookingOrder;
use App\Model\CouponUsed;
use App\Notification;
use Carbon\Carbon;
use DB,App\User,App\Model\Package,App\Model\UserPackage,App\Model\SubscribePlan;
use App\Model\Image as ModelImage;
use Illuminate\Http\Request;
use DateTime,DateTimeZone;
use Pushok\AuthProvider;
use Pushok\Client as PushClient;
use Pushok\Notification as PushNoti;
use Pushok\Payload;
use Pushok\Payload\Alert;

use App\Jobs\TempOrderPush;
class Helper2{

	public static function createRequest($booking_request,$sp){
        $input = json_decode($booking_request->order_data,true);
        $user = \App\User::find($booking_request->bookingorder->user_id);
		$message = 'Something went wrong';  
        $second_oponion = false;
        $timezone = $input['timezone'];
        $datenow = Carbon::parse($input['date'].' '.$input['time'],$timezone)->setTimezone('UTC')->format('Y-m-d H:i:s');
        $end_time_slot_utcdate = null;
        if(isset($input['end_date']) && isset($input['end_time'])){
            $end_time_slot_utcdate = Carbon::parse($input['end_date'].' '.$input['end_time'])->setTimezone('UTC')->format('Y-m-d H:i:s');
        }
        $category_id = Category::find($input['category_id']);
        $categoryservicetype_id = CategoryServiceType::where([
            'category_id'=>$category_id->id,
            'service_id'=>$input['service_id']
        ])->first();

        $spservicetype_id = SpServiceType::where('category_service_id',$categoryservicetype_id->id)->where('sp_id',$sp->id)->first();

        $sr_request = new \App\Model\Request();
        $sr_request->from_user = $booking_request->bookingorder->user_id;
        $sr_request->booking_date = $datenow;
        $sr_request->to_user = $sp->id;
        $sr_request->service_id = $booking_request->bookingorder->service_id;
        $sr_request->sp_service_type_id = isset($spservicetype_id)?$spservicetype_id->id:null;
        $sr_request->total_hours = $input['total_hours'];
        if($sr_request->save()){
            $sr_request->booking_end_date = $end_time_slot_utcdate;
            $sr_request->save();
            /* Requests Dates Saving... */
            $requesthistory = new \App\Model\RequestHistory();
            $requesthistory->discount = $input['discount'];
            $requesthistory->service_tax = $input['service_tax'];
            $requesthistory->tax_percantage = $input['tax_percantage'];
            $requesthistory->without_discount = $input['total_charges'];
            $requesthistory->total_charges = $input['grand_total'];
            $requesthistory->schedule_type = $input['schedule_type'];
            $requesthistory->status = 'pending';
            $requesthistory->request_id = $sr_request->id;

            if(isset($input['coupon_validation']['status']) && $input['coupon_validation']['status']=='success'){
                $requesthistory->coupon_id = $input['coupon_validation']['coupon_id'];
                $couponused = new CouponUsed();
                $couponused->user_id =  $booking_request->bookingorder->user_id;
                $couponused->coupon_id =  $input['coupon_validation']['coupon_id'];
                $couponused->save();
            }
            if($requesthistory->save()){

                self::insertRequestDetail($sr_request->id,$input,$user);
	            $used_packages = $subscribe_plan =false;
	            /* If Tier */
	            if(isset($input['tier_id'])){
	                $requesthistory->module_table = 'tier';
	                $requesthistory->module_id = $input['tier_id'];
	                $requesthistory->save();
	            }
	            $status = 'succeeded';
	            $withdrawal_to = array(
	                'balance'=>$input['grand_total'],
	                'user'=>$sr_request->cus_info,
	                'from_id'=>$sr_request->sr_info->id,
	                'request_id'=>$sr_request->id,
	                'status'=>$status
	            );
	            Transaction::createWithdrawalUberLike($withdrawal_to);
	            $vendor_sent_money = $input['total_charges'];
	            if($input['total_charges'] >0){
	                $vendor_sent_money = $input['total_charges'] - $input['service_tax'];
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
            $notification->receiver_id = $sp->id;
            $notification->module_id = $sr_request->id;
            $notification->module ='request';
            $notification->notification_type ='NEW_REQUEST';
            $message = __('notification.new_req_text', ['user_name' => $user->name,'service_type'=>($service_type)?($service_type->type):'']);
            $notification->message =$message;
            $notification->save();
            // $notification->push_notification(array($sp->id),array(
            //     'request_id'=>$sr_request->id,
            //     'pushType'=>'NEW_REQUEST',
            //     'is_second_oponion'=>$second_oponion,
            //     'message'=>$message
            // ));
            return ['status' => "success", 'statuscode' => 200,'message' => __('New Request Created '),'data'=>[
                'amountNotSufficient'=>false,
                'total_charges'=>$input['total_charges'],
                'service_tax'=>$input['service_tax'],
                'tax_percantage'=>$input['tax_percantage'],
                'is_second_oponion'=>$second_oponion,
                'request'=>['id'=>$sr_request->id],
                'sr_request'=>$sr_request
            ]];
        }
	}

    public static function  insertRequestDetail($request_id,$input,$user){
        $requestdetail= \App\Model\RequestDetail::firstOrCreate(['request_id'=>$request_id]);
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
        return array('status' => "success",'discount'=>(int)$discount,'grand_total'=>(int)$total_charges,'coupon_id'=>$coupon->id);
    }

    public static function createOrderTempRequest($input){
    	$order = BookingOrder::create([
    		'user_id'=>$input['user']->id,
    		'service_id'=>$input['service_id'],
    		'category_id'=>$input['category_id'],
    		'module_type'=>'TEMPORDER',
    		'order_data'=>json_encode($input),
    	]);
    	foreach ($input['sp_ids'] as $key => $sp_id) {
	    	BookingOrderRequest::create([
	    		'booking_order_id'=>$order->id,
	    		'status'=>'pending',
	    		'sp_id'=>$sp_id,
	    		'order_data'=>json_encode($input),
	    	]);
    	}

    	$service_type = \App\Model\Service::where('id',$input['service_id'])->first();

        $transaction = Transaction::create(array(
            'amount'=>$input['grand_total'],
            'transaction_type'=>'withdrawal',
            'status'=>'success',
            'wallet_id'=>$input['user']->wallet->id,
            'closing_balance'=>$input['user']->wallet->balance,
            'module_table'=>'booking_orders',
            'module_id'=>$order->id,
        ));

        $input['user']->wallet->decrement('balance',$input['grand_total']);

        $transaction->closing_balance = $input['user']->wallet->balance;
            $transaction->save();

        $admin = \App\User::whereHas('roles',function($query){
                    return $query->where('name','admin');
                })->first();

        $payment = \App\Model\Payment::create(array(
                'from'=>$admin->id,
                'to'=>$input['user']->id,
                'transaction_id'=>$transaction->id));


        $notification = new Notification();
        $notification->sender_id = $admin->id;
        $notification->receiver_id = $input['user']->id;
        $notification->module_id = $payment->id;
        $notification->module ='payment';
        $notification->notification_type ='BALANCE_DEDUCTED';
        $notification->message =__("$transaction->amount balance deducted for Booking Request");
        $notification->save();
        $notification->push_notification(array($input['user']->id),array(
            'pushType'=>'BALANCE_DEDUCTED',
            'message'=>__("$transaction->amount balance deducted for Booking Request")
        ));

    	
        $notification = new Notification();
        $notification->push_notification($input['sp_ids'],array(
        	'pushType'=>'BOOKING_REQUEST',
        	'request_id'=>$order->id,
        	'message'=>__('notification.new_booking_req_text', ['user_name' => $input['user']->name,'service_type'=>($service_type)?($service_type->type):''])));

        $time = new DateTime();
        $time->modify("+60 second");
        $time->format('Y-m-d H:i:s');
        $job = (new TempOrderPush(['order_id'=>$order->id]))->delay($time);
        dispatch($job);

        return response([
        	'status' => 'success',
        	'statuscode' => 200,
        	'message' => __('New Request Created Please wait to accept any nurse'),
        	'data'=>[
                        'amountNotSufficient'=>false,
                        'total_charges'=>$input['total_charges'],
                        'request_id'=>['id'=>$order->id]
             ]], 200);

    }

}