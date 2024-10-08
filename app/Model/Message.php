<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'user_id', 'message','request_id','message_type','image_url'
    ];

    public function user()
    {
        return $this->hasOne('App\User','id','user_id');
    }

    public static function getLastMessage($request_dt){
    	$last_message = self::select('id','message','user_id','created_at','message_type as messageType','delivered as isDelivered','read as isRead','image_url as imageUrl','status')
    	->with(['user' => function($query) {
    		return $query->select(['id', 'name', 'email','phone','profile_image']);
                        }])
    	->where('request_id',$request_dt->id)
    	->orderBy('id', 'desc')
    	->first();
    	if($last_message){
    		$receiverId = null;
        	if($request_dt->from_user==$last_message->user_id){
        		$receiverId = $request_dt->to_user;
        	}elseif ($request_dt->to_user==$last_message->user_id) {
        		$receiverId = $request_dt->from_user;
        	}
    		$last_message->sentAt = \Carbon\Carbon::parse($last_message->created_at)->getPreciseTimestamp(3);
    		$last_message->receiverId = $receiverId;
        	$last_message->senderId = $last_message->user_id;
            $last_message->messageId = $last_message->id;
    	}
    	return $last_message;
    }
    public static function getUnReadCount($request_dt,$user_id){
    	$count = self::select('id','status')->where(['request_id'=>$request_dt->id,
            'receiver_id'=>$user_id])->whereIn('status',['SENT','DELIVERED'])->count();
    	return $count;
    }
    public static function markAsRead($request_id,$receiver_id){
    	self::where(['receiver_id'=>$receiver_id,'request_id'=>$request_id])->update(['read' =>'1','delivered'=>'1','status'=>'SEEN']);
    	return true;
    }

	public static function getMessages($id,$user_id){
        $request_dt = \App\Model\Request::find($id);
        $messages = self::select('id','message','user_id','created_at','message_type as messageType','delivered as isDelivered','read as isRead','image_url as imageUrl','status','request_id')->with(['user' => function($query) {
                    return $query->select(['id', 'name', 'email','phone','profile_image']);
                }])->where('request_id',$id)
        ->orderBy('id', 'desc')->take(10)->get()->toArray();
        // print_r($user_id);die;
        foreach ($messages as $key => $message) {
            $receiverId = null;
            $sender = false;
            if($user_id==$message['user_id']){
                $sender = true;
            }
            $messages[$key]['sender'] = $sender;
            $messages[$key]['sentAt'] = \Carbon\Carbon::parse($message['created_at'])->getPreciseTimestamp(3);
            $messages[$key]['senderId'] = $message['user_id'];
            $messages[$key]['messageId'] = $message['id'];
        }
        if($request_dt){
            $receiverId = $request_dt->from_user;
            $senderId = $request_dt->to_user;
            if($user_id==$request_dt->from_user){
                $receiverId = $request_dt->to_user;
                $senderId = $request_dt->from_user;
            }
            $request_dt->receiverId = $receiverId;
            $request_dt->senderId = $senderId;
        }
        return ['messages'=>$messages,'request_dt'=>$request_dt];
    }
}
