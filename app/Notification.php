<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\User;
use Config;
class Notification extends Model
{
   public function push_notification($user_ids,$data){
   		$others = [];
      $ios_types = [];
   		foreach ($user_ids as $key => $user_id) {
   			$user_data = User::find($user_id);
   			if($user_data && $user_data->fcm_id){
          if(strtolower($user_data->device_type)=='ios'){
            $ios_types[] =  $user_data->fcm_id;
          }else{
   				   $others[] = $user_data->fcm_id;
          }
   			}
   		}
      $timeToLive = null;
      $priority = "normal";
      $pushTypes = ["CALL","CALL_RINGING","CALL_ACCEPTED","CALL_CANCELED","REQUEST_COMPLETED","BOOKING_REQUEST"];
      if(in_array($data['pushType'],$pushTypes)){
        $priority = "high";
      }
      if($data['pushType']=="CALL" || $data['pushType']=="CALL_RINGING" || $data['pushType']=="CALL_ACCEPTED" || $data['pushType']=="CALL_CANCELED"){
        $timeToLive = 20;
      }
      $notification = [];
      if(count($others)>0){
          $fields = array (
              'registration_ids' =>$others,
              'data' =>$data,
              'notification'=>null,
              "sound"=> "default",
              "priority"=>$priority
          );
          if($timeToLive){
            $fields["time_to_live"] = $timeToLive;
          }
          \Log::channel('custom')->info('Android Notification', ['device_ids'=>$others,'fields' => $fields]);
          $this->sendNotification($fields);
      }
      if(count($ios_types)>0){
          if(isset($data['pushType'])){
            $notification = [
                "title" => $data["pushType"],
                "body"=> $data["message"],
                "sound"=> "default",
                "badge"=>0
            ];
          }
          $fields = array (
              'registration_ids' =>$ios_types,
              'data' =>$data,
              'notification'=>$notification,
              "priority"=>$priority,
          );
          if($timeToLive){
            $fields["time_to_live"] = $timeToLive;
          }
          \Log::channel('custom')->info('IOS Notification', ['device_ids'=>$ios_types,'fields' => $fields]);
          $this->sendNotification($fields);
      }
      return;


   }

   public function push_test_notification($fcm_id,$data,$request){
      $others = [];
      $ios_types = [];
      if($request->device_type=='IOS'){
        $ios_types[] =  $fcm_id;
      }else{
         $others[] = $fcm_id;
      }
      $priority = "normal";
      $timeToLive = null;
      $notification = [];
      if(count($others)>0){
          $fields = array (
              'registration_ids' =>$others,
              'data' =>$data,
              'notification'=>null,
              "priority"=>$priority
          );
          if($timeToLive){
            $fields["time_to_live"] = $timeToLive;
          }
          return $this->sendTestNotification($fields,$request->fcm_server_key);

      }
      if(count($ios_types)>0){
          if(isset($data['pushType'])){
            $notification = [
                "title" => $data["pushType"],
                "body"=> $data["message"],
                "sound"=> "default",
                "badge"=>0
            ];
          }
          $fields = array (
              'registration_ids' =>$ios_types,
              'data' =>$data,
              'notification'=>$notification,
              "priority"=>$priority,
          );
          if($timeToLive){
            $fields["time_to_live"] = $timeToLive;
          }
          return $this->sendTestNotification($fields,$request->fcm_server_key);
      }

   }

   public function sendTestNotification($fields,$api_key){
   		$url = 'https://fcm.googleapis.com/fcm/send';
      $headers = array(
          'Content-Type:application/json',
          'Authorization:key='.$api_key
      );
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
      $result = curl_exec($ch);
      curl_close($ch);
      return $result;
   }


