<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\User;
use App\Model\EmergancyRequest;
use Config; 
use App\Notification;

use Illuminate\Support\Facades\Log;

class EmergencyRequestProcess implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
       
        $consultant_ids = \App\Model\SpServiceType::where('category_service_id', $this->data['category_service_id'])->pluck('sp_id');
        
        Log::channel('emergency_req')->info($consultant_ids);

        $ratings = [
            "5",
            "4.9",
            "4.8",
            "4.7",
            "4.6",
            "4.5",
            "4.4",
            "4.3",
            "4.2",
            "4.1",
            "4", 
            "3.9",
            "3.8",
            "3.7",
            "3.6",
            "3.5"
        ];

        foreach($ratings as $rating)
        {
          
            $getConsults = User::whereHas('roles', function ($query) {
                            $query->whereIn('name',['service_provider']);
                        })->whereIn('id',$consultant_ids)->where('account_verified','!=',Null)
                            ->whereHas('profile', function ($query)  use($rating){
                                $query->where('rating','=', $rating);
                        })->orderBy('id','desc')
                        ->get();

            $sent_count = 0;
            $lastuser_id = null;
         
            foreach($getConsults as $consult)
            {
                Log::channel('emergency_req')->info($consult->id);
                
                $sent_count++;

                // send notification
                $notification = new Notification();
                $notification->sender_id = $this->data['userid'];
                $notification->receiver_id = isset($consult->id) ? $consult->id : '';
                $notification->module_id = $this->data['id'];
                $notification->module ='request';
                $notification->notification_type ='NEW_EMERGENCY_REQUEST';
                $notification->message = __('notification.new_req_text', ['user_name' => $this->data['username'],'service_type'=>($this->data['service_type'])?($this->data['service_type']):'']);
                $notification->save();
                //return $notification;
                // $notification->push_notification(array($consult),array(
                //     'request_id'=>$emr_request->id,
                //     'pushType'=>'NEW_EMERGENCY_REQUEST',
                //     'is_second_oponion'=>'',
                //     'message'=>$message
                // ));
                 $lastuser_id = $consult->id;
                
                // check if accepted or not
                $check_emergency_request = \App\Model\EmergancyRequest::where('id', $this->data['id'])->first();
                if($check_emergency_request->status == 'accept')
                {
                    break;
                }

                if($sent_count == 10)
                {
                   
                    // $emergency = \App\Model\EmergancyRequest::where('id', $emr_request->id)->first();
                    // $emergency->limit ='';
                    // $emergency->request_time = $user_time_zone_slot;
                    // $emergency->lastid = $lastuser_id;
                    // $emergency->rating = $rating;
                    // $emergency->save();

                    sleep(10);

                  
                    // if accepted break the loop

                    $sent_count = 0;
                }
            }

            sleep(10);
        }

    }

    public function failed($exception)
    {
        Log::channel('emergency_req')->info($exception);
    }
}
