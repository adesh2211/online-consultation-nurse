<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class MonthlyRequest extends Model
{
    public static function AddMonthlyRequest($plan_detail){
    	$userpackage = $plan_detail['userpackage'];
    	$package = $plan_detail['package'];
    	if($package->type=='monthly'){
    		$month = 1;
        }elseif ($package->type=='yearly') {
    		$month = 12;
        }elseif ($package->type=='half_yearly') {
    		$month = 6;
        }
        for ($i=0; $i < $month ; $i++) { 
        		$expired_on = \Carbon\Carbon::parse($userpackage->created_at)->addMonth($i)->endOfMonth()->format('Y-m-d H:i:s');
    			$mntly = new self();
    			$mntly->user_package_plan_id = $userpackage->id;
    			$mntly->user_id = $userpackage->user_id;
    			$mntly->available_requests = $package->total_session;
    			$mntly->expired_on = $expired_on;
    			$mntly->save();
        }
        return true;
    }

    public static function  checkFreeRequest($user_id){
            $res = self::where(['user_id'=>$user_id])->whereMonth('expired_on',\Carbon\Carbon::now()->format('m'))->whereYear('expired_on',\Carbon\Carbon::now()->format('Y'))->where('available_requests','>=',1)->orderBy('id','ASC')->first();
            return $res;
    }
}