   public function sendNotification($fields){
      $url = 'https://fcm.googleapis.com/fcm/send';
      //header includes Content type and api key
      /*api_key available in:
      Firebase Console -> Project Settings -> CLOUD MESSAGING -> Server key*/
      $api_key = env('SERVER_KEY_ANDRIOD');
      if(Config::get('client_connected') && Config::get("client_data")->domain_name=="curenik"){
        $api_key = "AAAAJAP2EBA:APA91bHw_aB6CIomfP02M5Q3cCeScWBj9k3sMI4asx4qmrtJorX3hyCB_OofwWFxd6cK3LhDNr03e5cul-rbEFxUmgWY2FEyCxpEASR_nEGqjBzHL7OmMuvoQ46N4qfvt0iC6SMChHny";
      }
      if(Config::get('client_connected') && Config::get("client_data")->domain_name=="meetmd"){
        $api_key = "AAAA5zzxf7A:APA91bGpPD6CZEwwO9XdDDrebLYhCWkyGMGXWl7X50u3HVjwlzoUklJ5R8TOEHSiZ62EhxbW-k9UiSfnVwmCRGJzn8F9myu2WnSiGCBus4v_V1X6L31W6YmsmkGfLCvZxcOXUwQJ5vg8";
      }
      if(Config::get('client_connected') && Config::get("client_data")->domain_name=="physiotherapist"){
        $api_key = "AAAAfMsLRAc:APA91bHqtrp0Q9Aiy8DNBnCZHAIoSmPzLBYsjxsCy5UYifIP1sukPUYbQEnAfwVBXXr1LLs2p2eOwXOyHlIRWLo4Oil51QCzwBfxqKxrATQO2qz3O31F8OqGiBADJKd4Sa-SklH2fxr5";
      }
      if(Config::get('client_connected') && Config::get("client_data")->domain_name=="healthcare"){
        $api_key = "AAAAP6q3OX8:APA91bGmKnI1R9iYL-Cc9grwEJ0rZekgz3N4lGuefZcU2RAxiKk4dRqolfAOkgeVVnxlYkRJObKFgNWnz3HQncgcDhg_Pi0zxYqv3CmV5STxJ03aLvX3QZJNvI11SiCsTayZusy45AhO";
      }
      if(Config::get('client_connected') && Config::get("client_data")->domain_name=="healtcaremydoctor"){
        $api_key = "AAAA34cddfc:APA91bEEa_k2wElR68Nn7DDMB32o1P-1d-qvMA_S_WKDj9qL49YDn70enxibcNvOzJcD15OokDFcZJSE1ew_AuMUZSL40BS8UQd9Uh6Ztk4NrsY4upR3FpvxvqWNigI5goKyspjHZIbL";
      }
      if(Config::get('client_connected') && Config::get("client_data")->domain_name=="mp2r"){
        $api_key = "AAAA64lN9Xc:APA91bHReKpQhPNl0pN_42aoP8htSDwGHdoNYjR9xXxmwX3tL0SQC7NNQgt2UiWFV4cymK5kHAJJNdwyYnvHb1WcqqtUNbtmXIkpjbrGNY6H55jYO0Lzgk6TVrkfjNO18ep3OlqNWwCy";
      }
      if(Config::get('client_connected') && Config::get("client_data")->domain_name=="marketplace"){
        $api_key = "AAAA3XOvb-8:APA91bHHPgvNts5elTPiPFn19kYPU-jvhoQv_P_gx-dD5czvKyB2wEdY3XmGDn-3u5VBVbFPbAv0aHJI0xioN5EqCfLcuUnM7rU62N9x8Ng-F6RS5vBfaHqpc4GYeS8Naa1eGQAh7Q3d";
      }
      if(Config::get('client_connected') && Config::get("client_data")->domain_name=="education"){
        $api_key = "AAAA5XQpJX8:APA91bGAoctJ4pF3OJQG47VeAvdBFfuiHdZj7jqtycinW04yxJo_2Hj-o4hi4LIiYICvsG19Dn-_UA439PDBirY6Z-cdYlByoxGHQ9W9xWNx_MzbiDZ5dK2XVWv7O6_qOAbDvbpwAiqW";
      }
      if(Config::get('client_connected') && Config::get("client_data")->domain_name=="intely"){
        $api_key = "AAAAE377niE:APA91bGQNDBXyTvv1Q3SEzYuxSJ2er2Cka11ToP1X8lCCDl-vXSAW0Ho88yQEzhdC9ZUu-dIEnaZaC815bI7prRHLzTWxdu45WAyGl7r9RjbYHwHqJh2orVSmPLkmvDds03YS1dGaQ7T";
      }
      if(Config::get('client_connected') && Config::get("client_data")->domain_name=="heal"){
        $api_key = "AAAA6TV5n_k:APA91bEFAAQn-yv_eoFkqRWW85x3xK0S1fBky_Vi51FCghV7Arn5dC8S45ElyVrX7Nw3fm3pJ-axQ2Ths3boLRnUdn53MvTFgDUtKQe6m1U9G9Xc81GyDp1qpKJC9mDF4UqsHfX74q08";
      }
      if(Config::get('client_connected') && Config::get("client_data")->domain_name=="homedoctor"){
        $api_key = "AAAANM0FXDE:APA91bHzRNwliVNXjAoyfqssBn3SaU-wxnntyCaNi9kTtfnc7FuIaBZfPw6UNVhK7TK9Mm-p2j_1B4n4f0hG5FzcdKr2c86Gjs8oqOlqnevbfd-45BOR0eCzrwubmq0CoKTU1haB6QvS";
      }
      if(Config::get('client_connected') && Config::get("client_data")->domain_name=="feedni"){
        $api_key = "AAAAI2m6l3Q:APA91bGkSXOtGEOQuQxkq5A54dZoDi8V5163rGL5YnoVFNV8R6utT4KAeT-rQFZePlT_aY6jR2mTu0gAWuFiWZAU2xA3Z9vwA1BGUOobAhsZIw8XrVrKT-uPDC4vMIdlHMSazyuYSPHY";
      }
      if(Config::get('client_connected') && Config::get("client_data")->domain_name=="airdoc"){
        $api_key = "AAAA38hjuQs:APA91bGYEl4yefDaRpe0yv9w4Pc7HYrSZL413WW0pJB5-YqkwCKr3zNFvTvm3xZI4LlJMwDor6XJcl5Wn8ctwaY62_vSvwlCrFVrHhL2el-h4Z1tZEmSQ1KiacR8cgV944thVNl8X2Co";
      }
      if(Config::get('client_connected') && Config::get("client_data")->domain_name=="nurselynx"){
        $api_key = "AAAA8HhcKlk:APA91bE5KDVsQISrBCYPeNusR3zF8LRtRodGCVfnlrqpJ7TjB4RZ72CYin65CUb8TFkCC2F4FUxxWuKIhP7AK07ty1UeY5H4E_FZddc9ywbLU_zqDxd25Kemlrwq2k27O5_F6C2hLQ_4";
      }
      if(Config::get('client_connected') && Config::get("client_data")->domain_name=="iedu"){
        $api_key = "AAAAtvZTfBY:APA91bErBdLuYyjqvKjJzc3ThLbQLwcluCCCUV18mMSdP0DisFa3F5mAxdBZHgI06ipkc5XSkeL-lbsXJ5zGB8C1R4Z9_JkK2Seeq4qCTeU3VzpMuEUatHkCKDgbzhtxppBUps1EOtgG";
      }
      if(Config::get('client_connected') && Config::get("client_data")->domain_name=="taradoc"){
        $api_key = "AAAAGuScgqs:APA91bH_b_K5N7efge8dmA-pwsCbmxLiAcZOXHHQrNnkc_VT2OPtw3JXz2DJzj0n6J5qoVpzEIaqdV1WqYTUI9k1kI5LK85UEIdAA4u2fn10p_JdOt4mXp39d9foPgZ6jHmHcOAv3CG_";
      }

      if(Config::get('client_connected') && Config::get("client_data")->domain_name=="clouddoc"){
        $api_key = "AAAAfuO3mJo:APA91bEWlsEv80Wi5oeTMc4xg9eA9X6JKf6yZD1rTwHJvtHDZd2w4sO8bcNUev73_1mDEr7KA-6A6cIYdiBIgHf8INI39tRDimzYD399whE0dawW5bEuXpFE0T_IlcDagkhHBmEDRYZ9";
      }
      if(Config::get('client_connected') && Config::get("client_data")->domain_name=="care_connect_live"){
        $api_key = "AAAAPNUBkt8:APA91bEIBN0ToJ5Rkq1PKHOBu9h7YoKQNIcJMhahFvd7rmuhU4EaKiVh_-Lcx8u6clk057G-YA3Zw7vcbs61gk87gFvlvsIB3qGf3XGh3Q238gvXsST9DPOvpVo3uIoI3z8WuqZsQZRm";
      }
      $headers = array(
          'Content-Type:application/json',
          'Authorization:key='.$api_key
      );
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
      $result = curl_exec($ch);
      curl_close($ch);
      \Log::channel('custom')->info('sendNotification==========', ['domain' => Config::get("client_data")->domain_name,'apikey'=>$api_key]);
      \Log::channel('custom')->info('sendNotification==========', ['result' => $result]);
      return $result;
   }

   public static function markAsRead($receiver_id){
    	self::where(['read_status'=>'unread','receiver_id'=>$receiver_id])->update(['read_status' =>'read']);
    	return true;
    }
}
