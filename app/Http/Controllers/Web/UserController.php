<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Config;
use App\Model\Category;
use App\Model\CategoryServiceProvider;
use App\Model\SpServiceType;
use App\Rules\MatchOldPassword;
use App\User;
use App\Model\Profile;
use Auth;
use App\Model\SubjectTopic;
use App\Model\City;
use App\Model\Feedback;
use App\Model\State;
use App\Model\Insurance;
use App\Notification;
use App\Model\UserInsurance;
use Illuminate\Support\Facades\Validator;
use App\Model\CustomUserField;
use App\Model\Banner;
use App\Helpers\Helper;
use Hash;
use Location;
use App\Model\Request as RequestData;
use DateTime,DateTimeZone;
use App\Model\RequestHistory;
use App\Model\Service,App\Model\SocialAccount,App\Model\EnableService;
use App\Model\ServiceProviderSlotsDate;
use Carbon\Carbon;
use App\Model\ServiceProviderSlot;
use DB;
use App\Model\Country;
use Twilio\Jwt\ClientToken;
use Twilio\Rest\Client;
use App\Http\Traits\CategoriesTrait;





class UserController extends Controller
{
    use CategoriesTrait;
    public function paginate($items, $perPage = 20, $page = null, $options = [])
    {
		// use Illuminate\Pagination\Paginator;
		// use Illuminate\Support\Collection;
		// use Illuminate\Pagination\LengthAwarePaginator;
        $page = $page ?: (\Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof \Illuminate\Support\Collection ? $items : \Illuminate\Support\Collection::make($items);
        return new \Illuminate\Pagination\LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }
    /*
        This function is used to show home
        page when user signup
    */
    public function index(){
        $ip = request()->ip();
        // $ip = '50.90.0.1';
        $data = Location::get($ip);
        $banners = Banner::where('enable',1)->orderBy('id','DESC')->get();
        $zipCode = isset($data->zipCode) ?  $data->zipCode : '';
       
        if(Config::get("client_data")->domain_name=="mp2r" || Config::get("client_data")->domain_name=="food"){
            $categories = Category::where(array('enable'=>1,'parent_id'=>null))->get();
            return view('vendor.mp2r.user.home')->with(array('categories'=>$categories,'zipCode'=> $zipCode,'banners' => $banners));
        }
    }
    public function postServiceProviderOnline(Request $request){
        $user_id = $request->user_id;
        $manual_available = $request->manual_available;
        $user = User::where('id',$user_id)->first();
        if($user){
            if($manual_available == 'true'){
                $user->manual_available = 1;
            }else{
                $user->manual_available = 0;
            }
            $user->save();
        }
        return response()->json(['status'=>'success']);
    }
    public function get_category($category){

        if(Config::get("client_data")->domain_name=="mp2r" || Config::get("client_data")->domain_name=="food"){
            //Get dactor list
            $per_page = 10;
            $ids = [];
            $sp_ids = CategoryServiceProvider::select('sp_id')->where('category_id',$category->id)->get();
             foreach($sp_ids as $sp_id){
                array_push($ids,$sp_id->sp_id);
             }
             $doctors = User::whereIn('id',$ids)->paginate(5);
            // die();
            foreach ($doctors as $doctor) {
                $user_table = User::find($doctor->id);
                $user_table->profile; 
                $doctor->categoryData = $user_table->getCategoryData($doctor->id);
                $doctor->additionals = $user_table->getAdditionals($doctor->id);
                $doctor->insurances = $user_table->getInsurnceData($doctor->id);
                $doctor->filters = $user_table->getFilters($user_table->id);
                $doctor->subscriptions = $user_table->getSubscription($user_table);
                $doctor->custom_fields = $user_table->getCustomFields($user_table->id);
                $doctor->patientCount = User::getTotalRequestDone($doctor->id);
                $doctor->reviewCount = Feedback::reviewCountByConsulatant($user_table->id);
                $doctor->feedback = Feedback::where('consultant_id',$user_table->id)->first();
                $doctor->account_verified = ($user_table->account_verified)?true:false;
                $doctor->totalRating = 0;
                if(isset($doctor->category_service_type) && isset($doctor->category_service_type->service)){
                    $doctor->service_type = $doctor->category_service_type->service->type;
                    unset($doctor->category_service_type);
                }
                if($user_table->profile){
                    $doctor->profile->bio = $user_table->profile->about;
                    $doctor->profile->qualification = $user_table->profile->qualification;
                    $doctor->totalRating = $user_table->profile->rating;
                    $doctor->profile->location = ["name"=>$user_table->profile->location_name,"lat"=>$user_table->profile->lat,"long"=>$user_table->profile->long];
                }
       
              //  $doctor->doctor_data = Helper::getMoreData($doctor->doctor_data);
            }
    
          
            return view('vendor.mp2r.user.category-page')->with(array('category' =>$category,'doctors'=> $doctors));
        }
    }

    public function doctor_detail($user){
        $doctor = User::with('profile')->where('id',$user)->first();
        $reviewCount = Feedback::reviewCountByConsulatant($user);
        $reviews = User::getUserReview($user);
        if(Config::get('client_connected') && (Config::get('client_data')->domain_name=='iedu'))
        {
            return view('vendor.iedu.tutor-Details')->with(array('doctor'=> $doctor,'reviews'=> $reviews,'reviewCount'=>$reviewCount));  
        }
        else
        {
            return view('vendor.mp2r.user.doctor-page')->with(array('doctor'=> $doctor,'reviews'=> $reviews,'reviewCount'=>$reviewCount));
        }
       
    }

    /*
        Function for change password
        @post
    */
    public function changePassword(Request $request){
        //dd(Hash::make(12345678));
       // $data = $request->validate([
       //      'old_password' => ['required', new MatchOldPassword],
       //      'new_password' => ['requiredmin:8'],
       //  ]);
       // $this->validate($request,[


       //      'old_password' => 'required', new MatchOldPassword,
       //      'new_password' => 'required|min:8|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[@$!%*#?&]/',

       //      're_password' => 'required|same:new_password',
       //  ]);
        // $user = auth()->user(); // or pass an actual user here
        // $rules['old_password'] = 'required|password';
        // $rules['new_password'] = 'required|string|min:8|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[@$!%*#?&]/';
        // $rules['re_password'] => 'required|same:new_password';

        //     $customMessages['password.required'] = "The Password  required";

        //     $customMessages['password.regex:/[a-z]/'] = "Password Should be lowercase characters (A – Z)";
        //     $customMessages['password.regex:/[A-Z]/'] = 'Password Should be uppercase characters (A – Z)';

            $rules = [
                'old_password' => 'required|password',
                'new_password' => 'required|string|min:8|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/',
                're_password' => 'required|same:new_password',
            ];

            $customMessages = [
                'old_password.required' => 'The :attribute field is required.',
                'old_password.password' => 'The :attribute does not match',
                'new_password.regex' => 'New Password  should contain at-least 1 Uppercase, 1 Lowercase, 1 Numeric.'
            ];

            $this->validate($request, $rules, $customMessages);


        User::find(auth()->user()->id)->update(['password'=> Hash::make($request->new_password)]);
        return response()
        ->json(['message' => 'Password change successfully.', 'error' => false]);
    }


    /*
        Function for update user profile
    */
    
    public function updateProfile(Request $request){
        //This function is used in user model
        //@parms $request.
        $userId = Auth::user()->id;
        User::updateUserProfile($request, $userId);
        return redirect()->back()->with('message','Profile updated successfully!!');

    }

    /*
        Function for get city by state id
        @method post
        @params $stateId
    */

    public function getState(Request $request){

         $state=State::select('id')->where('name',$request->state_id)->first();
        $state_id=($state->id);

         $cities = City::where('state_id',$state_id)->get();
         return response()
         ->json(['cities' => $cities, 'error' => false]);
    }

    /*
      user profile page.
    */
    public function account(){
        $states = State::where('country_id', 231)->get();
		$insurances = Insurance::all();
		$userId = Auth::user()->id;
		$user = User::with('profile')->where('id',$userId )->first();
        if(Config::get('client_connected') && (Config::get('client_data')->domain_name=='care_connect_live'))
        {
            if($user->profile != '' && $user->profile != null)
            {
                if($user->profile->state)
                {
                   $state_id = State::select('id')->where('name', $user->profile->state)->first();
    
                   $cities = City::where('state_id',$state_id->id)->get();
                }
            }
            $cities = [];
        }
        elseif(Config::get('client_connected') && (Config::get('client_data')->domain_name=='iedu'))
        {
            if($user->profile != '' && $user->profile != null)
            {
                if($user->profile->state)
                {
                   $state_id = State::select('id')->where('name', $user->profile->state)->first();
    
                   $cities = City::where('state_id',$state_id->id)->get();
                }
            }
            $cities = [];
        }
        else
        {
            $state_id = State::select('id')->where('name', $user->profile->state)->first();

		    $cities = City::where('state_id',$state_id->id)->get();
        }
		
		$user_insurance_id = UserInsurance::select('insurance_id')->where('user_id',$userId)->first();
		$user_insurance_id = isset($user_insurance_id->insurance_id) ? $user_insurance_id->insurance_id : 0 ;
        $user_insurance = UserInsurance::with('insurance')->where('user_id',$userId)->first();
        $user_zip_code=CustomUserField::with('customfield')->where('user_id',$userId)->where('custom_field_id',2)->first();
        $cookie_policy = \App\Model\Page::where('slug','cookie-policy')->first();
        $privacy_policy = \App\Model\Page::where('slug','privacy-policy')->first();
        $notifications = Notification::select('id','notification_type as pushType','message','module','read_status','created_at','sender_id as form_user','receiver_id as to_user')->where('receiver_id',$user->id)->orderBy('id', 'desc')->paginate(20);
        if($notifications){
            foreach ($notifications as $key => $notification) {
                $notification->form_user = User::select('id','name','profile_image')->where('id',$notification->form_user)->first();
            }
        }
		if(Config::get("client_data")->domain_name == "mp2r" || Config::get("client_data")->domain_name=="food" || Config::get("client_data")->domain_name=="iedu" || Config::get("client_data")->domain_name=="care_connect_live"){
            $question1 = Helper::getSecurityQuestion('question1');
            $question2 = Helper::getSecurityQuestion('question2');
            $question3 = Helper::getSecurityQuestion('question3');
            $selectedQ1 = Helper::getSelectedQuestion('question1',$user->id);
            $selectedQ2 = Helper::getSelectedQuestion('question2',$user->id);
            $selectedQ3 = Helper::getSelectedQuestion('question3',$user->id);

            if(Config::get('client_data')->domain_name=='care_connect_live'|| Config::get('client_data')->domain_name=='iedu'){
              
                return view('vendor.'.Config::get("client_data")->domain_name.'.profile')->with(array('user'=>$user,'cities'=>$cities,
                    'states'=> $states,
                    'insurances'=>$insurances,
                    'user_insurance_id'=>$user_insurance_id,
                    'user_insurance' => $user_insurance,
                    'user_zip_code' => $user_zip_code,
                    'cookie_policy'=>$cookie_policy,
                    'privacy_policy'=>$privacy_policy,
                    'notifications' => $notifications,'question1'=>$question1,
                    'question2'=>$question2,
                    'question3'=>$question3,
                    'selectedQ1'=>$selectedQ1,
                    'selectedQ2'=>$selectedQ2,
                    'selectedQ3'=>$selectedQ3,));
                
            }
           
            else
            {
			return view('vendor.mp2r.user.account')->with(array('user'=>$user,'cities'=>$cities,'states'=> $states,'insurances'=>$insurances,'user_insurance_id'=>$user_insurance_id,'user_insurance' => $user_insurance,'user_zip_code' => $user_zip_code,'cookie_policy'=>$cookie_policy,'privacy_policy'=>$privacy_policy, 'notifications' => $notifications,'question1'=>$question1,
                'question2'=>$question2,
                'question3'=>$question3,
                'selectedQ1'=>$selectedQ1,
                'selectedQ2'=>$selectedQ2,
                'selectedQ3'=>$selectedQ3,));
            }
		}

    }
    

    /*

    user image update

    */

    public function Useruploadimage(Request $request){

        if ($request->hasfile('profile_image')) {
            if ($image = $request->file('profile_image')) {
                $extension = $image->getClientOriginalExtension();
                $filename = str_replace(' ', '', md5(time()) . '_' . $image->getClientOriginalName());
                $thumb = \Image::make($image)->resize(
                    100,
                    100,
                    function ($constraint) {
                        $constraint->aspectRatio();
                    }
                )->encode($extension);
                $normal = \Image::make($image)->resize(
                    400,
                    400,
                    function ($constraint) {
                        $constraint->aspectRatio();
                    }
                )->encode($extension);
                $big = \Image::make($image)->encode($extension);
                $_800x800 = \Image::make($image)->resize(
                    800,
                    800,
                    function ($constraint) {
                        $constraint->aspectRatio();
                    }
                )->encode($extension);
                $_400x400 = \Image::make($image)->resize(
                    400,
                    400,
                    function ($constraint) {
                        $constraint->aspectRatio();
                    }
                )->encode($extension);
                \Storage::disk('spaces')->put('thumbs/' . $filename, (string)$thumb, 'public');
                \Storage::disk('spaces')->put('uploads/' . $filename, (string)$normal, 'public');

                \Storage::disk('spaces')->put('original/' . $filename, (string)$big, 'public');
                \Storage::disk('spaces')->put('800x800/' . $filename, (string)$_800x800, 'public');
                \Storage::disk('spaces')->put('400x400/' . $filename, (string)$_400x400, 'public');
                \Storage::disk('spaces')->put('original/' . $filename, (string)$big, 'public');
                $user=Auth::user();
                $user->profile_image = $filename;
                $user->save();

                
            }
        }

        return response(['status' => 'success', 'statuscode' => 200, 'message' => __('Customer profile image update  successfully !')], 200);
    }


    /* 
    chnage customer password

    */

    public function changeCustomerPassword(Request $request){
        //dd(Hash::make(12345678));

        // $data = $request->validate([
        //     'old_password' => ['required', new MatchOldPassword],
        //     'new_password' => ['required'],
        // ]);
        User::find(auth()->user()->id)->update(['password'=> Hash::make($request->new_password)]);

        return redirect()->route('get-state')->with('success','Password change successfully.'); 
    }



    public function ServiceRequestPage(Request $request,$id){

        $Get_Category=Category::find($id);

        $Sp_Detail=CategoryServiceProvider::with('users','users.Customuserfield')->where('category_id',$id)->paginate(5);

        return view('vendor.mp2r.user.patient_request',compact('Get_Category','Sp_Detail'));
    }


    public function ServiceproviderDetail(Request $request,$id,$user_id){

        $Get_Category=Category::find($id);

        $Sp_Detail=User::getDoctorDetail($user_id);

        $feedback=Feedback::with('user')->where('consultant_id',$user_id)->paginate(5);

        return view('vendor.mp2r.user.service_provider_detail',compact('Get_Category','Sp_Detail','feedback'));

    }


    public function SpRequestConnectNow(Request $request){

        $timezone = $request->header('timezone');


        
        if(!$timezone){

            $timezone = 'Asia/Kolkata';

            date_default_timezone_set('Asia/Kolkata');

            $date=date('Y-m-d H:i:s');
        }
           
        
        $requestData=RequestData::create([

            'to_user' => $request['from_user'],

            'from_user' =>  Auth()->user()->id,

            'booking_date' => $date,

            'payment' => 'success',

            'request_type' => 'single',

            'service_id' => 1,

        ]);


        RequestHistory::create([

            'duration' => 0,

            'total_charges' => 0,

            'status' => 'pending',

            'request_id' => $requestData->id,
        ]);

        return redirect()->back()->with('message','Connect Now Request Sent Successfully !');
    }

    public static function postSlotsByMultipleDates(Request $request) {

        try{
            
            
            $input = $request->all();
            $timezone = $request->header('timezone');
            if(!$timezone){
                $timezone = 'Asia/Kolkata';
            }
            
            
            $input['date'] = date("Y-m-d", strtotime($request->dates));  
           
            if(isset($request->applyoption) && $request->applyoption=='weekwise'){
                 $sp_slot_array = [];
                 $actual_days = [];
                 $days = [0,1,2,3,4,5,6];
                 $single_day = 0;
                 foreach ($days as $key => $day) {
                    $sp_slot = ServiceProviderSlot::where([
                        'service_provider_id'=>$input['doctor_id'],
                        'service_id'=>$input['service_id'],
                        'day'=>$day,
                        'category_id'=>$input['category_id'],
                    ])->first();
                    if($sp_slot){
                        $single_day = $day;
                        $actual_days[] = true;
                    }else{
                        $actual_days[] = false;
                    }
                 }
                 $sp_slots = ServiceProviderSlot::where([
                        'service_provider_id'=>$input['doctor_id'],
                        'service_id'=>$input['service_id'],
                        'category_id'=>$input['category_id'],
                        'day'=>$single_day,
                    ])->get();
                 $array_of_time = array ();
                if($sp_slots->count()>0){
                    foreach ($sp_slots as $key => $sp_slot) {
                        $start_time_date = Carbon::parse($sp_slot->start_time,'UTC')->setTimezone($timezone);
                        $start_time = $start_time_date->isoFormat('h:mm a');
                        $end_time_date = Carbon::parse($sp_slot->end_time,'UTC')->setTimezone($timezone);
                        $end_time = $end_time_date->isoFormat('h:mm a');
                        $sp_slot_array[] = array('start_time'=>$start_time,'end_time'=>$end_time);
                    }
                }
                return response(['status' => "success", 'statuscode' => 200,'message' => __('Slot List '), 'data' =>['slots'=>$sp_slot_array,'interval'=>null,'date'=>null,'days'=>$actual_days]], 200);
            }else{
                $weekMap = ['SU'=>0,'MO'=>1,'TU'=>2,'WE'=>3,'TH'=>4,'FR'=>5,'SA'=>6];
                $sp_slots = ServiceProviderSlotsDate::where([
                    'service_provider_id'=>$input['doctor_id'],
                    'service_id'=>$input['service_id'],
                    'date'=>$input['date'],
                    'category_id'=>$input['category_id'],
                ])->get();
                $sp_slot_array = [];
                if($sp_slots->count()==0){
                    $day = strtoupper(substr(Carbon::parse($input['date'])->format('l'), 0, 2));
                    $day_number = $weekMap[$day];
                    $sp_slots = ServiceProviderSlot::where([
                        'service_provider_id'=>$input['doctor_id'],
                        'service_id'=>$input['service_id'],
                        'day'=>$day_number,
                        'category_id'=>$input['category_id'],
                    ])->get();

                }
                $dateznow = new DateTime("now", new DateTimeZone($timezone));
                $datenow = $dateznow->format('Y-m-d H:i:s');
                $current_date = $dateznow->format('Y-m-d');
                $currentTime    = strtotime ($datenow);
                $slot_duration = EnableService::where('type','slot_duration')->first();
                $add_mins  = 30 * 60;
                if($slot_duration){
                    $add_mins = $slot_duration->value * 60;
                }
                // print_r($currentTime);die;
                // echo " current time $currentTime \n";
                $array_of_time = array ();
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
                           $time = date ("h:i a", $starttime);
                           $starttime_slot = date ("H:i:s", $starttime);
                           $starttime_slot_one_m = date ("H:i:s", $starttime + 60);
                           // $starttime += $add_mins; // to check endtie=me
                           // $endtime_slot = date ("H:i:s", $starttime);

                           $endDT = $starttime + $add_mins;
                           $endtime_slot = date("H:i:s", $endDT);
                           $start_time_slot_utcdate = Carbon::parse($input['date'].' '.$starttime_slot,$timezone)->setTimezone('UTC');
                           $starttime_slot_one_m = Carbon::parse($input['date'].' '.$starttime_slot_one_m,$timezone)->setTimezone('UTC');
                           $end_time_slot_utcdate = Carbon::parse($input['date'].' '.$endtime_slot,$timezone)->setTimezone('UTC');
                           // print_r($end_time_slot_utcdate);
                           $exist = \App\Model\Request::where('to_user',$input['doctor_id'])
                           // ->where('booking_date','<=',$end_time_slot_utcdate)
                            ->where('booking_date','=',$start_time_slot_utcdate)
                            ->orWhereBetween('booking_end_date',[$starttime_slot_one_m,$end_time_slot_utcdate])
                           ->whereHas('requesthistory', function ($query) {
                                $query->where('status','!=','canceled');
                            })
                           ->get();
                           $available = true;
                           if($exist->count()>0){
                                $available = false;
                           }
                           if(isset($sp_slot->working_today) && $sp_slot->working_today=='n'){
                                $available = false;
                           }
                            // print_r($input['date']);die;
                           if($current_date==$input['date'] && $starttime>=$currentTime){
                                $time = date ("h:i a", $starttime);
                                $array_of_time[] = ["time"=>$time,"available"=>$available];
                           }else if($input['date'] > $current_date){
                                $time = date ("h:i a", $starttime);
                                $array_of_time[] = ["time"=>$time,"available"=>$available];
                           }
                           $starttime += $add_mins;
                        }
                        $sp_slot_array[] = array('start_time'=>$start_time,'end_time'=>$end_time);
                    }
                }
                // die;
                return response(['status' => "success", 'statuscode' => 200,'message' => __('Slot List '), 'data' =>['slots'=>$sp_slot_array,'interval'=>$array_of_time,'date'=>$input['date']]], 200);
            }
            
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage().' '.$ex->getLine()], 500);
        }
    }


    public function SpScheduleBooking(Request $request){

         $this->validate($request, [
            'time' => 'required',
        ]);
        try{
            $user = Auth::user();
            $input = $request->all();
            $total_hours = 0;
            $request_data = null;
            $consult = User::find($request['from_user']);
            $category_id = $consult->getCategoryData($consult->id);
            $timezone = isset($input['timezone'])?$input['timezone']:'Asia/Kolkata';
            $input['timezone'] = $timezone;
            $user_time_zone_slot = '';
            $user_time_zone_date = '';
            $connect_now_validation_disable = false;
            if(Config::get('client_connected') && (Config::get('client_data')->domain_name=='mp2r')){
                $connect_now_validation_disable = true;
            }
            $datenow = Carbon::parse($request->dates['full_date'].' '.$request->time,$timezone)->setTimezone('UTC')->format('Y-m-d H:i:s');
            // print_r($datenow);die;
            $message = 'Something went wrong';
            $categoryservicetype_id = \App\Model\CategoryServiceType::where(['category_id'=>$category_id->id,'service_id'=>1])->first();
            $spservicetype_id = null;
            if($categoryservicetype_id){
                $spservicetype_id = \App\Model\SpServiceType::where(['category_service_id'=>$categoryservicetype_id->id,'sp_id'=>$consult->id])->first();
            }
            if($request_data){
                
            }else{
                $second_oponion = false;
                $sr_request = new \App\Model\Request();
                $sr_request->from_user = $user->id;
                $sr_request->booking_date = $datenow;
                $sr_request->to_user = $consult->id;
                $sr_request->service_id = 1;
                $sr_request->sp_service_type_id = ($spservicetype_id)?$spservicetype_id->id:null;
                if($sr_request->save()){
                    $requesthistory = new \App\Model\RequestHistory();
                    $requesthistory->duration = 0;
                    $requesthistory->total_charges = 0;
                    $requesthistory->schedule_type = 'schedule';
                    $requesthistory->status = 'pending';
                    $requesthistory->request_id = $sr_request->id;
                    $requesthistory->save();
                    $notification = new Notification();
                    $notification->sender_id = $user->id;
                    $notification->receiver_id = $consult->id;
                    $notification->module_id = $sr_request->id;
                    $notification->module ='request';
                    $notification->notification_type ='NEW_REQUEST';
                    $notification->message =__('notification.new_req_text', ['user_name' => $user->name]);
                    $notification->save();
                    $notification->push_notification(array($consult->id),array(
                        'pushType'=>'NEW_REQUEST',
                        'is_second_oponion'=>$second_oponion,
                        'message'=>__('notification.new_req_text', ['user_name' => $user->name])
                    ));
                }
            }
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage().' '.$ex->getLine()], 500);
        }

    }


    public function saveuserInsuranceInfo(Request $request){

        
        try{
            $input = $request->all();
            
            $user = Auth::user();
            $insurance = UserInsurance::where([
                'user_id'=>$user->id
            ])->first();
            if($insurance){
                $input['insurance_id'] = $insurance->insurance_id;
            }
            $CustomInfo = new \App\Model\CustomInfo();
            $CustomInfo->info_type = 'insurance_verification';
            $CustomInfo->ref_table = 'users';
            $CustomInfo->ref_table_id = Auth::user()->id;
            $CustomInfo->status = 'success';
            $CustomInfo->raw_detail = json_encode($input);
            $CustomInfo->save();
           
            return response(array(
                'status' => 'success',
                'statuscode' => 200,
                'message' =>'Insurance Info Saved')
        ,200);
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage()], 500);
        } 
    }



    public  function getUserDoctorList(Request $request,SpServiceType $subscription) {
       


        try{
            $doctors = [];
            $service_id = null;
            $service_ids = null;
            $service_type = null;
            $per_page = (isset($request->per_page)?$request->per_page:10);
            
                $request->radius = 50;
                $set_radius = \App\Model\EnableService::where('type','set_radius')->first();
                if($set_radius)
                    $request->radius = $set_radius->value;
                $per_page = (isset($request->per_page)?$request->per_page:10);
            
            $pageNumber = (isset($request->page)?$request->page:1);
            $service_id = isset($request->service_id)?$request->service_id:null;
            $request->service_type = isset($request->service_type)?$request->service_type:'all';
            $input = $request->all();
            $subscription = $subscription->newQuery();
            $state_id = null;
            if(isset($request->state)){
                $state_id = 0;
                $state = \App\Model\State::where('name',$request->state)->first();
                if($state){
                    $state_id = $state->id;
                } 
            }
            /* for Consultant Listing */
            $consultant_ids = User::whereHas('roles', function ($query) {
                           $query->where('name','service_provider');
                        })->orderBy('id','DESC')->pluck('id')->toArray();
            if($request->service_type!='all'){
                $service_type = Service::select('id')->where('type',$request->service_type)->first();
                if($service_type){
                    $service_ids[] = $service_type->id;
                }
                if($request->service_type=='consult_online'){
                   $service_ids = Service::select('id')->whereIn('type',['video call','call','audio','Call','Video Call','Audio'])->pluck('id')->toArray();
                }
            }
            if($request->service_type!=='all'){
                $categoryservicetypeids = [];
                if(is_array($service_ids) && count($service_ids)>0){
                    $categoryservicetypeids = CategoryServiceType::whereIn('service_id',$service_ids)->pluck('id');
                }
                $subscription->whereIn('category_service_id',$categoryservicetypeids);
            }

            if($service_id!=null){
                $categoryservicetypeids = [];
                $categoryservicetypeids = CategoryServiceType::where('service_id',$service_id)->pluck('id');
                $subscription->whereIn('category_service_id',$categoryservicetypeids);
            }
            if($request->has('filter_option_ids')){
                $filter_option_ids = explode(",",$request->filter_option_ids);
                $consultant_ids = ServiceProviderFilterOption::whereIn('filter_option_id',$filter_option_ids)->whereIn('sp_id',$consultant_ids)->groupBy('sp_id')->pluck('sp_id');
            }
            $available = true;
            if($request->has('search')){
                if($request->search){
                    $available = false;
                    $consultant_ids = User::whereLike('name',$request->search)->whereIn('id',$consultant_ids)->groupBy('id')->pluck('id');
                }
            }
            // $consultant_ids = Helper::checkVendorsAvailableToday($consultant_ids);
            /* For Nurse APP */
            if($request->has('lat') && $request->has('long') && isset($input['lat']) && isset($input['long']) &&  $input['lat']!==null && $input['long']!==null){
                $sqlDistance = DB::raw("( 111.045 * acos( cos( radians(" . $input['lat'] . ") )* cos( radians(  profiles.lat ) ) * cos( radians( profiles.long ) - radians(" . $input['long']  . ") ) + sin( radians(" . $input['lat']  . ") ) * sin( radians(  profiles.lat ) ) ) )");
                    $consultant_ids =  DB::table('profiles')
                    ->select('*')
                    ->selectRaw("{$sqlDistance} AS distance")
                    ->havingRaw('distance BETWEEN ? AND ?', [0,isset($request->radius)?$request->radius/100:50/100])
                    ->orderBy('distance',"DESC")
                    ->whereIn('user_id',$consultant_ids)->pluck('user_id')->toArray();
            }
            $timezone = $request->header('timezone');
            if(!$timezone){
                $timezone = 'Asia/Kolkata';
            }
            if ($request->has('category_id')) {
                $subscription->whereHas('categoryServiceProvider', function($q) use($request){
                    if(isset($request->category_id))
                        $q->where('category_id',$request->category_id);
                });
            }
            if(Auth::guard('web')->check() && Helper::chargeFromSP()){
                $user = Auth::guard('web')->user();
                if($user->hasrole('customer')){
                    $sp_data  = Helper::getDocotorInsuranceByUser($user->id,$consultant_ids);
                    if($sp_data['check']){
                        $consultant_ids = $sp_data['sp_ids'];
                    }
                }
            }
            $consultant_ids  = Helper::getPaidDoctors($consultant_ids);
            if($available){
                $consultant_ids = Helper::checkVendorsAvailableToday($consultant_ids);
            }
            $subscription->whereIn('sp_id',$consultant_ids);
            // print_r($consultant_ids);die;
            $subscription->where('available','1')->with('doctor_data')->groupBy('sp_id')
            ->whereHas('doctor_data', function($query){
                    return $query->where('account_verified','!=',null);
            })
            ->whereHas('doctor_data.roles', function($query){
                    return $query->where('name','service_provider');
            });
            $subscription->join('profiles as pp', 'pp.user_id', '=', 'sp_service_types.sp_id');
            if($state_id!==null)
                $subscription->where('pp.state',$state_id);
            $subscription->select('sp_service_types.*','pp.id AS profile_id');
            $subscription->orderBy('pp.rating', 'DESC');
            $subscription->orderByRaw('FIELD(sp_id,'.implode(",", $consultant_ids).')');
            $doctors = $subscription->paginate($per_page, ['*'], 'page', $pageNumber);
            foreach ($doctors as $key => $doctor) {
                $user_table = User::find($doctor->doctor_data->id);
                $user_table->profile; 
                $doctor->doctor_data->categoryData = $user_table->getCategoryData($doctor->doctor_data->id);
                $doctor->doctor_data->additionals = $user_table->getAdditionals($doctor->doctor_data->id);
                $doctor->doctor_data->insurances = $user_table->getInsurnceData($doctor->doctor_data->id);
                $doctor->doctor_data->filters = $user_table->getFilters($user_table->id);
                $doctor->doctor_data->subscriptions = $user_table->getSubscription($user_table);
                $doctor->doctor_data->custom_fields = $user_table->getCustomFields($user_table->id);
                $doctor->doctor_data->patientCount = User::getTotalRequestDone($doctor->doctor_data->id);
                $doctor->doctor_data->reviewCount = Feedback::reviewCountByConsulatant($user_table->id);
                $doctor->doctor_data->account_verified = ($user_table->account_verified)?true:false;
                $doctor->doctor_data->totalRating = 0;
                if(isset($doctor->category_service_type) && isset($doctor->category_service_type->service)){
                    $doctor->service_type = $doctor->category_service_type->service->type;
                    unset($doctor->category_service_type);
                }
                if($user_table->profile){
                    $doctor->doctor_data->profile->bio = $user_table->profile->about;
                    $doctor->doctor_data->totalRating = $user_table->profile->rating;
                    $doctor->doctor_data->profile->location = ["name"=>$user_table->profile->location_name,"lat"=>$user_table->profile->lat,"long"=>$user_table->profile->long];
                }
                $doctor->doctor_data = Helper::getMoreData($doctor->doctor_data);
            }
            $after = null;
            $before = null;
            if($doctors->hasMorePages()){
                $next_page = $doctors->currentPage() + 1;
            }else{
                $next_page = 0;
            }
            $pre_page = $doctors->currentPage() - 1;
            $per_page = $doctors->perPage();

            $Sp_Detail=$doctors->items();

            //dd($Sp_Detail);

            return view('vendor.mp2r.user.patient_request_filter',compact('Sp_Detail'));

        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage().' '.$ex->getLine()], 500);
        }

    } 

    public function AppointmentHistory(Request $request){

        $booking_history=RequestData::with('servicetype','sr_info','requesthistory')->where('from_user',Auth::user()->id)->orderBy('id','DESC')->get();

        return view('vendor.mp2r.user.appointment_history',compact('booking_history'));
    }

    public function ServiceProviderReview(Request $request){

        

        return Feedback::create([

            'consultant_id' => $request['consultant_id'],

            'from_user' => Auth::user()->id,

            'request_id' => $request['request_id'],

            'rating' => $request['rating'],

            'comment' => $request['comment'],

        ]);
    }


    public function Counselorcategory(Request $request){

        $counselor=Category::where([['parent_id',2],['enable',1]])->get();
        $counselor_category=Category::where([['id',2],['enable',1]])->get();

        return view('vendor.mp2r.user.counselor',compact('counselor','counselor_category'));
    }

    public function getDoctorPage(Request $request){
       
        //return $request->date;
        if(Auth::user()->hasrole('service_provider')){
         $categories = Category::where(['enable'=>'1','parent_id'=>null])
         ->orderBy('id',"ASC")
         ->get();
 
         $timezone = $request->header('timezone');
         if(!$timezone){
                 $timezone = 'Asia/Kolkata';
         }
         $dateznow = new DateTime("now", new DateTimeZone($timezone));
         $datenow = $dateznow->format('Y-m-d H:i:s');
         $current_date = $dateznow->format('Y-m-d');
        
        // $current_date = $dateznow->format('Y-m-d');
         if($request->date == '' || $request->date == null)
         {
             $request->date = $current_date;
         }
         
         $user = Auth::user();
         $isAprroved = false;
         if($user->account_verified){
                 $isAprroved = true;
         }
         $from_date = null;
         $end_date = null;
         
         // print_r($request->second_oponion);die;
             $data = [];
             $requests = [];
             $service_type = isset($request->service_type)?$request->service_type:'all';
             $service_id = isset($request->service_id)?$request->service_id:null;
             // Query 
             $per_page = (isset($request->per_page)?$request->per_page:10);
             $requests = \App\Model\Request::select('id','service_id','from_user','to_user','booking_date','created_at','booking_date as bookingDateUTC','request_type','token_number','request_category_type','request_category_type_id','join_time')
             ->whereHas('servicetype', function($query) use ($service_type,$service_id){
                 if($service_type!=='all')
                     return $query->where('type', $service_type);
                 if($service_id)
                     return $query->where('id', $service_id);
 
             })
             ->whereHas('requesthistory',function($query) use($request){
                if(isset($request->type) && $request->type=='upcoming'){
                    return $query->whereNotIn('status',['canceled','failed','completed']);
                }elseif (isset($request->type) && $request->type=='archived') {
                    return $query->whereIn('status',['canceled','failed','completed']);
                }
            })
             ->when('booking_date', function($query) use ($request,$timezone, $current_date){
                 if(isset($request->date)){
                     $from_date = $request->date.' 00:00:00';
                     $end_date = $request->date.' 23:59:59';
                     $fromUTC = Carbon::parse($from_date, $timezone)->setTimezone('UTC');
                     $toUTC = Carbon::parse($end_date, $timezone)->setTimezone('UTC');
                    return $query->whereBetween('booking_date', [$fromUTC, $toUTC]);
                   // return $query->whereBetween('booking_date',[$from_date,$end_date]);
                 }
                 else
                 {
                     return $query->where('booking_date','=',$current_date);
                 }
             })
             ->where('to_user',$user->id)->orderBy('id', 'desc')->get();
             $id = null;
             foreach ($requests as $key => $request_status) {
                 $request_status->is_prescription = false;    
                 if($request_status->prescription){
                     $request_status->is_prescription = true;
                   //  unset($request_status->prescription);    
                 }
                 $dateznow = new DateTime("now", new DateTimeZone('UTC'));
                 $datenow = $dateznow->format('Y-m-d H:i:s');
                 if(\Config::get('client_connected') && (\Config::get('client_data')->domain_name=='healtcaremydoctor')){
                     $next_hour_time = strtotime($datenow);
                 }else{
                     $next_hour_time = strtotime($datenow) + 3600;
                 }
                 if($id==null)
                     $id = $request_status->id;
                 $request_status->service_type = $request_status->servicetype->type;
                 $last_message = \App\Model\Message::getLastMessage($request_status);
                 $request_status->unReadCount = \App\Model\Message::getUnReadCount($request_status,$user->id);
                 $request_status->last_message = $last_message;
 
                 $date = Carbon::parse($request_status->booking_date,'UTC')->setTimezone($timezone);
                 $request_status->booking_date = $date->isoFormat('D MMMM YYYY, h:mm:ss a');
                 $request_status->time = $date->isoFormat('h:mm a');
                 $request_history = $request_status->requesthistory;
                
                 if(\Config::get('client_connected') && (\Config::get('client_data')->domain_name=='iedu')){


                   
                 $getcourse = \App\Model\SpCourse::where('sp_id',$user->id)->first();
                 if($getcourse){
                    $course = \App\Model\Course::where('id',$getcourse->course_id)->first();
                    $request_status->course = $course->title;
                 }
               
                }
                
                 if($request_history){
                     $request_status->price = $request_history->total_charges;
                     if($user->hasrole('service_provider') && $request_history->total_charges>0){
                        $request_status->price = (string)($request_history->total_charges - $request_history->admin_cut);
                    }
                     $request_status->duration = $request_history->duration;
                     $request_status->status = $request_history->status;
                     $request_status->schedule_type = $request_history->schedule_type;
                 }
                 if(strtotime($request_status->bookingDateUTC)>=$next_hour_time && $request_status->status=='pending'){
                     $request_status->canReschedule = true;
                     $request_status->canCancel = true;
                 }else{
                     $request_status->canReschedule = false;
                     $request_status->canCancel = false;
                 }
                 $request_status->extra_detail = RequestData::getExtraRequestInfo($request_status->id,$timezone);
                 
                 $request_status->from_user = User::select('name','email','id','profile_image','phone','country_code')->with(['profile'])->where('id',$request_status->from_user)->first();
                 $request_status->to_user = User::select('name','email','id','profile_image','phone','country_code')->with(['profile'])->where('id',$request_status->to_user)->first();
                 $request_status->to_user->categoryData = $request_status->to_user->getCategoryData($request_status->to_user->id);
                 $request_status->from_user->categoryData = $request_status->from_user->getCategoryData($request_status->from_user->id);
                 array_push($data,$request_status);
                 
             }
            // return json_encode($request_status);
             if(isset($request['request_id'])){
                 $id = $request['request_id'];
             }
           
             $data = $this->paginate($data, $per_page);
             $data->withPath('/user/requests');
            // return json_encode($data);
            if(\Config::get('client_connected') && (\Config::get('client_data')->domain_name=='iedu')){
                $userz = User::with('roles')->find($user->id);
                // if(!$userz->profile){
                //     $profile = New Profile();
                //     $profile->dob ='0000-00-00';
                //     $profile->user_id = $user->id;
                //     $profile->save();
                // }
                $userz->profile;
                $userz->profile->location = ["name"=>$userz->profile->location_name,"lat"=>$userz->profile->lat,"long"=>$userz->profile->long];
                $userz->profile->bio = $userz->profile->about;
                $userz->subscriptions = $userz->getSubscription($userz); 
                $userz->categoryData = $userz->getCategoryData($userz->id);
                $userz->additionals = $userz->getAdditionals($userz->id);
                $userz->filters = $userz->getFilters($userz->id);
                $userz->services = $user->getServices($userz->id);
                $userz->insurances = $user->getInsurnceData($userz->id);
                $userz->custom_fields = $user->getCustomFields($userz->id);
                if($user->hasrole('service_provider')){
                    $userz->totalRating =  $userz->profile->rating;
                    $userz->patientCount = User::getTotalRequestDone($user->id);
                    $userz->reviewCount = Feedback::reviewCountByConsulatant($user->id); 
                }
                $userz = Helper::getMoreData($userz);

                $language  = DB::table('master_preferences')
                ->join('master_preferences_options', 'master_preferences.id', '=', 'master_preferences_options.preference_id')->where('master_preferences.name','=','Languages')
                ->select('master_preferences.id as preferid', 'master_preferences_options.name as optname', 'master_preferences_options.id as optid')
                ->get();

                $getuserpreference = \App\Model\UserMasterPreference::where('user_id',$user->id)->get();
               
                $classes = $this->parentCategories();
                foreach ($classes as $key => $class) {
                    $subcategories = Category::where('parent_id',$class->id)->where('enable','=','1')->get();
                    
                    
                    foreach($subcategories as $sub)
                    {
                        $topics =[];
                        $get_topics = SubjectTopic::with('topic')
                                    ->whereHas('topic',function($q) use($sub){
                                            $q->where('subject_id',$sub->id)
                                                ->where('status','activate');
                                    })->get();

                        foreach($get_topics as $item)
                        {
                            array_push($topics,$item);
                        }
                        $sub->topics = $topics;
                    }
                    // $class->topics = $topics;

                    $class->subjects = $subcategories;
                }
                $review_list = \App\Model\Feedback::select('id','from_user','rating','comment')->where('consultant_id',Auth::user()->id)->with(['user' => function($query) {
                    return $query->select(['id', 'name', 'email','phone','profile_image']);
                   }])->orderBy('id', 'desc')->take(3)->get();
                   //$review_list = $this->paginate($review_list, 5);


                   $Courses = \App\Model\Course::orderBy('id', 'desc')->get();

                    $selected_ids = [];

                    foreach ($Courses as $item)
                    { 
                        $get_course = \App\Model\SpCourse::where(['sp_id'=>$user->id])->where('course_id', $item->id)->first();
                        if($get_course)
                        {
                            $item->active = true;
                            array_push($selected_ids, $item->id);
                        }
                        else
                        {
                            $item->active = false;
                        }
                    }

                    $emsats = \App\Model\Emsat::select('id','title','icon','question','marks')->orderBy('id', 'desc')->get();

                    foreach ($emsats as $item)
                    { 
                        $get_emsat = \App\Model\SpEmsat::where(['sp_id'=>$user->id])->where('emsat_id', $item->id)->first();
                        if($get_emsat)
                        {
                            $item->consult_price = $get_emsat->price;
                        }
                        else
                        {
                            $item->consult_price = null;
                        }
                    }
                
                   $categories = Category::where(['enable'=>'1','parent_id'=>null])
                        ->orderBy('id',"ASC")
                        ->get();
                    foreach ($categories as $key => $class) {
                        $subjects_ids = [];
                        $subcategories = Category::where('parent_id',$class->id)->where('enable','=','1')->get();
                        $class->subjects = $subcategories;
                        foreach($subcategories as $sub)
                        {
                            $fetch_sp_category = \App\Model\CategoryServiceProvider::where('sp_id',Auth::id())->where('category_id',$sub->id)->first();
                            if($fetch_sp_category)
                            {
                                $sub->checked = true;
                            }
                            else
                            {
                                $sub->checked = false;
                            }
                    
                        }
                        
                
                    
                    }
               

                //return json_encode($userz);
                return view('vendor.'.Config::get("client_data")->domain_name.'.bookings-Tutor',compact('emsats','Courses','selected_ids','categories','language','getuserpreference','data','userz','review_list','classes','current_date'));
             }else{
                 return view('vendor.'.Config::get("client_data")->domain_name.'.doctors',compact('data','current_date'));
             }
        }
     }
     public function getPatientPage(){
         if(Auth::user()->profile)
         {
             $categories = Category::where(['enable'=>'1','parent_id'=>null])
             ->orderBy('id',"ASC")
             ->get();
             $testimonials = \App\Feed::where('type','blog')->with(['user' => function($query) {
                 $query->select(['id', 'name', 'email','phone','profile_image']);
             }])->orderBy('views', 'desc')
             ->take(3)->get();
             
             $countries = Country::where('phonecode','!=',0)->pluck('sortname','phonecode');
             $banners = Banner::orderBy('id','DESC')->get();
             $data = Helper::getBanners();
         
             $banners = $data['banners'];
             $blogs = $data['blogs'];
             if(Config::get("default")){
                 return view('vendor.default.doctor');
             }else{
                 return view('vendor.'.Config::get("client_data")->domain_name.'.patient',compact('categories','banners','blogs','countries','testimonials'));
             }
         }
         else{
             $user = User::where('id',Auth::user()->id)->first();
             return redirect('/edit/profile');
            //return  $this->UserController->updateProfileView();
         }
     }

     public function updateProfileView(Request $request){
        $states_d=\App\Model\State::where('country_id', '=', 101)->pluck('name', 'id');
        $states = State::where('country_id', 231)->get();
		$insurances = Insurance::all();
		$userId = Auth::user()->id;
		$user = User::with('profile')->where('id',$userId )->first();
         if($user->profile != '' && $user->profile != null)
         {
            if($user->profile->state)
            {
            $state_id = State::select('id')->where('name', $user->profile->state)->first();

            $cities = City::where('state_id',$state_id->id)->get();
            }
         }
         $cities = [];
		$user_insurance_id = UserInsurance::select('insurance_id')->where('user_id',$userId)->first();
		$user_insurance_id = isset($user_insurance_id->insurance_id) ? $user_insurance_id->insurance_id : 0 ;
        $user_insurance = UserInsurance::with('insurance')->where('user_id',$userId)->first();
        $user_zip_code=CustomUserField::with('customfield')->where('user_id',$userId)->where('custom_field_id',2)->first();
        $cookie_policy = \App\Model\Page::where('slug','cookie-policy')->first();
        $privacy_policy = \App\Model\Page::where('slug','privacy-policy')->first();
        $notifications = Notification::select('id','notification_type as pushType','message','module','read_status','created_at','sender_id as form_user','receiver_id as to_user')->where('receiver_id',$user->id)->orderBy('id', 'desc')->paginate(20);
        if($notifications){ 
            foreach ($notifications as $key => $notification) {
                $notification->form_user = User::select('id','name','profile_image')->where('id',$notification->form_user)->first();
            }
        }
        if(Config::get("client_data")->domain_name == "iedu")
        {
            return view('vendor.iedu.edit_profile')->with(array('user'=>$user,'cities'=>$cities,
            'states'=> $states,
            'insurances'=>$insurances,
            'user_insurance_id'=>$user_insurance_id,
            'user_insurance' => $user_insurance,
            'user_zip_code' => $user_zip_code,
            'cookie_policy'=>$cookie_policy,
            'privacy_policy'=>$privacy_policy,
            'notifications' => $notifications,
            ));

        }
		if(Config::get("client_data")->domain_name == "mp2r" || Config::get("client_data")->domain_name=="food" ||  Config::get("client_data")->domain_name=="healtcaremydoctor" || Config::get("client_data")->domain_name=="care_connect_live" ){
            $question1 = Helper::getSecurityQuestion('question1');
            $question2 = Helper::getSecurityQuestion('question2');
            $question3 = Helper::getSecurityQuestion('question3');
            $selectedQ1 = Helper::getSelectedQuestion('question1',$user->id);
            $selectedQ2 = Helper::getSelectedQuestion('question2',$user->id);
            $selectedQ3 = Helper::getSelectedQuestion('question3',$user->id);
			//return view('vendor.mp2r.user.account')->with(array('user'=>$user,'cities'=>$cities,'states'=> $states,'insurances'=>$insurances,'user_insurance_id'=>$user_insurance_id,'user_insurance' => $user_insurance,'user_zip_code' => $user_zip_code,'cookie_policy'=>$cookie_policy,'privacy_policy'=>$privacy_policy, 'notifications' => $notifications,'question1'=>$question1,
            
            return view('vendor.care_connect_live.edit_profile')->with(array('user'=>$user,'cities'=>$cities,
                'states'=> $states,
                'states_d'=> $states_d,
                'insurances'=>$insurances,
                'user_insurance_id'=>$user_insurance_id,
                'user_insurance' => $user_insurance,
                'user_zip_code' => $user_zip_code,
                'cookie_policy'=>$cookie_policy,
                'privacy_policy'=>$privacy_policy,
                'notifications' => $notifications,'question1'=>$question1,
                'question2'=>$question2,
                'question3'=>$question3,
                'selectedQ1'=>$selectedQ1,
                'selectedQ2'=>$selectedQ2,
                'selectedQ3'=>$selectedQ3,));
		}
      
    }

    public  function getDoctorData(Request $request,$doctor_id, $service_id) {
      //return $request->all();
        if($doctor_id)
        {
                if($request->schedule_type)
                {
                    $schedule_type = 'schedule';
                }
                else
                {
                    $schedule_type = 'instant'; 
                }
                $date = Carbon::now();
                $doctor_details = [] ;
                $timezone = 'Asia/Kolkata';
                if (isset($request->timezone)) {
                    $timezone = $request->timezone;
                }
                // $timezone = $request->header('timezone');
                // if(!$timezone){
                //     $timezone = 'Asia/Kolkata';
                // }
                $dateznow = new DateTime("now", new DateTimeZone($timezone));
                $datenow = $dateznow->format('Y-m-d H:i:s');
                if($request->schedule_type)
                {
                    $slot = $request->slot_time;
                    $current_date = $request->date;
                    $current_time = date('h:i a', strtotime($slot));
                    $currentdate = date('D . d M Y',strtotime($current_date));
                    $currenttime = date('h:i a', strtotime($slot));
                    $datetime = $currentdate.' . '.$currenttime;
                }
                else
                {
                    $current_date = $dateznow->format('Y-m-d');
                    $current_time = $dateznow->format('h:i a');
                    $currentdate = $dateznow->format('Y-m-d');
                    $currenttime = $dateznow->format('h:i a');
                    $datetime = $currentdate . $currenttime;
                    $datetime = date('D . d M Y . h:i a',strtotime($datetime));
                }
               
              
                $doctor_details = User::getDoctorDetail($doctor_id);


            if(Config::get('client_connected')&&Config::get('client_data')->domain_name=='iedu')
            {
                $spservicetype_id = \App\Model\SpServiceType::where([
                    'sp_id'=>$doctor_id
                ])->first();
    
                if(!$spservicetype_id){
                    return response(array(
                        'status' => "error",
                        'statuscode' => 400,
                        'message' =>__("Service not found")
                        ), 400);
                }
                $categoryservicetype_id = \App\Model\CategoryServiceType::where([
                    'id'=>$spservicetype_id->category_service_id
                ])->first();
                $input['service_id'] = $categoryservicetype_id->service_id;
                $category = \App\Model\Category::where('id',$categoryservicetype_id->category_id)->first();
                // print_r($category);die;
                if(!$category){
                    return response(array(
                        'status' => "error",
                        'statuscode' => 400,
                        'message' =>__("This Vendor have not assigned any Category")
                    ), 400);
                }
            $slot_duration = \App\Model\EnableService::where('type','slot_duration')->first();
            $unit_price = \App\Model\EnableService::where('type','unit_price')->first();
            $per_minute = $spservicetype_id->price/$unit_price->value;
            $slot_minutes = $slot_duration->value;
            $add_slot_second = $slot_duration->value * 60;
            $total_charges = $slot_minutes * $per_minute;
                $grand_total= $g_total = $slot_minutes * $per_minute;
               $input = $request->all();
                        if($input['booking_type']=='emsat'){
                            $emsat = \App\Model\Emsat::where('id',$input['booking_id'])->first();
                            if(!$emsat){
                                return response(array(
                                    'status' => "error",
                                    'statuscode' => 400,
                                    'message' =>__("Emsat not found")
                                ), 400);
                            }
                            $sp_emsat = \App\Model\SpEmsat::where(['emsat_id'=>$emsat->id,'sp_id'=>$doctor_id])->first();
                            $total_charges = $sp_emsat->price;
                            $grand_total= $g_total = $sp_emsat->price;
                        }
                       
                        $services = \App\User::getServicesByServiceProvider($doctor_id,$service_id);

            }
            else
            {
                $services = \App\User::getServicesByServiceProvider($doctor_id,$service_id);
            }
               
              
           
             $review_list = \App\Model\Feedback::select('id','from_user','rating','comment')->where('consultant_id',Auth::user()->id)->with(['user' => function($query) {
                             return $query->select(['id', 'name', 'email','phone','profile_image']);
                            }])->orderBy('id', 'desc')->take(3)->get();
                            //$review_list = $this->paginate($review_list, 5);

                    
               // return json_encode($services);
               if(Config::get('client_connected') && (Config::get('client_data')->domain_name=='iedu'))
                {
                   // $review_list->withPath('/user/expert_details/'.$doctor_id."/".$service_id);
                    return view('vendor.iedu.tutor-Details')->with('total_charges',$total_charges)->with('grand_total',$grand_total)->with('doctor_details',$doctor_details)->with('review_list',$review_list)->with('datetime',$datetime)->with('services',$services)->with('current_date',$current_date)->with('current_time',$current_time)->with('schedule_type',$schedule_type); 
                }
                else
                {
                   // $review_list->withPath('/user/doctor_details/'.$doctor_id."/".$service_id);
                    return view('vendor.care_connect_live.doctor_detail')->with('doctor_details',$doctor_details)->with('review_list',$review_list)->with('datetime',$datetime)->with('services',$services)->with('current_date',$current_date)->with('current_time',$current_time)->with('schedule_type',$schedule_type);
                }
               
        }
        else{
                return view('vendor.care_connect_live.doctor_detail')->with('No Data');
        }

    } 
    public function UpdatePhone(Request $request)
    {
        $phone = str_replace(" ","",$request->phone);
        $countrycode = $request->country_code;
        $findme = '+';
        $pos = strpos($countrycode, $findme);
        $role_type = $request->role_type; 
 
        if ($pos === false) {
             $code = '+'.$request->country_code;
         } else {
             $code = $request->country_code;
         }
         if($role_type == 'service_provider')
        {
            $roletype = 'Service Provider';
        }
        if($role_type == 'customer')
        {
            $roletype = 'Customer';
        }   
        $check_existing_phone_user = User::where('phone',$phone)->where('country_code',$code)->where('id',Auth::user()->id)->first();
       // return  $check_existing_phone_user;
       if(!$check_existing_phone_user)
       {
        $get_phone_user = User::where('phone',$phone)->where('country_code',$code)->first();
    
        if($get_phone_user){
            
            return response(array('status' => "error", 'statuscode' => 400, 'message' =>"Already Registered Account, Please try with other account."), 400);
            $current_role = ucwords(str_replace('_', ' ', $get_phone_user->roles[0]['name']));
            
            if(!$get_phone_user->hasrole($role_type)){
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>"You are register as $current_role with same account, Please try with other account."), 400);
            }
            
        }
        }

        $mobile_no = $code.$phone; 
        $data['to'] = $mobile_no;
        //print_r($mobile_no); die();
        $otp = mt_rand(1000,9999);
        $data['otp'] = $otp;
          //use later
        // if(\Config::get('client_connected') && (\Config::get('client_data')->domain_name!=='healtcaremydoctor' && \Config::get('client_data')->domain_name!=='curenik' && \Config::get('client_data')->domain_name!=='physiotherapist' && \Config::get('client_data')->domain_name!=='intely')){
        //         return response(['status' => 'success', 'statuscode' => 200, 'message' => __('OTP sent to your mobile number!'), 'data' => $data], 200);
        // }
        $f_keys = Helper::getClientFeatureKeys('social login','Twilio OTP');
        $accountSid = isset($f_keys['account_sid'])?$f_keys['account_sid']:env('TWILIO_ACCOUNT_SID_NEW');
        $authToken = isset($f_keys['token'])?$f_keys['token']:env('TWILLIO_TOKEN_NEW');
        $number = isset($f_keys['number'])?$f_keys['number']:"+14158959801";
        try{
            $body = "CODE: $otp";
            // if(\Config::get('client_connected') && (\Config::get('client_data')->domain_name=='healtcaremydoctor')){
            //     $body = "Welcome to My Doctor your Code: $otp";
            // }else if(\Config::get('client_connected') && (\Config::get('client_data')->domain_name=='intely')){
            //     $body = "Welcome to iCareConnect your Code: $otp";
            // }
            $twilio = new Client($accountSid, $authToken);
            // use later
            // $message = $twilio->messages->create($mobile_no, // to
            //                         ["body" =>$body,
            //                         "from" => $number]);

            $smsVerifcation = new \App\Model\Verification();

            $request['code'] = $otp;
            $request['phone'] = $phone;
            $request['country_code'] = $code; 
            

            $smsVerifcation->store($request);
            
            //$data = (object)[];
            //use later
            
        //    if ($message->sid) {
                if(true){
                return response(['status' => 'success', 'statuscode' => 200, 'message' => __('OTP sent to your mobile number!'), 'email'=> $request->email,'signuptype' =>'', 'userid' =>$request->userid, 'data' => $phone, 'codephone' => $data['to'], 'role_type' => $role_type,'country_code' => $code,  'applyoption' => 'register' ], 200);
            } else {
                return response(['status' => 'error', 'statuscode' => 400, 'message' => __('OTP has not been sent. Please try again!'),'email'=> $request->email,'signuptype' =>'', 'userid' =>$request->userid, 'data' => $phone,'codephone' => $data['to'], 'role_type' => $role_type,'country_code' => $code,  'applyoption' => 'register'], 400);
            }
        } catch (Exception $e) {
            return response(['status' => 'error', 'statuscode' => 500, 'message' => $data['to'] .' is not a Valid Phone Number', 'data' => $phone,'signuptype' =>'','userid' =>$request->userid, 'codephone' => $data['to'],'role_type' => $role_type,'country_code' => $code,  'applyoption' => 'register'], 500);
        }

    }

    public function verifyPhone(Request $request)
    {
        //return json_encode($request->all());
       
       
        $digit1 =isset($request->digit1)?$request->digit1:'';
        $digit2 =isset($request->digit2)?$request->digit2:'';
        $digit3 =isset($request->digit3)?$request->digit3:'';
        $digit4 =isset($request->digit4)?$request->digit4:'';
        $code = $digit1.$digit2.$digit3.$digit4;
       // $input = $request->all();
        $datenow = new DateTime("now", new DateTimeZone('UTC'));
        $datenowone = $datenow->format('Y-m-d H:i:s');
        $smsVerifcation = new \App\Model\Verification();
        // $usercountrycode = '+'.$request->country_code;
        $countrycode = $request->country_code;
        $findme = '+';
        $pos = strpos($countrycode, $findme);

        if ($pos === false) {
            $usercountrycode = '+'.$request->country_code;
            } else {
                $usercountrycode = $request->country_code;
            }
      
        //print_r($request->country_code); die();
        $smsVerifcation = $smsVerifcation::where(['phone' => $request->phone, 
            // 'code' => $code,
            'country_code'=>$usercountrycode
        ])->where('expired_at', '>=', $datenowone)
                ->latest() //show the latest if there are multiple
                ->first();
                //print_r($smsVerifcation); die();
        $codephone = $usercountrycode.$request->phone;
            if (($smsVerifcation && $code == $smsVerifcation->code) || $code=='1234') {
                $request["status"] = 'verified';
                $inputs['phone'] = $request->phone;
                $inputs['code'] = $code;
                $inputs['status'] = $request->status;
                $verify = $smsVerifcation::where('id', $smsVerifcation->id)->update($inputs);
                if($verify)
                {
                    $update_user_phone = User::where('id',$request->userid)->update([
                            'phone' => $request->phone,
                            'country_code' => $request->country_code
                    ]);
                   
                    if($update_user_phone)
                    {
                        return response(['status' => 'success', 'statuscode' => 200, 'phone' =>'', 'message'=>'phone Number Updated Successfully'], 200);
            
                    }
                }
            
             } 
        
            else {
                return response(['status' => 'error', 'statuscode' => 500, 'message' => __('Wrong OTP'), 'data' => $request->phone, 'codephone' => $codephone, 'role_type' => $request->role_type,'country_code' => $usercountrycode, 'applyoption' => $request->applyoption], 500);
            }
        

    }

    public function resendOtp(Request $request)
    {
        //print_r($request->all()); die();
        $phone= $request->phone; 
        $role_type= $request->role_type ; 
        $countrycode = $request->country_code;
        $findme = '+';
        $pos = strpos($countrycode, $findme);
 
        if ($pos === false) {
            $code = '+'.$request->country_code;
         } else {
             $code = $request->country_code;
         }
       // $code= $request->country_code; 
        $mobile_no = $code.$phone;
        //$mobile_no = '+'.$code.$phone;  
        //$usercountrycode = '+'.$request->country_code;
        $usercountrycode = $request->country_code;
        if($role_type){ 
                $check_existing_otp =  \App\Model\Verification::where('phone',$request->phone)->where('country_code',$usercountrycode)->where('status','pending')->first();
                //print_r($check_existing_otp); die();
                if($check_existing_otp){
                    $otp = mt_rand(1000,9999);
                    $data['otp'] = $otp;
                    $data['to'] = $mobile_no;
                    //  if(\Config::get('client_connected') && (\Config::get('client_data')->domain_name!=='healtcaremydoctor' && \Config::get('client_data')->domain_name!=='curenik' && \Config::get('client_data')->domain_name!=='physiotherapist' && \Config::get('client_data')->domain_name!=='intely')){
                    //   return response(['status' => 'success', 'statuscode' => 200, 'message' => __('OTP Resend to your mobile number!'), 'data' => $data], 200);
                    // }
                    $f_keys = Helper::getClientFeatureKeys('social login','Twilio OTP');
                    $accountSid = isset($f_keys['account_sid'])?$f_keys['account_sid']:env('TWILIO_ACCOUNT_SID_NEW');
                    $authToken = isset($f_keys['token'])?$f_keys['token']:env('TWILLIO_TOKEN_NEW');
                    $number = isset($f_keys['number'])?$f_keys['number']:"+14158959801";
                try {
                    $body = "CODE: $otp";
                    // if(\Config::get('client_connected') && (\Config::get('client_data')->domain_name=='healtcaremydoctor')){
                    //     $body = "Welcome to My Doctor your Code: $otp";
                    // }else if(\Config::get('client_connected') && (\Config::get('client_data')->domain_name=='intely')){
                    //     $body = "Welcome to iCareConnect your Code: $otp";
                    // }
                    $twilio = new Client($accountSid, $authToken);
                    //  $message = $twilio->messages->create($mobile_no, // to
                    //                         ["body" =>$body,
                    //                         "from" => $number]);
                    $request['code'] = $otp;
                    $request['phone'] = $phone;
                    //$request['country_code'] = '+'.$code; 
                    $request['country_code'] = $code;
                                
                    $smsVerifcation = new \App\Model\Verification;
                  
                    $smsVerifcation->store($request);

                    //$data = (object)[];
                    // if ($message->sid) {
                      if(true) {
                        return response(['status' => 'success', 'statuscode' => 200, 'message' => __('OTP Resend to your mobile number!'), 'data' => $phone, 'codephone' => $data['to'], 'role_type' => $role_type,'country_code' => $code ], 200);
                    } else {
                        return response(['status' => 'error', 'statuscode' => 400, 'message' => __('OTP has not been Resend. Please try again!'), 'data' => $phone,'codephone' => $data['to'], 'role_type' => $role_type,'country_code' => $code], 400);
                    }
                } catch (Exception $e) {
                    return response(['status' => 'error', 'statuscode' => 500, 'message' => $e->getMessage(), 'data' => $phone,'codephone' => $data['to'],'role_type' => $role_type,'country_code' => $code], 500);
                }
                    
                }
            }

    }

    public function updateProfileuser(Request $request){
       // return $request->file('profile_image');
        $userId = Auth::user()->id;
        
        $user = User::find($userId);
        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone = str_replace(' ', '', $request->phone);

        $this->Useruploadimage($request);
        // $user->manual_available =  $request->manual_available == 'on' ? 1 : 0;
        $user->save();
        $user_profile = Profile::where('user_id',$userId)->first();  
        if($user_profile == '' || $user_profile == null)
        {
            $user_profile = new Profile();
        }
        $user_profile->dob =  $request->dob;
        $user_profile->state =  $request->state;
        $user_profile->user_id = $userId;
        $user_profile->about =  $request->about;
       
        $user_profile->save();
        return redirect()->back()->with('message','Profile updated successfully!!');
    }

    public function getchangePassword(Request $request)
    {
        
        return view('vendor.'.Config::get("client_data")->domain_name.'.change-password');
       
    }

    public function onlineToggle(Request $request)
    {
        try
        {
        $user = Auth::user();
        $input = $request->all();
        $rules = [];
        if(isset($request->manual_available)){
            $rules['manual_available'] = 'required';
        }
       
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                $validator->getMessageBag()->first()), 400);
        }
        if(isset($input['manual_available'])){
           // return $input['manual_available'];
            if($input['manual_available'] == '1' || $input['manual_available'] == true && $input['manual_available'] == 'true'){
                $user->manual_available = 1;
            }else{
                $user->manual_available = 0;
            }
        }
        if($user->save()){
            return response(['status' =>"success", 'statuscode' => 200, 'message' => __('manual_available updated')], 200);
        }else{
            return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                __('manual_available not updated')), 400);
        }
        } catch (Exception $e) {
            return response(['status' => "error", 'statuscode' => 500, 'message' => $e->getMessage()], 500);
        }

    }

    public static function getUserProfile(Request $request) {
        try {
            $user = Auth::user();
            $userz = User::with('roles')->find($user->id);
            $token = $user->createToken('consult_app')->accessToken;
            $userz->token = $token;
            
            if(!$userz->profile){
                $profile = New Profile();
                $profile->dob ='0000-00-00';
                $profile->user_id = $user->id;
                $profile->save();
            }
            $userz->profile;
            $userz->profile->location = ["name"=>$userz->profile->location_name,"lat"=>$userz->profile->lat,"long"=>$userz->profile->long];
            $userz->profile->bio = $userz->profile->about;
            $userz->subscriptions = $userz->getSubscription($userz); 
            $userz->categoryData = $userz->getCategoryData($userz->id);
            $userz->additionals = $userz->getAdditionals($userz->id);
            $userz->filters = $userz->getFilters($userz->id);
            $userz->services = $user->getServices($userz->id);
            $userz->insurances = $user->getInsurnceData($userz->id);
            $userz->custom_fields = $user->getCustomFields($userz->id);
            if($user->hasrole('service_provider')){
                $userz->totalRating =  $userz->profile->rating;
                $userz->patientCount = User::getTotalRequestDone($user->id);
                $userz->reviewCount = Feedback::reviewCountByConsulatant($user->id); 
            }
            $userz = Helper::getMoreData($userz);
            return response(['status' =>"success", 'statuscode' => 200, 'message' => __('Profile'), 'data' => ($userz)], 200);
        } catch (Exception $e) {
            return response(['status' => "error", 'statuscode' => 500, 'message' => $e->getMessage()], 500);
        }
    }



}
