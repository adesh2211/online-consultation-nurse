<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


use App\User;
use Illuminate\Support\Facades\Auth;
use Validator,Hash,Mail,DB;
use DateTime,DateTimeZone;
use Redirect,Response,File,Image;
use Illuminate\Support\Facades\URL;
use App\Model\Role,App\Model\FilterType;
use App\Model\Wallet,App\Model\ServiceProviderFilterOption;
use App\Model\Feedback,App\Model\Banner,App\Model\Cluster;
use App\Model\Profile,App\Model\Payment,App\Model\Service,App\Model\Coupon;
use App\Model\SocialAccount,App\Model\Subscription;
use Socialite,Exception;
use Intervention\Image\ImageManager;
use Carbon\Carbon;
use App\Notification;
use App\Model\PackagePlan,App\Model\UserPackagePlan;
use App\Model\MonthlyRequest;
use App\Model\Transaction;
use App\Model\Topic;
use App\Model\SubscribeTopic;
class CouponController extends Controller
{

	public function __construct() {
        $this->middleware('auth')->except(['getCoupons','getSubscriptionList']);
    }
	/**
     * @SWG\Get(
     *     path="/coupons",
     *     description="Get Coupon",
     * tags={"Coupon"},
     *  @SWG\Parameter(
     *         name="category_id",
     *         in="query",
     *         type="string",
     *         description="category_id",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="service_id",
     *         in="query",
     *         type="string",
     *         description="service_id",
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

     public static function getCoupons(Request $request) {
        try{
            $per_page = (isset($request->per_page)?$request->per_page:10);
            $parent_id = (isset($request->parent_id)?$request->parent_id:NULL);
            $dateznow = new DateTime("now", new DateTimeZone('UTC'));
            $datenow = $dateznow->format('Y-m-d');
            $coupons = Coupon::select('id','category_id','service_id','percent_off','value_off','minimum_value','start_date','end_date','limit','coupon_code','maximum_discount_amount')->orderBy('id','DESC')
                ->where(function($q) use($datenow) {
                  $q->where('end_date', '>=', $datenow)
                    ->orWhere('start_date', '>=', $datenow);
                })->where(function($query2) use ($request){
                    if(isset($request->category_id))
                        $query2->where('category_id','=',$request->category_id);
                    if(isset($request->service_id))
                        $query2->where('service_id','=',$request->service_id);
              })->cursorPaginate($per_page);
	         foreach ($coupons as $key => $coupon) {
                $used = Coupon::usedCoupon($coupon->id);
                $coupon->limit = $coupon->limit -  $used;
	            $coupon->discount_type = '';
	            $coupon->discount_value = '';
	            if($coupon->percent_off){
	                $coupon->discount_value = $coupon->percent_off;
	                $coupon->discount_type = 'percentage';
	            }else{
	                $coupon->discount_value = $coupon->value_off;
	                $coupon->discount_type = 'currency';
	            }
                if($coupon->service_id){
                    $coupon->service;
                    $coupon->service->name = $coupon->service->type;
                    unset($coupon->service->type);
                }
                if($coupon->category_id){
                    $coupon->category;
                    if($coupon->category->subcategory->count() > 0){
                        $coupon->category->is_subcategory = true;
                    }else{
                        $coupon->category->is_subcategory = false;
                    }
                }
	            unset($coupon->percent_off);
	            unset($coupon->value_off);
	        }
            $after = null;
            if($coupons->meta['next']){
                $after = $coupons->meta['next']->target;
            }
            $before = null;
            if($coupons->meta['previous']){
                $before = $coupons->meta['previous']->target;
            }
            $per_page = $coupons->perPage();
            return response(['status' => "success", 'statuscode' => 200,
                                'message' => __('Coupon Listing'), 'data' =>['coupons'=>$coupons->items(),'after'=>$after,'before'=>$before,'per_page'=>$per_page]], 200);
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage()], 500);
        }
    }

    /**
     * @SWG\Get(
     *     path="/subscriptions",
     *     description="Get Subscription",
     * tags={"Subscriptions"},
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

    public function getSubscriptionList(Request $request){
        try{
            $per_page = (isset($request->per_page)?$request->per_page:10);
            $packageplans =  PackagePlan::select('id','title','price','total_session as total_requests','type','image_icon as image')->orderBy('id','DESC')->cursorPaginate($per_page);
            $expired_on = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
            foreach ($packageplans as $key => $package) {
                $package->subscribe = false;
                if(Auth::guard('api')->check()){
                    $userpackages = UserPackagePlan::where([
                        'package_id'=>$package->id,
                        'user_id'=> Auth::guard('api')->user()->id])
                    ->where('expired_on','>',$expired_on)
                    ->orderBy('id','DESC')->first();
                    if($userpackages)
                        $package->subscribe = true;
                }
            }
            $subscribe = false;
            if(Auth::guard('api')->check()){
                $userpackages = UserPackagePlan::where([
                    'user_id'=> Auth::guard('api')->user()->id])
                ->where('expired_on','>',$expired_on)
                ->orderBy('id','DESC')->first();
                if($userpackages)
                    $subscribe = true;
            }
            $after = null;
            if($packageplans->meta['next']){
                $after = $packageplans->meta['next']->target;
            }
            $before = null;
            if($packageplans->meta['previous']){
                $before = $packageplans->meta['previous']->target;
            }
            $per_page = $packageplans->perPage();
            return response(['status' => "success", 'statuscode' => 200,
                                'message' => __('Subscription Listing'),
                                'data' =>[
                                    'plans'=>$packageplans->items(),
                                    'after'=>$after,
                                    'before'=>$before,
                                    'per_page'=>$per_page,
                                    'active_plan'=>$subscribe
                                ]], 200);
            }catch(Exception $ex){
                return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage().' '.$ex->getLine()], 500);
            }
    }

    /**
     * @SWG\Get(
     *     path="/subscription-detail",
     *     description="Get Subscription Detail",
     * tags={"Subscriptions"},  
     *  @SWG\Parameter(
     *         name="plan_id",
     *         in="query",
     *         type="number",
     *         description="plan_id for fetch Subscription Detail",
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

     public  function getSubscriptionDetail(Request $request) {
        try{
            $user = Auth::user();
            $per_page = (isset($request->per_page)?$request->per_page:10);
            $rules = ['plan_id' => 'required|exists:package_plans,id'];
            $validator = Validator::make($request->all(),$rules);
            if ($validator->fails()) {
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                    $validator->getMessageBag()->first()), 400);
            }
            $package = PackagePlan::select('id','title','price','total_session as total_requests','type','image_icon as image')->where('id',$request->plan_id)->first();
             if($user){
                $expired_on = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
                $userpackages = UserPackagePlan::where(['package_id'=>$package->id,'user_id'=>$user->id])->where('expired_on','>',$expired_on)->orderBy('id','DESC')->first();
                $package->subscribe = false;
                if($userpackages){
                    $package->available_requests = 0;
                    $res = MonthlyRequest::checkFreeRequest($user->id);
                    if($res){
                        $package->available_requests = $res->available_requests;
                    }
                    $package->subscribe = true;
                    if($package->available_requests==0){
                        $package->subscribe = false;
                    }
                    $package->expired_on_plan = $userpackages->expired_on;
                }
            }
            return response(['status' => "success", 'statuscode' => 200,
                                'message' => __('packages detail'), 'data' =>['detail'=>$package]], 200);
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage()], 500);
        }
    }

    /**
     * @SWG\Post(
     *     path="/subscription-pack",
     *     description="Post Subscribe Package Or Subscription",
     * tags={"Subscriptions"},
     *  @SWG\Parameter(
     *         name="plan_id",
     *         in="query",
     *         type="string",
     *         description=" Package or Subscribe Id",
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

     public  function postSubscriptionPlan(Request $request) {
        try{
            $user = Auth::user();
            $rules = ['plan_id' => 'required|exists:package_plans,id'];
            $validator = Validator::make($request->all(),$rules);
            if ($validator->fails()) {
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                    $validator->getMessageBag()->first()), 400);
            }
            $package = PackagePlan::where('id',$request->plan_id)->first();
            if($user->wallet->balance < $package->price){
                return response(['status' => "success", 'statuscode' => 200,'message' => __('insufficient balance'),'data'=>['amountNotSufficient'=>true]], 200);
            }
            $userpackage  = new UserPackagePlan();
            $userpackage->user_id = $user->id;
            $userpackage->package_id = $package->id;
            $userpackage->available_requests = $package->total_session;
            if($package->type=='monthly'){
                $userpackage->expired_on = \Carbon\Carbon::now()->endOfMonth()->format('Y-m-d H:i:s');
            }elseif ($package->type=='yearly') {
                $userpackage->expired_on = \Carbon\Carbon::now()->addMonth(11)->endOfMonth()->format('Y-m-d H:i:s');
            }elseif ($package->type=='half_yearly') {
                $userpackage->expired_on = \Carbon\Carbon::now()->addMonth(5)->endOfMonth()->format('Y-m-d H:i:s');
            }
            $userpackage->type = $package->type;
            if($userpackage->save()){
                $p_detail = ['package'=>$package,'userpackage'=>$userpackage];
                MonthlyRequest::AddMonthlyRequest($p_detail);
                $user->wallet->decrement('balance',$package->price);
                $transaction = Transaction::create(array(
                        'amount'=>$package->price,
                        'transaction_type'=>'add_package',
                        'status'=>'success',
                        'wallet_id'=>$user->wallet->id,
                        'closing_balance'=>$user->wallet->balance,
                ));
                if($transaction){
                    $transaction->module_table = 'user_package_plans';
                    $transaction->module_id = $userpackage->id;
                    $transaction->save();
                    $payment = Payment::create(array('from'=>1,'to'=>$user->id,'transaction_id'=>$transaction->id));
                }
            }

            return response(array(
                'status' => "success",
                'statuscode' => 200,
                'data'=>(Object)[],
                'message' =>__('Subscribe Successfully')), 200);
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage()], 500);
        }
    }

    /**
     * @SWG\Post(
     *     path="/subscription-topic",
     *     description="Purchase Topic",
     * tags={"Subjects"},
     *  @SWG\Parameter(
     *         name="topic_id",
     *         in="query",
     *         type="string",
     *         description=" Topic Id",
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

     public  function postSubscriptionTopic(Request $request) {
        try{
            $user = Auth::user();
            $rules = ['topic_id' => 'required|exists:topics,id'];
            $validator = Validator::make($request->all(),$rules);
            if ($validator->fails()) {
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                    $validator->getMessageBag()->first()), 400);
            }
            $package = Topic::where('id',$request->topic_id)->first();
            if($user->wallet->balance < $package->price){
                return response(['status' => "success", 'statuscode' => 200,'message' => __('insufficient balance'),'data'=>['amountNotSufficient'=>true]], 200);
            }
            $userpackage  = new SubscribeTopic();
            $userpackage->user_id = $user->id;
            $userpackage->topic_id = $package->id;
            $userpackage->expired_on = \Carbon\Carbon::now()->addMonth(1)->format('Y-m-d H:i:s');
            if($userpackage->save()){
                $user->wallet->decrement('balance',$package->price);
                $transaction = Transaction::create(array(
                        'amount'=>$package->price,
                        'transaction_type'=>'subscribe_topics',
                        'status'=>'success',
                        'wallet_id'=>$user->wallet->id,
                        'closing_balance'=>$user->wallet->balance,
                ));
                if($transaction){
                    $transaction->module_table = 'subscribe_topics';
                    $transaction->module_id = $package->id;
                    $transaction->save();
                    $payment = Payment::create(array('from'=>$package->created_by,'to'=>$user->id,'transaction_id'=>$transaction->id));
                }

                /* For Service Provider */
                $create_by = User::where('id',$package->created_by)->first();
                $create_by->wallet->increment('balance',$package->price);
                $transaction = Transaction::create(array(
                        'amount'=>$package->price,
                        'transaction_type'=>'deposit',
                        'status'=>'success',
                        'wallet_id'=>$create_by->wallet->id,
                        'closing_balance'=>$create_by->wallet->balance,
                ));
                if($transaction){
                    $transaction->module_table = 'subscribe_topics';
                    $transaction->module_id = $package->id;
                    $transaction->save();
                    $payment = Payment::create(array('from'=>$user->id,'to'=>$package->created_by,'transaction_id'=>$transaction->id));
                }
            }

            return response(array(
                'status' => "success",
                'statuscode' => 200,
                'data'=>(Object)[],
                'message' =>__('Subscribe Successfully')), 200);
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage()], 500);
        }
    }

    /**
     * @SWG\Get(
     *     path="/tiers",
     *     description="Get All Tiers--Nurselynx",
     * tags={"Tiers"},
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

     public  function getTiers() {
        try{
            $tiers = \App\Model\Tier::select('id','title','price')->with('tier_options')->orderBy('order_by','ASC')->get();
            return response(array(
                'status' => "success",
                'statuscode' => 200,
                'data'=>['tiers'=>$tiers],
                'message' =>__('Tiers Listing')), 200);
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage()], 500);
        }
    }
}
