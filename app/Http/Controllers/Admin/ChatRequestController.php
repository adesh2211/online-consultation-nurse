<?php

namespace App\Http\Controllers\Admin;
use Auth;
use App\Http\Controllers\Controller;
use App\Model\Request as RequestTable;
use App\Model\RequestHistory;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Model\ServiceProviderSlot;
use App\Model\ServiceProviderSlotsDate;
use App\Model\UserMasterPreference;
use App\Model\slot;
class ChatRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $admin = \Auth::user();
        $category = $admin->getCategoryData($admin->id);
        $doctors = [];
        if($category){
            $doctors = \App\Model\CustomInfo::where([
            'ref_table'=>'category',
            'ref_table_id'=>$category->id,
            'info_type'=>'custom_sp'])->get();
        }
        if($admin->hasRole('service_provider')){
            $chats = RequestTable::where('to_user',$admin->id)->orderBy('id','desc')->get();
        }
        elseif($admin->hasRole('doctor_manager'))
        {
            $timezone = $request->header('timezone');
            if(!$timezone){
                $timezone = 'Asia/Kolkata';
            }
            $slot_duration = \App\Model\EnableService::where('type','slot_duration')->first();
            $slot_minutes = $slot_duration->value;
            $add_slot_second = $slot_duration->value * 60;

            $dateznow = new \DateTime("now", new \DateTimeZone($timezone));
            $datenow = $dateznow->format('Y-m-d H:i');
            $time = $dateznow->format('H:i');
            $date = $dateznow->format('Y-m-d');
            if($request->slot_date )
            {
                $slotdate = $request->slot_date;
                // return $slotdate;

                $slotdate_n = Carbon::createFromFormat('m-d-Y', $slotdate);

                $slotdate_new = Carbon::parse($slotdate_n ,$timezone)->setTimezone('UTC')->format('Y-m-d');
                $newdatetime = $slotdate_new.' '.$request->slot_time;
                $utcdate =  Carbon::parse($newdatetime,$timezone)->setTimezone('UTC')->format('Y-m-d H:i');
            
                $end_date = Carbon::parse($newdatetime,$timezone)->addSeconds($add_slot_second)->setTimezone('UTC')->format('Y-m-d H:i');

                //$datenow = $request->slot_time;
                $date = $slotdate_new;
                $time=  $request->slot_time;

     
            } 
            else
            {
                $utcdate =  Carbon::parse($datenow,$timezone)->setTimezone('UTC')->format('Y-m-d H:i');
            
                $end_date = Carbon::parse($datenow,$timezone)->addSeconds($add_slot_second)->setTimezone('UTC')->format('Y-m-d H:i');
     
            }

            $weekMap = ['SU'=>0,'MO'=>1,'TU'=>2,'WE'=>3,'TH'=>4,'FR'=>5,'SA'=>6];
            if($slot_duration){
                $add_mins = $slot_duration->value * 60;
            }

            $sp_slots = ServiceProviderSlotsDate::where([
                'service_provider_id'=>$request->get('doctor_id'),
                'date'=>$date
            ])->get();
            $sp_slot_array = [];
            if($sp_slots->count()==0){
                $day = strtoupper(substr(Carbon::parse($date)->format('l'), 0, 2));
                $day_number = $weekMap[$day];
                $sp_slots = ServiceProviderSlot::where([
                    'service_provider_id'=>$request->get('doctor_id'),
                    'day'=>$day_number
                ])->get();
            }
          // return $sp_slots;   
                // $sp_slots = [
                //     'start_time' =>'',
                //     'end_time' => ''
                // ];
            
          
            $all_items = [];
           
            $duration_by_setting = true;

            if($sp_slots->count()>0){
                foreach ($sp_slots as $key => $sp_slot) {
                    $start_time_date = Carbon::parse($sp_slot->start_time,'UTC')->setTimezone($timezone);
                    $start_time = $start_time_date->isoFormat('h:mm a');
                    $end_time_date = Carbon::parse($sp_slot->end_time,'UTC')->setTimezone($timezone);
                    $end_time = $end_time_date->isoFormat('h:mm a');
                    $starttime    = strtotime ($start_time); //change to strtotime
                    $endtime      = strtotime ($end_time); //change to strtotime
                    while ($starttime < $endtime) // loop between time
                    { 
                       $time_s = date ("h:i a", $starttime);
                       $starttime_slot = date ("H:i:s", $starttime);
                       $starttime_slot_one_m = date ("H:i:s", $starttime + 60);
                       if($duration_by_setting)
                       {
                            $endDT = $starttime + $add_mins;
                            $end_time_new = date ("h:i a", $endtime);
                       }
                       else
                       {
                            $endDT = $endtime;
                            $end_time_new = date ("h:i a", $endtime);
                       }
                     
                       
                       $H = date("H", $starttime);
                       $mode = null;
                       if($H < 12)
                       {
                        $mode = "morning";
                       }
                       elseif($H > 11 && $H < 18)
                       {
                        $mode = "afternoon";
                       }
                       elseif($H > 17)
                       {
                        $mode = "evening";
                       }
                      // $time = date ("h:i a", $starttime);



                        $slotdate_s = $request->slot_date;
                        // return $slotdate;

                        $slotdate_n_s = Carbon::createFromFormat('m-d-Y', $slotdate_s);

                        $slotdate_new_s = Carbon::parse($slotdate_n_s ,$timezone)->setTimezone('UTC')->format('Y-m-d');
                        $newdatetime_s = $slotdate_new_s.' '.$time_s;
                        $utcdate_s =  Carbon::parse($newdatetime_s,$timezone)->setTimezone('UTC')->format('Y-m-d H:i');

                        // return $utcdate_s;


                        $fetch_count = RequestTable::where('to_user', $request->get('doctor_id'))
                        ->where('booking_date', $utcdate_s)
                        ->whereHas('requesthistory', function($query){
                            return $query->whereIn('status',['accept']);
                        })
                        ->orderBy('token_number','asc')->count();

                      $item_d = [
                          "time"    =>  $time_s,
                          "count"   =>  $fetch_count
                      ];
                  
                     array_push($all_items, $item_d);
                    
                    if($duration_by_setting){
                            $starttime += $add_mins;
                    }else{
                            $starttime += 60*60;
                    }
                  }
              }
            }

            $tmp = array_unique(array_column($all_items, 'time'));
            $all_items = array_intersect_key($all_items, $tmp); 
            

            $progress = RequestTable::where('to_user', $request->get('doctor_id'))
                        ->whereBetween('booking_date', [$utcdate, $end_date])
                        ->whereHas('requesthistory', function($query){
                          return $query->whereIn('status',['in-progress']);
                        })
                          ->orderBy('id','asc')->get();
            
            
            $chats = RequestTable::where('to_user', $request->get('doctor_id'))
                        ->where('booking_date', $utcdate)
                        ->whereHas('requesthistory', function($query){
                          return $query->whereIn('status',['accept']);
                        })
                          ->orderBy('token_number','asc')->get();
            
            // return $chats;
       
        }
        else{
            $chats = RequestTable::orderBy('id','desc')->get();
        }
        if($admin->hasRole('doctor_manager'))
        {
           //return $slotdate;
            return view('admin.doctor_requets')->with(['progress' => $progress,'slots'=>$all_items, 'slotdate'=>$slotdate,'chats'=>$chats,'doctors'=>$doctors,'datenow'=>$datenow,'date'=>$date, 'time'=>$time, 'doctor_id'=>$request->get('doctor_id')]);  
        }
        
        return view('admin.chats')->with(['chats'=>$chats,'doctors'=>$doctors]);
    }

    public function pendingRequests(){
        $admin = \Auth::user();
        $dateznow = new \DateTime("now", new \DateTimeZone('UTC'));
        $datenow = $dateznow->format('Y-m-d H:i:s');
        $hours = 24;
        $pending_request_hours = \App\Model\EnableService::where('type','pending_request_hours')->first();
        if($pending_request_hours){
            $hours = $pending_request_hours->value;
        }
        $end_date = Carbon::parse($datenow)->addHours($hours)->setTimezone('UTC')->format('Y-m-d H:i:s');
        $chats = RequestTable::whereHas('requesthistory', function($query){
                            return $query->whereIn('status',['pending']);
                    })->whereBetween('booking_date', [$datenow, $end_date])->orderBy('id','desc')->get();
        return view('admin.requests.pending')->with(['chats'=>$chats,'hours'=>$hours]);   
    }

    public function unAnswerRequests(){
        $admin = \Auth::user();
        $chats = RequestTable::whereHas('requesthistory', function($query){
                            return $query->whereIn('status',['accept','in-progress']);
                    })->whereDoesntHave('request_statuses', function($query){
                            // return $query->whereNotIn('status',['CALL_ACCEPTED','CALL_RINGING','CALL_CANCELED']);
                    })->orderBy('id','desc')->get();
        return view('admin.requests.un-ans')->with(['chats'=>$chats]);   
    }

    public function updateToken(Request $request)
    {
        // return json_encode($request->all());
        $user = \Auth::user();
        $requests = RequestTable::where('id',$request->id)->update([
                            'token_number' => $request->token_number
                    ]);
        $record = RequestTable::where('id',$request->id)->first();
        
        $status = ucwords(strtolower(str_replace('_', ' ', 'token updated')));
        $notification = new \App\Notification();
        $notification->sender_id = $user->id;
        $notification->receiver_id = $record->from_user;
        
        $notification->module_id = $record->id;
        $notification->module ='request';
        $notification->notification_type = strtoupper($status);
        $notification->message =__('notification.token_update_req', ['token_number' => $request->token_number]);
        $notification->save();
        $notification->push_notification(
            array($notification->receiver_id),
            array('pushType'=>strtoupper($status),
                'message'=>__('notification.token_update_req', ['token_number' => $request->token_number]),
                'request_time'=>$record->booking_date,
                //'service_type'=>$record->servicetype->type,
                'sender_name'=>$user->name,
                'sender_image'=>$user->profile_image,
                'request_id'=>$record->id,
                'call_id'=>'',
                'token_number' => $request->token_number
            ));
            return response()->json(['status'=>'success']);

    }

    public function changeAppointmentStatus(Request $request){
        $admin = \Auth::user();
        $req = RequestHistory::where('request_id',$request->request_id)->first();
        if($req){
            if(isset($request->admin_status)){
                $req->request->verified_hours = $request->hours;
                $req->request->admin_status = $request->status;
                $req->request->save();
            }else{
                $custominfo = new \App\Model\CustomInfo();
                $custominfo->info_type = 'request_status';
                $custominfo->ref_table = 'request';
                $custominfo->ref_table_id = $request->request_id;
                $custominfo->status = $request->status;
                $custominfo->save();
                $req->status = $request->status;
                $req->save();
                $status = ucwords(strtolower(str_replace('_', ' ', $request->status)));
                $notification = new \App\Notification();
                $notification->sender_id = $admin->id;
                $notification->receiver_id = $req->request->from_user;
                $notification->module_id = $req->request->id;
                $notification->module ='request';
                $notification->notification_type = strtoupper($request->status);
                $notification->message =__('notification.call_req_text', ['user_name' => $admin->name,'call_status'=>$status]);
                $notification->save();
                $notification->push_notification(
                array($notification->receiver_id),
                array('pushType'=>strtoupper($request->status),
                    'message'=>__('notification.call_req_text', ['user_name' => $admin->name,'call_status'=>$status]),
                    'request_id'=>$request->request_id,
                ));
            }
        }
        return response()->json(['status'=>'success']);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

    }
     /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
     public function createSessionAppointment(){
        $admin = \Auth::user();
        $category = $admin->getCategoryData($admin->id);
        $doctors = [];
        if($category){
            $doctors = \App\Model\CustomInfo::where([
            'ref_table'=>'category',
            'ref_table_id'=>$category->id,
            'info_type'=>'custom_sp'])->get();
        }
        $customers = \App\User::whereHas('roles', function ($query) {
                           $query->where('name','customer');
                        })->where('created_by',$admin->id)->orderBy('id','DESC')->get();
        return view('admin.appointment')->with(['customers'=>$customers,'doctors'=>$doctors]);
     }

     public function postSessionAppointment(Request $request){
        $user = \Auth::user();
        $validator = \Validator::make($request->all(), [
                'patient' => 'required',
                'physiotherapist'      => 'required',
                'appointment_date' => 'required',
          ]);
          if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
          }
        $input = $request->all();
        $category_id = $user->getCategoryData($user->id);
        $categoryservicetype_id = \App\Model\CategoryServiceType::where(['category_id'=>$category_id->id])->first();
        $spservicetype_id = null;
        $service_id = null;
        if($categoryservicetype_id){
            $service_id = $categoryservicetype_id->service_id;
            $spservicetype_id = \App\Model\SpServiceType::where(['category_service_id'=>$categoryservicetype_id->id,'sp_id'=>$user->id])->first();
        }
        $timezone = 'Asia/Kolkata';
        $datenow = Carbon::parse($request->appointment_date,$timezone)->setTimezone('UTC')->format('Y-m-d H:i:s');
        $sr_request = new RequestTable();
        $sr_request->from_user = $input['patient'];
        $sr_request->booking_date = $datenow;
        $sr_request->to_user = $user->id;
        $sr_request->service_id = $service_id;
        $sr_request->sp_service_type_id = ($spservicetype_id)?$spservicetype_id->id:null;
        $sr_request->save();

        $requesthistory = new \App\Model\RequestHistory();
        $requesthistory->duration = 0;
        $requesthistory->total_charges = 0;
        $requesthistory->schedule_type = 'schedule';
        $requesthistory->status = 'pending';
        $requesthistory->source_from = 'WEB';
        $requesthistory->request_id = $sr_request->id;
        $requesthistory->save();

        $new_sp = new \App\Model\CustomInfo();
        $new_sp->ref_table = 'requests';
        $new_sp->ref_table_id = $sr_request->id;
        $new_sp->info_type = 'doctor_assign';
        $new_sp->raw_detail = json_encode([
            'category_id'=>$category_id->id,
            'doctor_id'=>$input['physiotherapist'],
            'request_id'=>$sr_request->id]);
        $new_sp->save();
        return redirect('admin/requests');
     }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Model\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $request_info = RequestTable::find($id);
        return view('admin.appointment-view',compact('request_info'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Model\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function edit(RequestTable $requesttable)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Model\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, RequestTable $requesttable)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Model\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(RequestTable $requesttable)
    {
        //
    }
}
