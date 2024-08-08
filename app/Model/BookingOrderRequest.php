<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\Helper;
class BookingOrderRequest extends Model
{
    protected $fillable = ['booking_order_id', 'sp_id','status','order_data'];

    public function bookingorder()
    {
        return $this->hasOne('App\Model\BookingOrder','id','booking_order_id');
    }

    /**
     * Get the Service Type From Service Model.
     */
    public function cus_info()
    {
        return $this->hasOne('App\User','id','from_user');
    }

    public function sr_info()
    {
        return $this->hasOne('App\User','id','sp_id');
    }

    public static function getDetailOrder($order_detail){
    	$order_data = json_decode($order_detail->order_data);
    	$order_detail->price = $order_data->grand_total;
        $order_detail->booking_date = $order_data->date.' '.$order_data->time;
    	$order_detail->booking_end_date = $order_data->end_date.' '.$order_data->end_time;

        $date = \Carbon\Carbon::parse($order_detail->booking_date,$order_data->timezone)->timeZone("UTC");
        $date2 = \Carbon\Carbon::parse($order_detail->booking_end_date,$order_data->timezone)->timeZone("UTC");

        $current_date = \Carbon\Carbon::now();
        $created_at = \Carbon\Carbon::parse($order_detail->created_at);

        $remain_time = 0;
        $seconds = $current_date->diffInSeconds($created_at);
        if($seconds<45){
            $remain_time = 45 - $seconds;
        }
        $order_detail->remain_second = $remain_time;
        $order_detail->bookingDateUTC = $date->format('Y-m-d H:i:s');
        $order_detail->booking_date = $date->format('Y-m-d H:i:s');
        $order_detail->booking_end_date = $date2->format('Y-m-d H:i:s');
    	$order_detail->from_user = \App\User::select('id','name','profile_image')->find($order_detail->bookingorder->user_id);
    	$order_detail->service_type = \App\Model\Service::where('id',$order_detail->bookingorder->service_id)->pluck('type')->first();
        $order_detail->main_service_type = \App\Model\Service::where('id',$order_detail->bookingorder->service_id)->pluck('service_type')->first();
    	$order_detail->category = \App\Model\Category::find($order_detail->bookingorder->category_id);
    	unset($order_detail->bookingorder);

        $order_detail->extra_detail = self::getExtraRequestInfo($order_data,$order_detail);
    	return $order_detail;
    }

    public static function getExtraRequestInfo($order_data,$res){
        $request_detail = null;
        $request_detail =  $order_data;
        if($request_detail){
            $lat_longs=[];
            if($res->sr_info->profile && $res->sr_info->profile->lat!==null &&  $res->sr_info->profile->long!==null){
                $request_detail->center_location = null;
                if($res->servicetype && $res->servicetype->service_type=='clinic_visit'){

                    $request_detail->center_location = ["name"=>$res->sr_info->profile->location_name,"lat"=>$res->sr_info->profile->lat,"long"=>$res->sr_info->profile->long];
                }
                if(isset($request_detail->lat) && isset($request_detail->long)){
                    $lat_longs['user_lat'] = $request_detail->lat;
                    $lat_longs['user_long'] = $request_detail->long;
                    $lat_longs['doctor_lat'] = $res->sr_info->profile->lat;
                    $lat_longs['doctor_long'] = $res->sr_info->profile->long;
                }
            }
            $request_detail->distance = null;
            if(isset($lat_longs['user_lat'])){
                $request_detail->distance = Helper::twopoints_on_earth($lat_longs). " KM";
            }
            $request_detail->filter_id = null;
            $request_detail->filter_name = null;
        }
        return $request_detail;
    }

}
