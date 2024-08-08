<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Tier extends Model
{
    /**
    * User Cards
    * @param 
    */
    public function tier_options()
    {
        return $this->hasMany('App\Model\TierOption','tier_id')->select('id','title','tier_id');
    }

    public static function getTierDetail($req_id){
    	$detail = null;
    	$tier = \App\Model\RequestTierPlan::select('request_id','tier_id')->where(['request_id'=>$req_id])->first();
    	if($tier){
    		$tier_option_ids = \App\Model\RequestTierPlan::where(['request_id'=>$req_id])->get();
    		$detail = self::select('id','title','price')->find($tier->tier_id);
    		$tier_option_detail = [];
    		foreach ($tier_option_ids as $tier_option_id) {
    			$tier_option = \App\Model\TierOption::select('id','title','tier_id')->where('id',$tier_option_id->tier_option_id)->first();
    			if($tier_option){
    				$tier_option_detail[] = array(
    					'id'=>$tier_option->id,
    					'title'=>$tier_option->title,
    					'tier_id'=>$tier_option->tier_id,
    					'status'=>$tier_option_id->status,
    					'type'=>$tier_option_id->type
    				);
    			}
    		}
    		if($detail){
    			$detail->tier_options = $tier_option_detail;
    		}

    	}
    	return $detail;
    }
}
