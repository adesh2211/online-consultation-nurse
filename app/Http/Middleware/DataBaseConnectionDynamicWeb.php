<?php

namespace App\Http\Middleware;

use Closure;


use Cookie;
use Config;
use DB;
use App\User;
use Auth;
use Session;
class DataBaseConnectionDynamicWeb
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try{
            Config::set("default",false);
            $domain_name = 'nurselynx';
            $client = \App\Model\Client::where('domain_name','=',$domain_name)->first();
            if($client){
                $client->payment_type = 'stripe';
                $client->gateway_key = '';
                $client->gateway_secret = '';
                $client_features2 = \App\Model\GodPanel\ClientFeature::select('id as client_feature_id','client_id','feature_id','feature_values')
                ->where(['client_id'=>$client->id])
                ->get();
                foreach ($client_features2 as $key => $client_feature) {
                    if($client_feature->feature_values){
                        $client_feature->feature_values = json_decode($client_feature->feature_values,true);
                        $client_feature_key_values = [];
                        foreach ($client_feature->feature_values as $key_id => $value) {
                            $featurekey = \App\Model\GodPanel\FeatureKey::where('id',$key_id)
                            ->first();
                        }
                    }
                }
                $client_features = [];
                $builds = (object)[];
                Config::set("client_id", $client->id);
                Config::set("client_connected",true);
                Config::set("client_data",$client);
                $builds->ios_url = \App\Helpers\Helper::getClientFeatureKeys('Build Urls','IOS Url');
                $builds->android_url = \App\Helpers\Helper::getClientFeatureKeys('Build Urls','Android Url');
                $client_feature_type = \App\Model\GodPanel\ClientFeature::where('client_id',$client->id)->pluck('feature_id')->toArray();
                if($client_feature_type){
                    $client_features = \App\Model\GodPanel\Feature::whereIn('id',$client_feature_type)->groupBy('feature_type_id')->get();
                }
                Config::set("client_features",$client_features);
                Config::set("builds",$builds);
            }
            return $next($request);
        }catch(\Exception $ex){
            return $next($request);
        }
    }
}
