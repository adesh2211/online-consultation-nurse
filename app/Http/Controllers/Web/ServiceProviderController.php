<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Config;
use App\Model\ServiceProviderSlot;
use Auth;
use  App\User;
use Socialite, Exception;
use Intervention\Image\ImageManager;
use Carbon\Carbon;
use App\Model\Insurance;
use App\Model\UserInsurance;
use App\Model\City;
use App\Model\CategoryServiceProvider;
use App\Model\State;
use App\Model\Category;
use App\Model\EnableService;
use App\Notification;
use Illuminate\Support\Facades\Validator;
use App\Model\ServiceProviderSlotsDate;
use DateTimeZone;
use App\Model\Request as RequestData;
use App\Model\CustomUserField;
use App\Model\SpServiceType;
use App\Model\RequestHistory;
use App\Model\CustomInfo;
use App\Model\Profile;
use DB;
use App\Helpers\Helper;
use App\Model\Feedback;
use DateTime;
use Carbon\CarbonPeriod;
use App\Model\Service;
use App\Model\FilterType;
use App\Model\ServiceProviderFilterOption;
use App\Model\PreScription,App\Model\PreScriptionMedicine,App\Model\Image as ModelImage;
use App\Model\CategoryServiceType;
use Illuminate\Support\Facades\Auth as FacadesAuth;

class ServiceProviderController extends Controller
{
    public function __construct(UserController $UserController)
    {
        $this->UserController = $UserController;
       
    }
    public function paginate($items, $perPage = 20, $page = null, $options = [])
    {
		// use Illuminate\Pagination\Paginator;
		// use Illuminate\Support\Collection;
		// use Illuminate\Pagination\LengthAwarePaginator;
        $page = $page ?: (\Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof \Illuminate\Support\Collection ? $items : \Illuminate\Support\Collection::make($items);
        return new \Illuminate\Pagination\LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }

	public function getProfile(){
		$user = Auth::user();
		if(Config::get("default")){
            return view('vendor.default.profile');
        }else{
            return view('vendor.'.Config::get("client_data")->domain_name.'.profile')->with(['user'=>$user]);
        }
	}
	public function getManageAvailPage()
	{

		return redirect('Sp/manage_availibilty_new');

		$states = State::where('country_id', 231)->get();
		$insurances = Insurance::all();
		$userId = Auth::user()->id;
		$user = User::with('profile')->where('id', $userId)->first();
		$state_id = State::select('id')->where('name', $user->profile->state)->first();
		$cities = City::where('state_id', $state_id->id)->get();
		$user_insurance_id = UserInsurance::select('insurance_id')->where('user_id', $userId)->first();
		$user_insurance_id = $user_insurance_id->insurance_id;
		$user_insurance = UserInsurance::with('insurance')->where('user_id',$userId)->first();
        
        $user_zip_code=CustomUserField::with('customfield')->where('user_id',$userId)->where('custom_field_id',3)->first();

        $cookie_policy = \App\Model\Page::where('slug','cookie-policy')->first();
        $privacy_policy = \App\Model\Page::where('slug','privacy-policy')->first();
        
		// Get all the brands from the Brands Table.
		$parentCategories = Category::with('subcategory')->where('name', '!=', 'Find Local Resources')->where('parent_id', NULL)->where('enable', '=', '1')->get();
		$notifications = Notification::select('id','notification_type as pushType','message','module','read_status','created_at','sender_id as form_user','receiver_id as to_user')->where('receiver_id',$user->id)->orderBy('id', 'desc')->paginate(20);
    	if($notifications){
    		foreach ($notifications as $key => $notification) {
	    		$notification->form_user = User::select('id','name','profile_image')->where('id',$notification->form_user)->first();
	    		$notification->to_user = User::select('id','name','profile_image')->where('id',$notification->to_user)->first();
	    		$notification->sentAt = \Carbon\Carbon::parse($notification->created_at)->getPreciseTimestamp(3);
    		}
    	}
		if (Config::get("client_data")->domain_name == "mp2r" || Config::get("client_data")->domain_name == "food") {
			$question1 = Helper::getSecurityQuestion('question1');
            $question2 = Helper::getSecurityQuestion('question2');
            $question3 = Helper::getSecurityQuestion('question3');
            $selectedQ1 = Helper::getSelectedQuestion('question1',$user->id);
            $selectedQ2 = Helper::getSelectedQuestion('question2',$user->id);
            $selectedQ3 = Helper::getSelectedQuestion('question3',$user->id);
			return view('vendor.mp2r.account.manage-availibility')->with(array(
				'user' => $user,
				'cities' => $cities,
				'states' => $states,
				'insurances' => $insurances,
				'user_insurance_id' => $user_insurance_id,
				'parentCategories' => $parentCategories,
				'user_insurance' => $user_insurance,
				'user_zip_code' => $user_zip_code,
				'cookie_policy'=>$cookie_policy,
				'privacy_policy'=>$privacy_policy,
				'notifications' => $notifications,
				'question1'=>$question1,
				'question2'=>$question2,
				'question3'=>$question3,
				'selectedQ1'=>$selectedQ1,
				'selectedQ2'=>$selectedQ2,
				'selectedQ3'=>$selectedQ3,
			));
		}
	}

	public function postManageAvailibilty(Request $request)
	{

		//dd($request->all());

		try{
			$rules = [
				'handle_type' => 'required',
				'timzone' => 'required',
				'date' => 'required',
				'interval' => 'required'
			];
			$validator = \Validator::make($request->all(), $rules);
			if ($validator->fails()) {
				return response(array('status' => "error", 'statuscode' => 400, 'message' =>
				$validator->getMessageBag()->first()), 400);
			}
			$user = Auth::user();
			$input = $request->all();
			$current_category = 1;
			$service_id = 1;
			$category = $user->getCategoryData($user->id);
			if ($category) {
				$current_category = $category->id;
			}
			$timezone = 'Asia/Kolkata';
			if (isset($request->timezone)) {
				$timezone = $request->timezone;
			}
			$duration = '60';
	        $unit_price = EnableService::where('type','unit_price')->first();
	        if($unit_price){
	            $duration = $unit_price->value * 60;
	        }

			$service = \App\Model\CategoryServiceType::where(['category_id'=>$current_category,'service_id'=>$service_id])->first();
			if($service){
				$spservicetype = \App\Model\SpServiceType::firstOrCreate([
		            'sp_id'=>$user->id,
		            'category_service_id'=>$service->id
		        ]);
		        $spservicetype->available = "1";
		        $spservicetype->minimmum_heads_up = "5";
		        if($service->price_fixed!==null){
		            $spservicetype->price = $service->price_fixed;
		        }else{
		            $spservicetype->price = 0;
		        }
		        $spservicetype->duration = $duration;
		        $spservicetype->save();
			}

			if ($input['handle_type'] == 'all_weekdays') {
				if (isset($input['interval'][0]) && isset($input['interval'][0]['seleted_start']) && isset($input['interval'][0]['seleted_end'])) {
					$weekdays = [1, 2, 3, 4, 5];
					ServiceProviderSlot::where([
						'service_provider_id' => $user->id,
						'service_id' => $service_id,
						'category_id' => $current_category,
					])->whereIn('day', $weekdays)->delete();
					foreach ($weekdays as $day) {
						foreach ($input['interval'] as $slot) {
							if (isset($slot['seleted_start']) && isset($slot['seleted_end'])) {
								$start_time = Carbon::parse($slot['seleted_start'], $timezone)->setTimezone('UTC')->format('H:i:s');
								$end_time = Carbon::parse($slot['seleted_end'], $timezone)->setTimezone('UTC')->format('H:i:s');
								$spavailability = new ServiceProviderSlot();
								$spavailability->service_provider_id = $user->id;
								$spavailability->service_id = $service_id;
								$spavailability->category_id = $current_category;
								$spavailability->start_time = $start_time;
								$spavailability->end_time = $end_time;
								$spavailability->day = $day;
								$spavailability->save();
							}
						}
					}
				}
			} else if ($input['handle_type'] == 'date'  || $input['handle_type'] == 'specific_date') {

				$date = $input['date_picker_date'];

	            // $end_date = $input['date']['full_date'].' 23:59:59';
	            // $fromUTC = Carbon::parse($from_date, $timezone)->setTimezone('UTC')->format('H:i:s');
	            // $toUTC = Carbon::parse($end_date, $timezone)->setTimezone('UTC')->format('H:i:s');
	           

				ServiceProviderSlotsDate::where([
					'service_provider_id' => $user->id,
					'service_id' => $service_id,
					'date' => $date,
					'category_id' => $current_category,
				])->delete();
				// ->where(function($query2) use ($fromUTC,$toUTC){
	   //              $query2->whereTime('start_time','>',$fromUTC);
	   //              $query2->orWhereTime('end_time','<',$toUTC);
	   //           })
				if (isset($input['interval'][0]) && isset($input['interval'][0]['seleted_start']) && isset($input['interval'][0]['seleted_end'])) {
					foreach ($input['interval'] as $slot) {
						if (isset($slot['seleted_start']) && isset($slot['seleted_end'])) {
							$start_time = Carbon::parse($slot['seleted_start'], $timezone)->setTimezone('UTC')->format('H:i:s');
							$end_time = Carbon::parse($slot['seleted_end'], $timezone)->setTimezone('UTC')->format('H:i:s');
							$spavailability = new ServiceProviderSlotsDate();
							$spavailability->service_provider_id = $user->id;
							$spavailability->service_id = $service_id;
							$spavailability->category_id = $current_category;
							$spavailability->start_time = $start_time;
							$spavailability->end_time = $end_time;
							$spavailability->date = $date;
							$spavailability->save();

							
						}
					}
				}
			}else if($input['handle_type']=='all_selected_day'){
				
				$from_date = $input['date']['full_date'].' 00:00:00';
				$date = Carbon::parse($from_date)->setTimezone('UTC')->format('Y-m-d');
				$weekMap = ['SU'=>0,'MO'=>1,'TU'=>2,'WE'=>3,'TH'=>4,'FR'=>5,'SA'=>6];
	            $day = strtoupper(substr(Carbon::parse($date)->format('l'), 0, 2));
	            $day_number = $weekMap[$day];
	            ServiceProviderSlot::where([
	                'service_provider_id'=>$user->id,
	                'service_id'=>$service_id,
	                'day'=>$day_number,
	                'category_id'=>$current_category,
	            ])->delete();
	           foreach ($input['interval'] as $slot) {
	           		if (isset($slot['seleted_start']) && isset($slot['seleted_end'])) {
		                $start_time = Carbon::parse($slot['seleted_start'],$timezone)->setTimezone('UTC')->format('H:i:s');
		                $end_time = Carbon::parse($slot['seleted_end'],$timezone)->setTimezone('UTC')->format('H:i:s');
		                $spavailability = new ServiceProviderSlot();
		                $spavailability->service_provider_id = $user->id;
		                $spavailability->service_id = $service_id;
		                $spavailability->category_id = $current_category;
		                $spavailability->start_time = $start_time;
		                $spavailability->end_time = $end_time;
		                $spavailability->day = $day_number;
		                $spavailability->save();	
	           		}
				}
			}else if($input['handle_type']=='multiple_days'){
				//dd($request->all());
				$weekMap = ['SUNDAY'=>0,'MONDAY'=>1,'TUESDAY'=>2,'WEDNESDAY'=>3,'THURSDAY'=>4,'FRIDAY'=>5,'SATURDAY'=>6];
	           foreach ($input['days'] as $key => $day) {
	           		if(isset($day['day'])){
	            	  //$day_number = $weekMap[strtoupper($day['day'])];

	           			$day_number = $day['day_count'];
	            	  
			           ServiceProviderSlot::where([
			                'service_provider_id'=>$user->id,
			                'service_id'=>$service_id,
			                'day'=>$day_number,
			                'category_id'=>$current_category,
			            ])->delete();
			           foreach ($input['interval'] as $slot) {

			           		if (isset($slot['seleted_start']) && isset($slot['seleted_end'])) {
				                $start_time = Carbon::parse($slot['seleted_start'],$timezone)->setTimezone('UTC')->format('H:i:s');
				                $end_time = Carbon::parse($slot['seleted_end'],$timezone)->setTimezone('UTC')->format('H:i:s');
				                $spavailability = new ServiceProviderSlot();
				                $spavailability->service_provider_id = $user->id;
				                $spavailability->service_id = $service_id;
				                $spavailability->category_id = $current_category;
				                $spavailability->start_time = $start_time;
				                $spavailability->end_time = $end_time;
				                $spavailability->day = $day_number;
				                $spavailability->save();	
			           		}

			           		//print_r($day_number);die;
						}
	           		}
	           }
			}else if($input['handle_type']=='block_date'){
				$from_date = $input['date']['full_date'].' 00:00:00';
				$block_date = Carbon::parse($from_date)->setTimezone('UTC')->format('Y-m-d');
				$start_time = Carbon::parse($block_date.' 00:00:00',$timezone)->setTimezone('UTC')->format('H:i:s');
                $end_time = Carbon::parse($block_date.' 23:59:59',$timezone)->setTimezone('UTC')->format('H:i:s');
                ServiceProviderSlotsDate::where([
                    'service_provider_id'=>$user->id,
                    'date'=>$block_date,
                    'service_id'=>$service_id,
                    'category_id'=>$current_category,
                ])->delete();
                $spavailability = new ServiceProviderSlotsDate();
                $spavailability->service_provider_id = $user->id;
                $spavailability->service_id = $service_id;
                $spavailability->category_id = $current_category;
                $spavailability->start_time = $start_time;
                $spavailability->end_time = $end_time;
                $spavailability->date = $block_date;
                $spavailability->working_today = 'n';
                $spavailability->save();
			}
			if(isset($input['sign_up']) && $input['sign_up']){
				$user->account_step = 4;
				$user->save();
			}
			return response(['status' => "success", 'statuscode' => 200,
	                                'message' => __('success')], 200);
		} catch (Exception $ex) {
				return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage()], 500);
			}

	}

	/*

		This function is use to show menu of service provider e.g  booking menu
	*/

	public function menu()
	{
		$request = '';
		if (Config::get("client_data")->domain_name == "mp2r" || Config::get("client_data")->domain_name == "food") {
			try {
				$user = Auth::user();
				$isAprroved = false;
				if ($user->account_verified) {
					$isAprroved = true;
				}
				$from_date = null;
				$end_date = null;
				$status = 'pending';
				$requests = [];
				$service_type = isset($request->service_type) ? $request->service_type : 'all';
				// Query 
				$per_page = (isset($request->per_page) ? $request->per_page : 10);
				//Get Only pending appointments
				$booking_appointments = \App\Model\Request::select('id', 'service_id', 'from_user', 'to_user', 'booking_date', 'created_at', 'booking_date as bookingDateUTC')
					->whereHas('sr_info', function ($query) use ($isAprroved) {
						if (!$isAprroved)
							return $query->where('account_verified', '!=', null);
					})
					->whereHas('requesthistory', function ($query) use ($status) {
						return $query->where('status', '=', 'pending');
					})
					->where('to_user', $user->id)->orderBy('id', 'desc')->paginate($per_page);

				//Get all pending appointments
				$appointments = \App\Model\Request::select('id', 'service_id', 'from_user', 'to_user', 'booking_date', 'created_at', 'booking_date as bookingDateUTC')
					->whereHas('sr_info', function ($query) use ($isAprroved) {
						if (!$isAprroved)
							return $query->where('account_verified', '!=', null);
					})->where('to_user', $user->id)->orderBy('id', 'desc')->paginate($per_page);

				// dd($requests);
				$requests = self::modifyData($booking_appointments);
				$appointments = self::modifyData($appointments);

				//function for get getPatientList 

				$patients = self::getPatientList();
				//Get user reviews 
				$reviews = User::getUserReview($user->id);
				// dd($reviews);

				return view('vendor.mp2r.account.menu')->with(array('requests' => $requests, 'appointments' => $appointments, 'patients' => $patients, 'isAprroved' => $isAprroved, 'reviews' => $reviews));
			} catch (Exception $ex) {
				return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage()], 500);
			}
		}
	}
	public static function getPatientList()
	{
		try {
			$user = Auth::user();
			$per_page = (isset($request->per_page) ? $request->per_page : 10);
			$patients = RequestData::select('*', 'booking_date as last_consult_date')->with('cus_info')
				->where('to_user', $user->id)
				->groupBy('from_user')->orderBy('id', 'asc')
				->cursorPaginate($per_page);
			foreach ($patients as $key => $patient) {
				$patient->id = $patient->cus_info->id;
				$patient->name = $patient->cus_info->name;
				$patient->email = $patient->cus_info->email;
				$patient->profile_image = $patient->cus_info->profile_image;
				unset($patient->cus_info);
				unset($patient->booking_date);
				unset($patient->from_user);
				unset($patient->to_user);
			}
			$after = null;
			if ($patients->meta['next']) {
				$after = $patients->meta['next']->target;
			}
			$before = null;
			if ($patients->meta['previous']) {
				$before = $patients->meta['previous']->target;
			}
			$per_page = $patients->perPage();
			return $patients;
		} catch (Exception $ex) {
			return array();
		}
	}

	public function getPlanPage(Request $request){
		$user = Auth::user();
		$user = Helper::getCurrentSubscription($user);
		// print_r($user);die;
		return view('vendor.mp2r.account.upgrade',compact('user'));
	}

	/*
		Function for modify appointment based data..
		@Params $request
	*/

	public static function modifyData($requests)
	{
		$timezone = 'Asia/Kolkata';
		foreach ($requests as $key => $request_status) {
			$request_status->is_prescription = false;
			if ($request_status->prescription) {
				$request_status->is_prescription = true;
				unset($request_status->prescription);
			}
			$dateznow = new DateTime("now", new DateTimeZone('UTC'));
			$datenow = $dateznow->format('Y-m-d H:i:s');
			$next_hour_time = strtotime($datenow) + 3600;
			$date = Carbon::parse($request_status->booking_date, 'UTC')->setTimezone($timezone);
			$request_status->booking_date = $date->isoFormat('D MMMM YYYY, h:mm:ss a');
			$request_status->time = $date->isoFormat('h:mm a');
			$request_history = $request_status->requesthistory;
			if ($request_history) {
				$request_status->price = $request_history->total_charges;
				$request_status->duration = $request_history->duration;
				$request_status->status = $request_history->status;
				$request_status->schedule_type = $request_history->schedule_type;
			}
			if (strtotime($request_status->bookingDateUTC) >= $next_hour_time && $request_status->status == 'pending') {
				$request_status->canReschedule = true;
				$request_status->canCancel = true;
			} else {
				$request_status->canReschedule = false;
				$request_status->canCancel = false;
			}
			$request_status->extra_detail = RequestData::getExtraRequestInfo($request_status->id, $timezone);
			$request_status->service_type = $request_status->servicetype->type;
			$request_status->from_user = User::select('name', 'email', 'id', 'profile_image', 'phone', 'country_code')->where('id', $request_status->from_user)->first();
			$request_status->to_user = User::select('name', 'email', 'id', 'profile_image', 'phone', 'country_code')->where('id', $request_status->to_user)->first();
			$request_status->to_user->categoryData = $request_status->to_user->getCategoryData($request_status->to_user->id);
			unset($request_status->requesthistory);
			// unset($request_status->service_id); 
			unset($request_status->servicetype);
		}
		return $requests;
	}

	public function serviceProviderCategoryUpdate(Request $request)
	{
		$user = Auth::user();
		$category = CategoryServiceProvider::where('sp_id', $user->id)->first();
		$category->category_id = $request->category_id;
		$category->save();
		return redirect()->back()->with('message', 'Category Updated successfully!!');
	}

	/*

    user image update

    */

    public function Serviceuploadimage(Request $request){



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

    Doctor Booking Appointment With Patient

    */

    public function Appointment(Request $request){

    	$bookingRequest = RequestData::with('servicetype')
				    	->with('cus_info')->with('requesthistory')
				    	->where('to_user',Auth::user()->id)
				    	->whereHas('requesthistory', function($query) {
        					$query->where('status', '=', 'pending')->orWhere('status', '=', 'in-progress');
    					})->get();
    	return view('vendor.mp2r.account.booking_request',compact('bookingRequest'));
    }
    public function getPatientListPage(Request $request){
		$patients = self::getPatientList();
    	return view('vendor.mp2r.account.patient_list',compact('patients'));
    }
    public function getReports(Request $request){
        $user = Auth::user();
    	$requests_data = \App\Model\Request::getReqAnaliticsBySrPro($user->id);
        $revenu_res = \App\Model\Transaction::getRevenueBySrPro($user);
        $requests_data['totalRevenue'] = $revenu_res['totalRevenue'];
        $requests_data['monthlyRevenue'] = $revenu_res['monthlyRevenue'];
    //     foreach ($requests_data['monthlyRevenue'] as  $monthlyRevenue) {
    //     	# code...
    //     }
    //    	echo '<pre>';
  		// print_r($requests_data);die;
    //   	echo '</pre>';
    	return view('vendor.mp2r.account.reports',compact('requests_data'));
    }
    public function getReviews(Request $request){
    	$review_list = \App\Model\Feedback::select('id','from_user','rating','comment')->where('consultant_id',Auth::user()->id)->with(['user' => function($query) {
                            return $query->select(['id', 'name', 'email','phone','profile_image']);
        }])->orderBy('id', 'desc')->paginate(10);
    	return view('vendor.mp2r.account.review',compact('review_list'));
    }
    public function getTermsConditions(Request $request){
    	$data = \App\Model\Page::where('slug','term-and-conditions')->first();
    	return view('vendor.mp2r.account.termsconditions',compact('data'));
    }

    public function chat(Request $request){
    	

    	$receiverInfo=User::find($_GET['receiver_id']);


    	return view('vendor.mp2r.account.chat_new',compact('receiverInfo'));
    }

    public function serviceProviderBookingStatus(Request $request,$id,$status){


    	
    	RequestHistory::where('id',$id)->update(['status' => $status]);

    	return redirect()->route('SPAppointment')->with('message','Booking '.$status.' Successfully');
    }


    public function UserverifyEligibility(Request $request){
        try{
            $input = $request->all();
            $rules = ['request_id'=>'required|exists:requests,id'];
            $validation = \Validator::make($input,$rules);            
            if($validation->fails()){
                return response(array('status' => 'error', 'statuscode' => 400, 'message' =>
                    $validation->getMessageBag()->first()), 400);
            }
            $request_data = \App\Model\Request::where('id',$request->request_id)->first();

            $ins_info = CustomInfo::where([
                'ref_table_id'=>$request_data->from_user,
                'ref_table'=>'users',
                'info_type'=>'insurance_verification'
            ])->orderBy('id','DESC')->first();
            $insurance_query = new \StdClass();
            if($ins_info){
                $insurance_query = json_decode($ins_info->raw_detail);
                if(isset($insurance_query->insurance_id)){
                     $insurance = \App\Model\Insurance::where(['id'=>$insurance_query->insurance_id])->first();
                     if($insurance){
                        $insurance_query->carrier_code = $insurance->carrier_code;
                     }
                }
            }
            $insurance_query->npi = $request_data->sr_info->npi_id;
            $http_build_query =  http_build_query($insurance_query);
            // print_r($http_build_query);die;
            $url = "https://api.doradosystems.com/rt/validate";
            $api_key = "dCdWheAbtdfdOdEcDbDbCeFbDegbHbk";
            $curl = curl_init();
            curl_setopt_array($curl, array(
              CURLOPT_URL => $url."?api_key=$api_key&".$http_build_query,
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => "",
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 30,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => "POST",
              CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "content-type: application/json",
              ),
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if ($err) {
                return response(array(
                    'status' => 'error',
                    'statuscode' => 400,
                    'message' =>$err)
                ,400);
            } else {
                $result = json_decode($response);
                if(isset($result->Loop_2000A->Loop_2100A) && isset($result->Loop_2000A->Loop_2100A->PER_InformationSourceContactInformation_2100A)){
                    // $datenow = new DateTime("now", new DateTimeZone('UTC'));
                    // $datenowone = $datenow->format('Y-m-d H:i:s');
                    // $user = Auth::user();
                    // $user->insurance_verified = $datenowone;
                    // $user->save();
                    return response(array(
                    'status' => 'success',
                    'statuscode' => 200,
                    'data'=>[
                        'insurance'=>$result->Loop_2000A->Loop_2100A->PER_InformationSourceContactInformation_2100A
                        ],
                    'message' =>'Insurance Verified')
                ,200);
                }else{
                    return response(array(
                    'status' => 'error',
                    'statuscode' => 400,
                    'message' =>'Insurance Not Verified')
                ,400);
                }
            }
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage()], 500);
        } 
    }


    public function ServiceProviderFilter(Request $request){

    	$categories = Category::where(array('enable'=>1,'parent_id'=>null))->get();

    	return view('vendor.mp2r.account.service_provider_filter',compact('categories'));
    }

    public function ServiceProviderCategoryFilter(Request $request,$id){

        $Get_Category=Category::find($id);

        $Sp_Detail=CategoryServiceProvider::with('users','users.Customuserfield')->where('category_id',$id)->paginate(5);

        $states=\App\Model\State::where('country_id', '=', 231)->whereNotIn('name', ["Byram", "Cokato", "District of Columbia", "Lowa", "Medfield", "New Jersy", "Ontario", "Ramey", "Sublimity", "Trimble"])->pluck('name', 'id');

        

        return view('vendor.mp2r.account.service_provider_category_filter',compact('Get_Category','Sp_Detail','states'));
    }

    public static function getSPList(Request $request,SpServiceType $subscription) {
        try{
            $doctors = [];
            $service_id = null;
            $service_ids = null;
            $service_type = null;
            $per_page = (isset($request->per_page)?$request->per_page:10);
            // print_r($per_page);die;
            $request->radius = 1000;
            $pageNumber = (isset($request->page)?$request->page:1);
            $service_id = isset($request->service_id)?$request->service_id:null;
            $request->service_type = isset($request->service_type)?$request->service_type:'all';
            $input = $request->all();
            $subscription = $subscription->newQuery();
            $state_id = null;
            $city_id = null;
            $zip_code = null;
            if(isset($request->state)){
                $request->radius = 10000;
                $state_id = 0;
                $state = \App\Model\State::where('name',$request->state)->first();
                if($state){
                    $state_id = $state->id;
                } 
            }
            if(isset($request->city)){
                $request->radius = 10000;
                $city_id = 0;
                $city = \App\Model\City::where('name',$request->city)->first();
                if($city){
                    $city_id = $city->id;
                } 
            }
            if(isset($request->zip_code)){
                $request->radius = 10000;
                $zip_code = $request->zip_code; 
            }
            /* for Consultant Listing */
            $consultant_ids = User::whereHas('roles', function ($query) {
                           $query->where('name','service_provider');
                        })->orderBy('id','DESC')->pluck('id')->toArray();
            if($request->service_type!='all'){
                if($request->service_type=='home_care'){
                    $request->service_type = 'Home Appointment';
                }else if($request->service_type=='clinic_appointment'){
                    $request->service_type = 'Clinic Appointment';
                }
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
            if($request->has('search')){
                if($request->search){
                    $consultant_ids = User::whereLike('name', $request->search)->whereIn('id',$consultant_ids)->groupBy('id')->pluck('id');
                }
            }
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
            if(Helper::checkFeatureExist(['client_id'=>\Config::get('client_id'),'feature_name'=>'monthly plan'])){
                $consultant_ids  = Helper::getPaidDoctors($consultant_ids);
            }
            if($zip_code!==null){
                $consultant_ids = \App\Model\CustomUserField::whereHas('customfield', function ($query) {
                    $query->where('field_name','Zip Code');
                })->where('field_value',$zip_code)->whereIn('user_id',$consultant_ids)->pluck('user_id')->toArray();
            }
            // print_r($consultant_ids);die;
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
            if($city_id!==null)
                $subscription->where('pp.city',$city_id);
            $subscription->select('sp_service_types.*','pp.id AS profile_id');
            $subscription->orderBy('pp.rating', 'DESC');
            $subscription->orderByRaw('FIELD(sp_id,'.implode(",", $consultant_ids).')');
            $doctors = $subscription->paginate($per_page);
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

            return view('vendor.mp2r.account.sp_category_filter_By_filter',compact('Sp_Detail'));
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage().' '.$ex->getLine()], 500);
        }
    }


    public function ChatHistoryPage(Request $request){

    	return view('vendor.mp2r.account.chat_history');
    }

    public function manage_availibilty_new(Request $request){



		$states = State::where('country_id', 231)->get();
		$insurances = Insurance::all();
		$userId = Auth::user()->id;
		$user = User::with('profile')->where('id', $userId)->first();
		$state_id = State::select('id')->where('name', $user->profile->state)->first();
		if($state_id){

			$cities = City::where('state_id', $state_id->id)->get();
		}else
		{
			$cities ="";
		}
		
		$user_insurance_id = UserInsurance::with('insurance')->select('insurance_id')->where('user_id', $userId)->get();
		
		$user_insurance = UserInsurance::with('insurance')->where('user_id',$userId)->first();
        
        $user_zip_code=CustomUserField::with('customfield')->where('user_id',$userId)->where('custom_field_id',3)->first();
        //dd($user_zip_code);
        $cookie_policy = \App\Model\Page::where('slug','cookie-policy')->first();
        $privacy_policy = \App\Model\Page::where('slug','privacy-policy')->first();
        
		// Get all the brands from the Brands Table.
		$parentCategories = Category::with('subcategory')->where('name', '!=', 'Find Local Resources')->where('parent_id', NULL)->where('enable', '=', '1')->get();
		$notifications = Notification::select('id','notification_type as pushType','message','module','read_status','created_at','sender_id as form_user','receiver_id as to_user')->where('receiver_id',$user->id)->orderBy('id', 'desc')->paginate(10);
    	if($notifications){
    		foreach ($notifications as $key => $notification) {
	    		$notification->form_user = User::select('id','name','profile_image')->where('id',$notification->form_user)->first();
	    		$notification->to_user = User::select('id','name','profile_image')->where('id',$notification->to_user)->first();
	    		$notification->sentAt = \Carbon\Carbon::parse($notification->created_at)->getPreciseTimestamp(3);
    		}
    	}
		if (Config::get("client_data")->domain_name == "mp2r" || Config::get("client_data")->domain_name == "food") {
			$question1 = Helper::getSecurityQuestion('question1');
            $question2 = Helper::getSecurityQuestion('question2');
            $question3 = Helper::getSecurityQuestion('question3');
            $selectedQ1 = Helper::getSelectedQuestion('question1',$user->id);
            $selectedQ2 = Helper::getSelectedQuestion('question2',$user->id);
            $selectedQ3 = Helper::getSelectedQuestion('question3',$user->id);
			return view('vendor.mp2r.account.manage-availibility_new')->with(array(
				'user' => $user,
				'cities' => $cities,
				'states' => $states,
				'insurances' => $insurances,
				'user_insurance_id' => $user_insurance_id,
				'parentCategories' => $parentCategories,
				'user_insurance' => $user_insurance,
				'user_zip_code' => $user_zip_code,
				'cookie_policy'=>$cookie_policy,
				'privacy_policy'=>$privacy_policy,
				'notifications' => $notifications,
				'question1'=>$question1,
				'question2'=>$question2,
				'question3'=>$question3,
				'selectedQ1'=>$selectedQ1,
				'selectedQ2'=>$selectedQ2,
				'selectedQ3'=>$selectedQ3,
			));
		}


    	//return view('vendor.mp2r.account.manage-availibility_new');
    }

    public static function ServicePSlotsByMultipleDates(Request $request) {
        try{
            $user =  Auth::user();
            
            $input = $request->all();
            $timezone = $request->header('timezone');
            if(!$timezone){
                $timezone = 'Asia/Kolkata';
            }
            $dates = explode(',', $input['dates']);
            //dd($dates);
            $data = [];
            foreach ($dates as $key => $date) {
                $weekMap = ['SU'=>0,'MO'=>1,'TU'=>2,'WE'=>3,'TH'=>4,'FR'=>5,'SA'=>6];
                $sp_slots = ServiceProviderSlotsDate::where([
                    'service_provider_id'=>$user->id,
                    'service_id'=>$input['service_id'],
                    'date'=>$date,
                    'category_id'=>$input['category_id'],
                ])->get();
                $sp_slot_array = [];
                if($sp_slots->count()==0){
                    $day = strtoupper(substr(Carbon::parse($date)->format('l'), 0, 2));
                    $day_number = $weekMap[$day];
                    $sp_slots = ServiceProviderSlot::where([
                        'service_provider_id'=>$user->id,
                        'service_id'=>$input['service_id'],
                        'day'=>$day_number,
                        'category_id'=>$input['category_id'],
                    ])->get();

                }
                // dd($sp_slots);
                $array_of_time = array ();
                if($sp_slots->count()>0){
                    foreach ($sp_slots as $key => $sp_slot) {
                        $start_time_date = Carbon::parse($sp_slot->start_time,'UTC')->setTimezone($timezone);
                        $start_time = $start_time_date->isoFormat('HH:mm');
                        $end_time_date = Carbon::parse($sp_slot->end_time,'UTC')->setTimezone($timezone);
                        $end_time = $end_time_date->isoFormat('HH:mm');
                        $starttime    = strtotime ($start_time); //change to strtotime
                        $endtime      = strtotime ($end_time); //change to strtotime
                        $sp_slot_array[] = array('start_time'=>$start_time,'end_time'=>$end_time);
                    }
                }
                $data[] = ["date"=>$date,"sp_slot_array"=>$sp_slot_array];
            }
            // die;
           //dd($data);
            return response(['status' => "success", 'statuscode' => 200,'message' => __('Slot List '), 'data' =>$data], 200);
            
            // return view('vendor.mp2r.account.manage-availibility_second')->with('data',$datas);
            
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage().' '.$ex->getLine()], 500);
        }
    }

    public function add_manage_availibilty_new(Request $request){
    	$states = State::where('country_id', 231)->get();
		$insurances = Insurance::all();
		$userId = Auth::user()->id;
		$user = User::with('profile')->where('id', $userId)->first();
		$state_id = State::select('id')->where('name', $user->profile->state)->first();
		if($state_id){

			$cities = City::where('state_id', $state_id->id)->get();
		}else
		{
			$cities ="";
		}
		
		$user_insurance_id = UserInsurance::select('insurance_id')->where('user_id', $userId)->first();
		if($user_insurance_id){
			$user_insurance_id = $user_insurance_id->insurance_id;
		}else
		{
			$user_insurance_id="";
		}
		$user_insurance = UserInsurance::with('insurance')->where('user_id',$userId)->first();
        
        $user_zip_code=CustomUserField::with('customfield')->where('user_id',$userId)->where('custom_field_id',3)->first();
        //dd($user_zip_code);
        $cookie_policy = \App\Model\Page::where('slug','cookie-policy')->first();
        $privacy_policy = \App\Model\Page::where('slug','privacy-policy')->first();
        
		// Get all the brands from the Brands Table.
		$parentCategories = Category::with('subcategory')->where('name', '!=', 'Find Local Resources')->where('parent_id', NULL)->where('enable', '=', '1')->get();
		$notifications = Notification::select('id','notification_type as pushType','message','module','read_status','created_at','sender_id as form_user','receiver_id as to_user')->where('receiver_id',$user->id)->orderBy('id', 'desc')->paginate(20);
    	if($notifications){
    		foreach ($notifications as $key => $notification) {
	    		$notification->form_user = User::select('id','name','profile_image')->where('id',$notification->form_user)->first();
	    		$notification->to_user = User::select('id','name','profile_image')->where('id',$notification->to_user)->first();
	    		$notification->sentAt = \Carbon\Carbon::parse($notification->created_at)->getPreciseTimestamp(3);
    		}
    	}
			$question1 = Helper::getSecurityQuestion('question1');
            $question2 = Helper::getSecurityQuestion('question2');
            $question3 = Helper::getSecurityQuestion('question3');
            $selectedQ1 = Helper::getSelectedQuestion('question1',$user->id);
            $selectedQ2 = Helper::getSelectedQuestion('question2',$user->id);
            $selectedQ3 = Helper::getSelectedQuestion('question3',$user->id);
    	return view('vendor.mp2r.account.add_manage-availibility')->with(array(
				'user' => $user,
				'cities' => $cities,
				'states' => $states,
				'insurances' => $insurances,
				'user_insurance_id' => $user_insurance_id,
				'parentCategories' => $parentCategories,
				'user_insurance' => $user_insurance,
				'user_zip_code' => $user_zip_code,
				'cookie_policy'=>$cookie_policy,
				'privacy_policy'=>$privacy_policy,
				'notifications' => $notifications,
				'question1'=>$question1,
				'question2'=>$question2,
				'question3'=>$question3,
				'selectedQ1'=>$selectedQ1,
				'selectedQ2'=>$selectedQ2,
				'selectedQ3'=>$selectedQ3,
			));
    }

    public static function postMannualSubscribeService(Request $request) {

    	//dd($request->all());
        try{
            $user = Auth::user();
            // if(!$user->hasrole('service_provider')){
            //     return response(array('status' => "error", 'statuscode' => 400, 'message' =>'Invalid user role, must be role as service_provider'), 400);
            // }
             $rules = [
                    'category_id'=>'required|integer|exists:categories,id'
            ];
            $input = $request->all();
            $input['category_services_type'] = [['id'=>2,'available'=>'1','price'=>10,'minimmum_heads_up'=>5,'availability'=>['applyoption'=>'weekdays','days'=>[1,2,3,4,5],'slots'=>[['start_time'=>'09:00','end_time'=>'18:30']]]]];
            // $validator = Validator::make($request->all(),$rules);
            // if ($validator->fails()) {
            //     return response(array('status' => "error", 'statuscode' => 400, 'message' =>
            //         $validator->getMessageBag()->first()), 400);
            // }
            $timezone = $request->header('timezone');
            if(!$timezone){
                $timezone = 'Asia/Kolkata';
            }
            $input['slots'] = [['start_time'=>'09:00','end_time'=>'18:30']];
            $feature = Helper::getClientFeatureExistWithFeatureType('Dynamic Sections','Master Interval');
            if($feature){
                $slots =  Helper::getMasterSlots($timezone);
                if(count($slots) > 0){
                    $input['slots'] = $slots->toArray();
                }
            }
            $duration = '60';
            $unit_price = EnableService::where('type','unit_price')->first();
            if($unit_price){
                $duration = $unit_price->value * 60;
            }
            if(isset($input['category_services_type'])){
                foreach ($input['category_services_type'] as $category_service_type) {
                    $spservicetype = SpServiceType::firstOrCreate([
                        'sp_id'=>$user->id,
                        'category_service_id'=>$category_service_type['id']
                    ]);
                        // print_r($category_service_type);die;
                    if($spservicetype){
                        $service = CategoryServiceType::where('id',$category_service_type['id'])->first();
                        $spservicetype->available = $category_service_type['available'];
                        if($category_service_type['available']=="1")
                            $spservicetype->minimmum_heads_up = $category_service_type['minimmum_heads_up'];
                        if($service->price_fixed){
                            $spservicetype->price = $service->price_fixed;
                        }else{
                            if($category_service_type['available']=="1"){
                                if($category_service_type['price'] >= $service->price_minimum && $category_service_type['price']<=$service->price_maximum){
                                    $spservicetype->price = $category_service_type['price'];
                                }else{
                                    return response(array('status' => "error", 'statuscode' => 400, 'message' =>'Please select price into the range price_fixed'), 400);
                                }
                            }
                        }
                        $spservicetype->duration = $duration;
                        $spservicetype->save();
                        if($service && $service->service->need_availability){
                            if(!isset($category_service_type['isAvailabilityChanged']) || $category_service_type['isAvailabilityChanged']){
                                $availability = $category_service_type["availability"];
                                if($availability['applyoption']=='weekdays'){//monday-to-friday
                                    $weekdays = [1,2,3,4,5];
                                    ServiceProviderSlot::where([
                                        'service_provider_id'=>$user->id,
                                        'service_id'=>$service->service_id,
                                        'category_id'=>$input['category_id'],
                                    ])->whereIn('day',$weekdays)->delete();
                                    foreach ($weekdays as $day) {
                                       foreach ($input['slots'] as $slot) {
                                            $start_time = Carbon::parse($slot['start_time'],$timezone)->setTimezone('UTC')->format('H:i:s');
                                            $end_time = Carbon::parse($slot['end_time'],$timezone)->setTimezone('UTC')->format('H:i:s');
                                            $spavailability = new ServiceProviderSlot();
                                            $spavailability->service_provider_id = $user->id;
                                            $spavailability->service_id = $service->service_id;
                                            $spavailability->category_id = $input['category_id'];
                                            $spavailability->start_time = $start_time;
                                            $spavailability->end_time = $end_time;
                                            $spavailability->day = $day;
                                            $spavailability->save();
                                       }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if(isset($input['category_id'])){
                $category_service = CategoryServiceProvider::where(['sp_id'=>$user->id])->first();
                if(!$category_service){
                    $category_service =  new CategoryServiceProvider();
                    $category_service->sp_id = $user->id;
                }
                $category_service->category_id = $input['category_id'];
                $category_service->save();
            }
            $user->account_step = 5;
            $user->save();
            $user->subscriptions = $user->getSubscription($user);
            if($user->profile){
                $user->profile->bio = $user->profile->about;
                $user->totalRating =  $user->profile->rating;
            }
            $user->categoryData = $user->getCategoryData($user->id);
            $user->additionals = $user->getAdditionals($user->id);
            $user->services = $user->getServices($user->id);
            $user->filters = $user->getFilters($user->id);
            $user->patientCount = User::getTotalRequestDone($user->id);
            $user->reviewCount = Feedback::reviewCountByConsulatant($user->id);
            $token = $user->createToken('consult_app')->accessToken;
            $user->token = $token; 
            return response(['status' => "success", 'statuscode' => 200,
                                'message' => __('Subscribed  '), 'data' => $user], 200);
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage().' '.$ex->getLine()], 500);
        }
    }


    public function ServiceProviderAdvertisment(Request $request){

        return view('vendor.mp2r.account.advertisement');
    }


    public function ServiceProvideraddBanner(Request $request){


    	if ($request->hasfile('image')) {
    		

            if ($image = $request->file('image')) {
            	
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
                $start_date = \Carbon\Carbon::now()->format('Y-m-d');
	            $end_date = \Carbon\Carbon::now()->addDay(15)->format('Y-m-d');
	            $banner = new \App\Model\Banner();
	            $banner->image_web =  $filename;;
	            $banner->image_mobile =  $filename;;
	            $banner->start_date = $start_date;
	            $banner->end_date = $end_date;
	            $banner->position = '1';
	            $banner->category_id =null;
	            $banner->sp_id = $user->id;
	            $banner->created_by = $user->id;
	            $banner->class_id =null;
	            $banner->banner_type = 'service_provider';
	            $banner->enable = 0;
	            $banner->save();

                
            }
        }


        return redirect()->route('SPAppointment')->with('message','Banner Add Successfully');

        

    }


    public function EditManageAvailability(Request $request){


    	$editData=$request->all();

    	return view('vendor.mp2r.account.edit_manage-availibility')->with('editData', $editData);



    }

    public function SPCounselorPage(Request $request){

    	$counselor_category=Category::where(array('enable'=>1,'id'=>2))->get();

    	$categories = Category::where(array('enable'=>1,'parent_id'=>2))->get();

    	return view('vendor.mp2r.account.sp_conselor',compact('categories','counselor_category'));



    }

    public static function getWalletBalance(Request $request) {
        try{
            $user = Auth::user();
            $balance = 0;
            $wallet = \App\Model\Wallet::where('user_id',$user->id)->first();
            if($wallet){
                $balance = $wallet->balance;
            }
            $payments = [];
            $transaction_type=null;
            if(isset($request->transaction_type) && $request->transaction_type!=='all'){
                $transaction_type = $request->transaction_type;
            }
            $per_page = (isset($request->per_page)?$request->per_page:10);
	    	$payments = \App\Model\Payment::where('to',$user->id)->
            whereHas('transaction', function ($query) use($transaction_type) {
                            if($transaction_type){
                                $query->where('transaction_type', $transaction_type);
                            }
                        })->orderBy('id', 'desc')->get();
                
	    	foreach ($payments as $key => $payment) {
	    		$payment->from = User::select('name','email','id','profile_image')->with('profile')->where('id',$payment->from)->first();
	    		$payment->to = User::select('name','email','id','profile_image')->with('profile')->where('id',$payment->to)->first();
	    		$transaction_type = \App\Model\Transaction::select('amount','transaction_type','status','closing_balance','request_id','payout_message')->where('id',$payment->transaction_id)->first();
                $payment->call_duration = null;
                $payment->service_type = null;
                if($transaction_type->requesthistory){
                    $payment->call_duration = $transaction_type->requesthistory->duration;
                    $payment->service_type = $transaction_type->requesthistory->request->servicetype->type;
                }
                $payment->amount = $transaction_type->amount;
                $payment->payout_message = $transaction_type->payout_message;
                $payment->type = $transaction_type->transaction_type;
                $payment->status = $transaction_type->status;
                $payment->closing_balance = $transaction_type->closing_balance;
	    	}

           
           // $payments = $this->paginate($payments,10);

           if(Config::get('client_connected') && (Config::get('client_data')->domain_name=='iedu')){
            
            return view('vendor.iedu.wallet_doctor')->with(['balance' => $balance, 'payments' => $payments]);
           }
           else
           {
            return view('vendor.care_connect_live.wallet_doctor')->with(['balance' => $balance, 'payments' => $payments]);
           }
        
        }catch(Exception $ex){
            return response(['status' => "error", 'Auth::user()->balance::user()->balancestatuscode' => 500, 'message' => $ex->getMessage()], 500);
        }
        
    }

    public static function getBankAccountsListing(Request $request) {
        try{
            $user = Auth::user();
            if(!$user->hasrole('service_provider')){
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>'Invalid Valid user role must be as service_provider'), 400);
            }
            $bank_accounts = [];
            $bank_accounts = $user->getAttachedBanks($user);
            return view('vendor.care_connect_live.payout')->with(['bank_accounts' => $bank_accounts]);
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage()], 500);
        }
    }

    public function getServiceProviderProfile($id){
        if($id)
        {
		    $doctor_details = User::getDoctorDetail($id);
            return view('vendor.care_connect_live.profile_doctor')->with('doctor_details',$doctor_details);
        }
	}

    public function EditServiceProviderProfile($id){
        if($id)
        {
            $user = User::where('id',$id)->first();
            $profile = Profile::where('user_id',$user->id)->first();
            $language  = DB::table('master_preferences')
            ->join('master_preferences_options', 'master_preferences.id', '=', 'master_preferences_options.preference_id')->where('master_preferences.name','=','Languages')
            ->select('master_preferences.id as preferid', 'master_preferences_options.name as optname', 'master_preferences_options.id as optid')
            ->get();
    
            $Gender  = DB::table('master_preferences')
            ->join('master_preferences_options', 'master_preferences.id', '=', 'master_preferences_options.preference_id')->where('master_preferences.name','=','Gender')
            ->select('master_preferences.id as preferid', 'master_preferences_options.name as optname', 'master_preferences_options.id as optid')
            ->get();
    
            $getuserpreference = \App\Model\UserMasterPreference::where('user_id',Auth::user()->id)->get();
            //  return json_encode($getuserpreference);
    
            
            return view('vendor.care_connect_live.edit_profile_doctor')->with('id', $id)->with('user', $user)->with('profile', $profile)->with('Gender',$Gender)->with('language',$language)->with('getuserpreference',$getuserpreference);
           
           
        }
	}

    public static function postAcceptRequest(Request $request) {
    	
        $user = Auth::user();
        
        $sr_request = \App\Model\Request::where('id',$request->request_id)->first();
        $message = 'Something went wrong';
        if($sr_request){
            if($sr_request->requesthistory->status=='pending'){
                $re_history = \App\Model\RequestHistory::where('request_id',$sr_request->id)->first();
                $re_history->status = 'accept';
                $re_history->save();
                if(Config::get('client_connected') && (Config::get('client_data')->domain_name=='care_connect_live')){
                        $timezone = 'Asia/Kolkata';
                        if (isset($request->timezone)) {
                            $timezone = $request->timezone;
                        }
                        $slot_duration = \App\Model\EnableService::where('type','slot_duration')->first();
                        $slot_minutes = $slot_duration->value;
                        $add_slot_second = $slot_duration->value * 60;
                        $end_time_slot_utcdate = Carbon::parse($sr_request->booking_date)->addSeconds($add_slot_second)->format('Y-m-d H:i:s');
                        $end_time_zone_slot = Carbon::parse($end_time_slot_utcdate)->format('h:i:s');
                        $end_time_zone_date = Carbon::parse($end_time_slot_utcdate)->format('Y-m-d');
                        
                        $bookingdate = Carbon::parse($sr_request->booking_date)->format('Y-m-d H:i:s');
                        $stat_time_zone_slot = Carbon::parse($bookingdate)->format('h:i:s');
                        $start_time_zone_date = Carbon::parse($bookingdate)->format('Y-m-d');
                        
                            // get all accepted requests
                        
                            $check_request = \App\Model\Request::where('to_user',Auth::user()->id)
                                            ->whereBetween('booking_date', [$bookingdate, $end_time_slot_utcdate])
                                            ->whereHas('requesthistory', function ($query) {
                                                $query->whereNotIn('status',['canceled','failed','completed','pending']);
                                            })
                                            ->where('token_number', '!=', NULL)
                                            ->orderby('id','asc')
                                        ->get();
                        // return json_encode($check_request);
                            // get last token id generated

                            if(sizeof($check_request) > 0)
                            {
                            $tokens = [];
                            foreach($check_request as $item)
                            {
                                if($item->token_number != null)
                                {
                                    array_push($tokens, $item->token_number);
                                }
                            }

                                    sort($tokens);
                                    $new_token = end($tokens) + 1;

                            }
                            else
                            {
                                $new_token = 1;
                            }

                            $n_request = \App\Model\Request::find($request->request_id);
                            $n_request->token_number = $new_token;
                            $n_request->save();
                    }
                    
                $notification = new Notification();
                $notification->sender_id = $sr_request->to_user;
                $notification->receiver_id = $sr_request->from_user;
                $notification->module_id = $sr_request->id;
                $notification->module ='request';
                $notification->notification_type ='REQUEST_ACCEPTED';
                $notification->message =__('notification.accept_req_text', ['vendor_name' => $sr_request->sr_info->name]);;
                $notification->save();
               // $notification->push_notification(array($sr_request->from_user),array('pushType'=>'REQUEST_ACCEPTED','request_id'=>$sr_request->id,'message'=>__('notification.accept_req_text', ['vendor_name' => $sr_request->sr_info->name])));
               if(Config::get('client_connected') && (Config::get('client_data')->domain_name=='care_connect_live')){
                 return response(['status' => "success", 'statuscode' => 200,'message' => __('Request Accepted '),'data'=>(object)['token_number' => $new_token]], 200);
               }
               else
               {
                return response(['status' => "success", 'statuscode' => 200,'message' => __('Request Accepted '),'data'=>(object)[]], 200);
               }
            }else{
                $message = 'Already Accepted';
            }
        }else{
            $message = 'No Request Found';
        }
        return response(array('status' => "error", 'statuscode' => 400, 'message' =>__($message)), 400);

} 


public function UpdateServiceProviderProfile(Request $request)
{
   try
    {
        $user = $request->user_id;
        if(!$user)
        {
        $isValid =  Validator::make($request->all(), [
            'title'	=>	'required',
            'name'	=>	'required',
            'email' =>  'required|unique:users,email,'.$request->user_id,
            'password' => 'required|min:8|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|confirmed',
            'dob'   =>  'required' ,
            'working_since' => 'required',
            'qualification' => 'required',
            'bio' => 'required'
        ]);
        }
        else{    
         $isValid =  Validator::make($request->all(), [
             'title'	=>	'required',
             'name'	=>	'required',
             'email' =>  'required|unique:users,email,'.$request->user_id,
             'dob'   =>  'required' ,
             'working_since' => 'required',
             'qualification' => 'required',
             'bio' => 'required'
         ]);
        }

       
         
        if($isValid->fails()) {
            return redirect('/service_provider/editprofile/'.$request->input('user_id'))->withErrors($isValid)->withInput();

        }
        else {
            
            $input = $request->all();
            //return json_encode($input);
            $user = User::where('id',$request->user_id)->first();
            $profile = Profile::where('user_id',$request->user_id)->first();  
           
            if($profile)
            {
            if(isset($request->profile_image))
            {
                 //return "file ";
                
               $uploadimage = $this->UserController->Useruploadimage($request);
               
            }

            if(isset($request->dob)){
                $orgDate = $request->dob;  
                $profile->dob = date("Y-m-d", strtotime($orgDate));
            }
           
            if(isset($request->working_since)){
                $orgDate = $request->working_since;  
                $profile->working_since = date("Y-m-d", strtotime($orgDate));
            }
            if(isset($request->bio)){
                $profile->about = $request->bio;  
            }
            if(isset($request->qualification)){
             $profile->qualification = $request->qualification;  
              }
            if(isset($request->title)){
                $profile->title = $request->title;  
            }
            
            $profile->save();
        }
        else{
            // $input['password'] = Hash::make($input['password']);
            $pro = new Profile;
            if(isset($request->dob)){
                $orgDate = $request->dob;  
             $pro->dob = date("Y-m-d", strtotime($orgDate)); 
            }
           
            if(isset($request->working_since)){
                $orgDate = $request->working_since;  
                $pro->working_since = date("Y-m-d", strtotime($orgDate));
            }
            if(isset($request->bio)){
                $pro->about = $request->bio;  
            }
            if(isset($request->title)){
                $pro->title = $request->title;  
            }
            if(isset($request->qualification)){
             $pro->qualification = $request->qualification;  
              }
            $pro->user_id = $user->id;
            $pro->save();

              }
            if(isset($request->phone)){
                $user->phone = str_replace(' ', '',$request->input('phone'));
            } 
            if(isset($request->email)){
                $user->email = $request->input('email');
            }
            
            if(isset($request->name)){
                $user->name = $request->input('name');
            }
         //    if(isset($request->password)){
         //     $user->password =  $input['password'];
         //     }
            $user->account_step = '1';
            $user->save();
            if(isset($request->gender_opt_id)){
             // $gender_options = [
             //     'prefer_id' =>$request->gender,
             //     'opt_id' => $request->gender_opt_id
             // ];
             
                 \App\Model\UserMasterPreference::updateOrCreate([
                     'user_id'   => Auth::user()->id,
                     'preference_id'=>$request->gender,
                 ],[
                     'user_id'=>$user->id,
                     'preference_id'=>$request->gender,
                     'preference_option_id'=>$request->gender_opt_id,
                 ]);
             }
             if(isset($request->language_opt_id)){
                 
                 \App\Model\UserMasterPreference::where('user_id', Auth::user()->id)->where('preference_id', $request->language)->delete();
                 foreach ($request->language_opt_id as $key => $lang) {
          

                     DB::table('user_master_preferences')->insert([
                         'user_id'=>$user->id,
                         'preference_id'=>$request->language,
                         'preference_option_id'=>$lang,
                     ]);

                     // \App\Model\UserMasterPreference::updateOrCreate([
                     //     'user_id'   => Auth::user()->id,
                     //     'preference_id'=>$request->language,
                     // ],[
                     //     'user_id'=>$user->id,
                     //     'preference_id'=>$request->language,
                     //     'preference_option_id'=>$lang,
                     // ]);
                 }
             }
          
            
            if(isset($input['speciality'])){
                $profile->speciality = $input['speciality'];
            }
            if(isset($input['address'])){
                $profile->address = $input['address'];
            }
            if(isset($input['call_price'])){
                $profile->call_price = $input['call_price'];
            }
            if(isset($input['chat_price'])){
                $profile->chat_price = $input['chat_price'];
            }
            if(isset($input['experience'])){
                $profile->experience = $input['experience'];
            }
            if(isset($input['state'])){
                $profile->state = $input['state'];
            }
            if(isset($input['city'])){
                $profile->city = $input['city'];
            }
            $profile->save();
         }
         return redirect('/service_provider/editprofile/'.$request->input('user_id'))->with('status.success', 'Profile Updated Successfully');
     }
    catch(\Exception $ex)   
    {
        return $ex;
        return "test";
    }
}
public function getRevenue(Request $request)
{  
    $user = Auth::user();
    
    // if(!$user->hasrole('service_provider')){
    //     return response(array('status' => "error", 'statuscode' => 400, 'message' =>'Invalid Valid user role must be as service_provider'), 400);
    // }
    $start = isset($request->start_date)?$request->start_date:null;
    $end = isset($request->end_date)?$request->end_date:null;
    $requests_data = \App\Model\Request::getReqAnaliticsBySrPro($user->id);
    $revenu_res = \App\Model\Transaction::getRevenueBySrPro($user);
    $totalHours = User::getTotalRequestHours($user->id,$start,$end);
    $requests_data['totalRevenue'] = $revenu_res['totalRevenue'];
    $requests_data['monthlyRevenue'] = $revenu_res['monthlyRevenue'];
    $requests_data['totalHours'] = $totalHours;

    return view('vendor.care_connect_live.revneue-doctor');
    
}


 public function getManageAvailibilty(Request $request)
 { 
     $fetch_selected_cat = \App\Model\CategoryServiceProvider::where('sp_id', Auth::id())->first();
     $cat_info = $fetch_selected_cat->category_id;

     $services = [];
     $service_id = null;
     $timezone = 'Asia/Kolkata';
     if (isset($request->timezone)) {
         $timezone = $request->timezone;
     }
     $dateznow = new DateTime("now", new DateTimeZone($timezone));
     $datenow = $dateznow->format('Y-m-d H:i:s');
     $current_date = $dateznow->format('Y-m-d');
     $currentTime    = strtotime ($datenow);
    
     $effectiveDate = date('Y-m-d', strtotime("+3 months", strtotime($current_date)));

     $period = CarbonPeriod::create($current_date, $effectiveDate);
     
     $weekMap = ['SUNDAY'=>0,'MONDAY'=>1,'TUESDAY'=>2,'WEDNESDAY'=>3,'THURSDAY'=>4,'FRIDAY'=>5,'SATURDAY'=>6];
     $data = [];
    
     // Iterate over the period
     foreach ($period as $date) {
         $dates = $date->format('Y-m-d');
         $day = strtoupper(substr(Carbon::parse($date)->format('l'), 0, 10));

         $item = [
             'date' => $dates,
             'day'  => $day
         ];
        array_push($data,$item);
     }

   $input['category_id'] = $cat_info;
     // $input = $request->all();
     
     $unit_price = EnableService::where('type','unit_price')->first();
     $service_ids = Service::where('enable',1)->pluck('id')->toArray();
     $slot_duration = EnableService::where('type','slot_duration')->first();
    
     $services = CategoryServiceType::where([
         'category_id'   =>  $input['category_id'],
         'is_active'     =>  "1"
     ])->whereIn('service_id', $service_ids)->orderBy('id', 'asc')->get();

     //return json_encode($services);

     $services_data = [];

     foreach ($services as $key => $categoryservice)
     {
         if($categoryservice->service)
         {
             $categoryservice->unit_price = $unit_price->value * 60;
             if(\Config::get('client_connected') && \Config::get("client_data")->domain_name == "healtcaremydoctor")
             {
                 $categoryservice->fixed_price = false; 
                 $categoryservice->unit_price = $slot_duration->value * 60; 
                 $categoryservice->slot_duration = $unit_price->value;
                 if($categoryservice->price_fixed)
                 {
                     $categoryservice->fixed_price = true;
                     $categoryservice->unit_price = $slot_duration->value * 60; 
                      $categoryservice->slot_duration = $slot_duration->value ; 

                   
                 }
              
             } 
            
             $categoryservice->name = $categoryservice->service->type;
             $categoryservice->main_service_type = $categoryservice->service->service_type;
             $categoryservice->color_code = $categoryservice->service->color_code;
             $categoryservice->description = $categoryservice->service->description;
             $categoryservice->need_availability = $categoryservice->service->need_availability;
             $categoryservice->price_type = null;
             if($categoryservice->price_fixed!==null)
             {
                 $categoryservice->price_type = 'fixed_price';
                 unset($categoryservice->price_minimum);
                 unset($categoryservice->price_maximum);
             }
             else
             {
                 unset($categoryservice->price_fixed);
                 $categoryservice->price_type = 'price_range';
                 $fetcinfo = \App\Model\SpServiceType::select('price')->where('sp_id', Auth::id())->where('category_service_id', $categoryservice->id)->first();
                 // $getprice = json_decode($fetcinfo);
                 // return $fetcinfo->price;
                 if($fetcinfo)
                 {
                     $categoryservice->price_fixed = $fetcinfo->price;
                 }
             }
             unset($categoryservice->service);


             $service_enabled = false;
             $fetch_s_info = \App\Model\SpServiceType::where('sp_id', Auth::id())->where('category_service_id', $categoryservice->id)->first();
             if($fetch_s_info)
             {
                 $service_enabled = true;
             }

             $categoryservice->service_enabled = $service_enabled;

             $services_data[] = $categoryservice; 
         }
     }

     //return json_encode($services_data);

     // $categoryservicetype = CategoryServiceType::pluck('id')->toArray();
     //return json_encode($services_data);
     return view('vendor.care_connect_live.manage_availability_doctor')
         ->with('id', Auth::user()->id)
         ->with('services_data',$services_data)
         ->with('cat_info' ,$cat_info)
         ->with('data', $data);
   

 }
 public function getManagePreferences(Request $request)
 {
     
     $fetch_selected_cat = \App\Model\CategoryServiceProvider::where('sp_id', Auth::id())->first();
     $cat_info = $fetch_selected_cat->category_id;

     $selectedserviceType = ServiceProviderFilterOption::where('sp_id',Auth::user()->id)->get();

     try{
         // $user = Auth::user();
         // if(!$user->hasrole('service_provider')){
         //     return response(array('status' => "error", 'statuscode' => 400, 'message' =>'Invalid role, must be as service_provider'), 400);
         // }
         $request->category_id = $cat_info;
         $user_id = '';
         $rules = [];
         if(isset($request->category_id)){
             $rules['category_id'] = "required|integer|exists:categories,id";
         }
        
         
         $input = $request->all();
         $filters = [];
         $user_id = isset($user_id)?$user_id:null;
         // $categoryData = $user->getCategoryData($user->id);
         if(isset($request->category_id)){
                $filters = FilterType::getFiltersByCategory($request->category_id,$user_id);
             // return json_encode($filters);
                return view('vendor.care_connect_live.manage_preferences_doctor')->with('filters',$filters)->with('selectedserviceType',$selectedserviceType);
         }

        
       
       
     }catch(Exception $ex){
         return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage()], 500);
     }
    

 }

 public function setFilters(Request $request)
 {
     //return json_encode($request->all());
     try{
          $user = Auth::user();
         if(!$user->hasrole('service_provider')){
             return response(array('status' => "error", 'statuscode' => 400, 'message' =>'Invalid role, must be as service_provider'), 400);
         }
         $rules = [
             'filters' => "required|array|min:1",
             'filters' => "filled",
             // 'filters.*.filter_id' => 'required|integer|exists:filter_types,id',
             // 'filters.*.filter_option_ids' => 'required|array'
         ];
         $validator = Validator::make($request->all(),$rules);
         if ($validator->fails()) {
             return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                 $validator->getMessageBag()->first()), 400);
         }
         $input = $request->all();
         // return json_encode($input);
         
         if($input['filter_option_ids'] && $input['filters'])
         { 
             foreach ($input['filters'] as $key => $filter)
             {
                 // echo $filter[$key];
                 // die();

                 ServiceProviderFilterOption::where([
                     'sp_id' =>  $user->id,
                     'filter_type_id'    =>  $filter[$key],
                 ])->delete();

                 foreach($input['filter_option_ids'] as $filter_option_key => $filter_option)
                 {
                     // echo json_encode($filter_option);
                     // die();
                     foreach ($filter_option as $item_id)
                     {
                         ServiceProviderFilterOption::firstOrCreate([
                             'sp_id'             =>  $user->id,
                             'filter_type_id'    =>  $filter[$key],
                             'filter_option_id'  =>  $item_id,
                         ]);
                     }
                 }
             }
             $user = User::where('id',Auth::user()->id)->update([
                 'account_step' =>  '3'
             ]);
            
             return redirect('/service_provider/get_manage_preferences')->with('status.success', 'Set Preference Successfully'); 
         }   
         else{
             
             return redirect('service_provider/get_manage_preferences')->with('status.error', 'Please Set Preference'); 
         }
    
         
         // redirect('');
     }
     catch(Exception $ex)
     {
         return $ex;
     }
 }

 public function getUpdateCategory(Request $request)
    {
        $per_page = (isset($request->per_page)?$request->per_page:10);
        
        $categories = Category::where(['enable'=>'1','parent_id'=>null])
            ->orderBy('id',"ASC")
            ->get();

        // get if any sp_additional_fields filled

        $fetch_docs = \App\Model\SpAdditionalDetail::where('sp_id', Auth::id())->select('additional_detail_id')->get();

        
        $info = [];

        if($fetch_docs)
        {
            foreach ($fetch_docs as $doc)
            {
                $sub_cat_id = $doc->additional_detail_id;

                // fetch main category
                $fetch_cat_id = \App\Model\AdditionalDetail::where('id', $sub_cat_id)->first();
                if($fetch_cat_id)
                {
                    $cat_id = $fetch_cat_id->category_id;

                    array_push($info, $cat_id);
                }
            }
        }

        // return json_encode($info);

        // if selected, show that category only and show next button

         

       
        return view('vendor.care_connect_live.update_category_doctor')->with('id', Auth::user()->id)->with('categories',$categories)->with('cat_info', $info);
      
    }


    public function addAvailbility(Request $request)
{
   // return json_encode($request->all());
    $user = Auth::user();
    if(!$user->hasrole('service_provider')){
        return response(array('status' => "error", 'statuscode' => 400, 'message' =>'Invalid user role, must be role as service_provider'), 400);
    }
    $availability = [];
    $input = $request->all();
    // $data = [
    //     'start_time' => $request->start_time,
    //     'end_time' => $request->end_time
    // ],
    // [
    //     'start_time' => $request->start_time,
    //     'end_time' => $request->end_time
    // ];
    // array_push($availability,$data);

    // description="[{'availability':{'applyoption':'specific_day','day':2,'date':'2010-08-19','days':[true,false,true,true,true,true,false],'slots':[{'start_time':'11:00','end_time':'16:30'}]}}]",

    $input_data = [];

    $slots = [];

    $timezone = 'Asia/Kolkata';
			if (isset($request->timezone)) {
				$timezone = $request->timezone;
			}
    
    
    $service = CategoryServiceType::where('category_id',$request->category_id)->where('service_id',$request->service_id)->first();
    if($request->action=='weekwise'){
        if(isset($availability['days'])){
            ServiceProviderSlot::where([
                'service_provider_id'=>$user->id,
                'service_id'=>$service->service_id,
                'category_id'=>$input['category_id'],
            ])->delete();
            foreach ($availability['days'] as $day=>$flag) {
                if($flag){
                   foreach ($availability['slots'] as $slot) {
                        $start_time = Carbon::parse($slot['start_time'],$timezone)->setTimezone('UTC')->format('H:i:s');
                        $end_time = Carbon::parse($slot['end_time'],$timezone)->setTimezone('UTC')->format('H:i:s');
                        $spavailability = new ServiceProviderSlot();
                        $spavailability->service_provider_id = $user->id;
                        $spavailability->service_id = $service->service_id;
                        $spavailability->category_id = $input['category_id'];
                        $spavailability->start_time = $start_time;
                        $spavailability->end_time = $end_time;
                        $spavailability->day = $day;
                        $spavailability->save();
                   }
                }
            }
        }
    }else if($request->action=='multiple_days'){
        $slot_1 = [
            "start_time"    =>  $request->start_time,
            "end_time"      =>  $request->end_time
        ];
    
        array_push($slots, $slot_1);
    
        $availability = [
            'days' => '2',
            'date'          => $request->slot_date ,
            'slots' =>  $slots
        ];
        if(isset($availability['days'])){
            foreach ($availability['days'] as $day=>$flag) {
                if($flag){
                    ServiceProviderSlot::where([
                        'service_provider_id'=>$user->id,
                        'service_id'=>$service->service_id,
                        'category_id'=>$input['category_id'],
                        'day'=>$day,
                    ])->delete();
                   foreach ($availability['slots'] as $slot) {
                        $start_time = Carbon::parse($slot['start_time'],$timezone)->setTimezone('UTC')->format('H:i:s');
                        $end_time = Carbon::parse($slot['end_time'],$timezone)->setTimezone('UTC')->format('H:i:s');
                        $spavailability = new ServiceProviderSlot();
                        $spavailability->service_provider_id = $user->id;
                        $spavailability->service_id = $service->service_id;
                        $spavailability->category_id = $input['category_id'];
                        $spavailability->start_time = $start_time;
                        $spavailability->end_time = $end_time;
                        $spavailability->day = $day;
                        $spavailability->save();
                   }
                }
            }
        }
    }else if($request->action=='specific_date'){
        $slot_1 = [
            "start_time"    =>  $request->start_time,
            "end_time"      =>  $request->end_time
        ];
    
        array_push($slots, $slot_1);
    
        $availability = [
            'date'          => $request->slot_date ,
            'slots' =>  $slots
        ];
        ServiceProviderSlotsDate::where([
            'service_provider_id'=>$user->id,
            'service_id'=>$service->service_id,
            'date'=>$availability['date'],
            'category_id'=>$input['category_id'],
        ])->delete();
       foreach ($availability['slots'] as $slot) {
        $start_time = $slot['start_time'][0]; 
        $end_time = $slot['end_time'][0]; 
            // $start_time = Carbon::parse($slot['start_time'],$timezone)->setTimezone('UTC')->format('H:i:s');
            // $end_time = Carbon::parse($slot['end_time'],$timezone)->setTimezone('UTC')->format('H:i:s');
            $spavailability = new ServiceProviderSlotsDate();
            $spavailability->service_provider_id = $user->id;
            $spavailability->service_id = $service->service_id;
            $spavailability->category_id = $input['category_id'];
            $spavailability->start_time = $start_time;
            $spavailability->end_time = $end_time;
            $spavailability->date = $availability['date'];
            $spavailability->save();
       }
       return redirect('service_provider/get_manage_availibilty')->with('status.suucess', 'Availbility Added Successfully' );
         //return response(array('status' => "success", 'statuscode' => 200, 'message' =>'Availbility Added Successfully'), 200);
    }else if($request->action=='specific_day'){
        $slot_1 = [
            "start_time"    =>  $request->start_time,
            "end_time"      =>  $request->end_time
        ];
    
        array_push($slots, $slot_1);
    
        $availability = [
            'date'          => $request->slot_date ,
            'slots' =>  $slots
        ];
        $weekMap = ['SU'=>0,'MO'=>1,'TU'=>2,'WE'=>3,'TH'=>4,'FR'=>5,'SA'=>6];
        $day = strtoupper(substr(Carbon::parse($availability['date'])->format('l'), 0, 2));
        $day_number = $weekMap[$day];
        ServiceProviderSlot::where([
            'service_provider_id'=>$user->id,
            'service_id'=>$service->service_id,
            'day'=>$day_number,
            'category_id'=>$input['category_id'],
        ])->delete();
       foreach ($availability['slots'] as $slot) {
            $start_time = $slot['start_time'][0]; 
            $end_time = $slot['end_time'][0]; 
            // $start_time = Carbon::parse($slot['start_time'],$timezone)->setTimezone('UTC')->format('H:i:s');
            // $end_time = Carbon::parse($slot['end_time'],$timezone)->setTimezone('UTC')->format('H:i:s');
            $spavailability = new ServiceProviderSlot();
            $spavailability->service_provider_id = $user->id;
            $spavailability->service_id = $service->service_id;
            $spavailability->category_id = $input['category_id'];
            $spavailability->start_time = $start_time;
            $spavailability->end_time = $end_time;
            $spavailability->day = $day_number;
            $spavailability->save();
       }
       return redirect('service_provider/get_manage_availibilty')->with('status.suucess', 'Availbility Added Successfully' );
    }else if($request->action=='weekdays'){//monday-to-friday
        $weekdays = [1,2,3,4,5];
        ServiceProviderSlot::where([
            'service_provider_id'=>$user->id,
            'service_id'=>$service->service_id,
            'category_id'=>$input['category_id'],
        ])->whereIn('day',$weekdays)->delete();
        $slot_1 = [
            "start_time"    =>  $request->start_time,
            "end_time"      =>  $request->end_time
        ];
    
        array_push($slots, $slot_1);
    
        $availability = [
            'date'          => $request->slot_date ,
            'slots' =>  $slots
        ];
    
        //return json_encode($item);
        // return $availability['slots'];
        foreach ($weekdays as $day) {
           foreach ($availability['slots'] as $key=>$slot)
           {
            $start_time = $slot['start_time'][0]; 
            $end_time = $slot['end_time'][0]; 
                //$start_time = Carbon::parse($slot['start_time'][0],$timezone)->setTimezone('UTC')->format('H:i:s');
               // $end_time = Carbon::parse($slot['end_time'][0],$timezone)->setTimezone('UTC')->format('H:i:s');
                $spavailability = new ServiceProviderSlot();
                $spavailability->service_provider_id = $user->id;
                $spavailability->service_id = $service->service_id;
                $spavailability->category_id = $input['category_id'];
                $spavailability->start_time = $start_time;
                $spavailability->end_time = $end_time;
                $spavailability->day = $day;
                $spavailability->save();
           }
        }
        return redirect('service_provider/get_manage_availibilty')->with('status.suucess', 'Availbility Added Successfully' );
    }
}


public function getPrescription( Request $request)
{

      /* returns Ony Id value */

     $request_id = $request->input('request_id');
    //return $request_id;
    $user = Auth::user();
    if($request_id)
    {
        $requests = \App\Model\Request::select('id','service_id','from_user','to_user','booking_date','created_at','booking_date as bookingDateUTC','request_type')
                ->where('id',$request_id)
                 ->whereHas('requesthistory',function($query) use($request){
                
                        return $query->where('status','completed');
                
                }) ->where('to_user',$user->id)->first();

    }
    else
    {
        $requests = \App\Model\Request::select('id','service_id','from_user','to_user','booking_date','created_at','booking_date as bookingDateUTC','request_type')
                ->whereHas('requesthistory',function($query) use($request){
                
                        return $query->where('status','completed');
                
                }) ->where('to_user',$user->id)->latest()->first();

    }
    if($requests != '')
    
    {
    $requests->is_prescription = false;    
    if($requests->prescription){
        $requests->is_prescription = true;
      //  unset($requests->prescription);    
      $requests->prescriptionImage = ModelImage::where('module_table_id',$requests->prescription->id)->get();
      $requests->prescriptionMedicine = \App\Model\PreScriptionMedicine::where('pre_scription_id',$requests->prescription->id)->get();
    }
    
    $requests->service_type = $requests->servicetype->type;
    $last_message = \App\Model\Message::getLastMessage($requests);
    $requests->unReadCount = \App\Model\Message::getUnReadCount($requests,$user->id);
    $requests->last_message = $last_message;
    $userprefer=  \App\Model\UserMasterPreference::where('user_id', Auth::user()->id)->where('preference_id','2')->first();
    
    $getgen = \App\Model\MasterPreferencesOption::where('preference_id',$userprefer->preference_option_id)->first();
    if($getgen)
    {
        $requests->gender = $getgen->name;
    }
    
    
    $requests->from_user = User::select('name','email','id','profile_image','phone','country_code')->with(['profile'])->where('id',$requests->from_user)->first();
    $requests->to_user = User::select('name','email','id','profile_image','phone','country_code')->with(['profile'])->where('id',$requests->to_user)->first();
    }
  //  return $requests->prescriptionMedicine;
    return view('vendor.care_connect_live.prescription')->with('requests',$requests);
    
}

public function postAddPreScriptions(Request $request){
   //return json_encode($request->all()); 
   $input = $request->all();
   $input["pre_scriptions"] = [];

//    if($request->dummy_id != '' || $request->dummy_id != null)
//    { 
      $getdummydatadet =  DB::table('prescriptiondummys')->where('request_id',$input['request_id'])->get();
     
    foreach($getdummydatadet as $getdummydata){
        $item = [
            'medicine_name' => $getdummydata->medicine_name,
            'duration' => $getdummydata->duration,
            'dosage_type' => $getdummydata->dosage_type,
            'dosage_timing' => $getdummydata->dosage_timimg
        ];
        array_push($input["pre_scriptions"], $item);
    }
  
    
//    }
   //return $input["pre_scriptions"];
    
    $user = Auth::user();
    $customer = false;
    if($user->hasrole('customer')){
        $customer = true;
    }
  
    $rules = [];
    $rules["type"] = "required|in:digital,manual";
    $rules["request_id"] = "required|exists:requests,id";
    if(isset($request->type) && $request->type=="digital"){
        $rules["pre_scription_notes"] = "required|string";
        // $rules["pre_scriptions"] = "required|array|min:1";
        // $rules["pre_scriptions.*.medicine_name"] = "required|string";
        // $rules["pre_scriptions.*.duration"] = "required|string";
        // $rules["pre_scriptions.*.dosage_type"] = "required|string";
        // $rules["pre_scriptions.*.dosage_timing"] = "required||array|min:1";
        // $rules["pre_scriptions.*.image"] = "required|array|min:1";
    }
    if(isset($request->type) && $request->type=="manual"){
        $rules["title"] = "required|string";
        $rules["image"] = "required|array|min:1";
    }
    $validator = Validator::make($request->all(),$rules);
    
    if ($validator->fails()) {
        if($input['pre_scription_id'])
        {
            return redirect('service_provider/prescription?request_id='.$input["request_id"])->withErrors($validator)->withInput();
        }
        return redirect('service_provider/prescription')->withErrors($validator)->withInput();
    }   
   
   

     // return $input['pre_scriptions'];



   if($input['pre_scription_id'])
   {
       
    $prescription = PreScription::find($input['pre_scription_id']);

   }
   else
   {
    $prescription = new PreScription();
   }
   
    $prescription->type = $input['type'];
    $prescription->request_id = $input['request_id'];
    $prescription->save();
    if($input['type']=="digital"){ 
        $prescription->pre_scription_notes = $input["pre_scription_notes"];
       if($input['pre_scription_id'] == '' || $input['pre_scription_id'] == null)
       {
            if($input["pre_scriptions"]){ 
                foreach ($input["pre_scriptions"] as $pre_scription) {
                    
                    $prescriptionmedicine = new PreScriptionMedicine();
                    $prescriptionmedicine->medicine_name = $pre_scription['medicine_name'];
                    $prescriptionmedicine->duration = $pre_scription['duration'];
                    $prescriptionmedicine->dosage_type = $pre_scription['dosage_type'];
                    $prescriptionmedicine->dosage_timing = $pre_scription['dosage_timing'];
                    $prescriptionmedicine->pre_scription_id = $prescription->id;
                    $prescriptionmedicine->save();
                }
            }
       }
        if($request->request_id != '' || $request->request_id != Null)
        {
         DB::table('prescriptiondummys')
         ->where('request_id',$request->request_id)->delete();
        
        }
    }else{
        
        $prescription->title = $input['title'];
       
        if($request->hasfile('image'))

        {
            // if($input['pre_scription_id'])
            // {
            //     ModelImage::where('module_table_id',$input['pre_scription_id'])->delete();
            // }

           foreach($request->file('image') as $file)

           {

                $extension = $file->getClientOriginalExtension();
                $filename = str_replace(' ', '', md5(time()) . '_' . $file->getClientOriginalName());
                $thumb = \Image::make($file)->resize(
                    100,
                    100,
                    function ($constraint) {
                        $constraint->aspectRatio();
                    }
                )->encode($extension);
                $normal = \Image::make($file)->resize(
                    400,
                    400,
                    function ($constraint) {
                        $constraint->aspectRatio();
                    }
                )->encode($extension);
                $big = \Image::make($file)->encode($extension);
                $_800x800 = \Image::make($file)->resize(
                    800,
                    800,
                    function ($constraint) {
                        $constraint->aspectRatio();
                    }
                )->encode($extension);
                $_400x400 = \Image::make($file)->resize(
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
               
               
               $data[] = $filename;  
              // return $data;
              

           }
          // print_r($data); die();
           foreach($data as $imgdata)
           {
            $modelimage = new ModelImage();
            $modelimage->image_name = $imgdata;
            $modelimage->module_table = 'pre_scriptions';
            $modelimage->module_table_id = $prescription->id;
            $modelimage->save();
           }

        }
       
       
        // foreach ($data as $image) {
        //     $modelimage = new ModelImage();
        //     $modelimage->image_name = $data;
        //     $modelimage->module_table = 'pre_scriptions';
        //     $modelimage->module_table_id = $prescription->id;
        //     $modelimage->save();
        // }
    }
    $prescription->save();
    $sr_request = RequestData::where('id',$input['request_id'])->first();
    $sender_id = $user->id;
    if($customer){
        $receiver_id = $sr_request->to_user;
    }else{
        $receiver_id = $sr_request->from_user;
    }
    $notification = new Notification();
    $notification->sender_id = $sender_id;
    $notification->receiver_id = $receiver_id;
    $notification->module_id = $sr_request->id;
    $notification->module ='request';
    $notification->notification_type ='PRESCRIPTION_ADDED';
    $notification->message =__('Prescription added for your appointment');
    $notification->save();
    $notification->push_notification(array($receiver_id),array(
        'pushType'=>'PRESCRIPTION_ADDED',
        'request_id'=>$sr_request->id,
        'message'=>__('Prescription added for your appointment')
    ));
    if($input['pre_scription_id'])
    {
        return redirect('service_provider/prescription?request_id='.$input["request_id"])->with('status.success','Prescription saved for your appointment');
    }
    return redirect('service_provider/prescription')->with('status.success','Prescription saved for your appointment');
    // return response(['status' => "success",
    //     'statuscode' => 200,
    //     'message' => __('prescription saved'),
    //     'data'=>(object)[]], 
    //     200);
}

public function deletePrescriptionImage($id,Request $request)
{
    
     if($id)
    {
        ModelImage::where('id',$id)->delete();
        return 'true';
    }
    
   // return redirect('service_provider/prescription')->with('status.success','Prescription Image Deleted');

}

public function PrescriptionMedicineAdd(Request $request)
{
    //return json_encode($request->breakfast_dose_value);
    $dosage_timing = [];

    // breakfast
    $breakfast_checked = false;
    if($request->filled('breakfasttime'))
    {
        $breakfast_checked = true;
    }
    $breakfast_time_checked = false;
    if($request->filled('with-breakfast'))
    {
        $breakfast_time_checked = true;
        if($request->dosage_type == 'Tablet' )
        {
            $dose_value = $request->input('tablet_breakfast_dose_value');
        }
        elseif($request->dosage_type == 'Capsule')
        {
            $dose_value = $request->input('capsule_breakfast_dose_value');
    
        }elseif($request->dosage_type == 'Syrup')
        {
            $dose_value = $request->input('syrup_breakfast_dose_value');
        }else
        {
            $dose_value = $request->input('breakfast_dose_value');
        }
    }
   


    if($breakfast_checked && $breakfast_time_checked && $dose_value != null)
    {
        $breakfast_item = [
            "time"  =>  "Breakfast",
            "with"  =>  $request->input('with-breakfast'),
            "dose_value"    =>  $dose_value
        ];

        array_push($dosage_timing, $breakfast_item);
    }

    // return json_encode($dosage_timing);

    // lunch
    $lunch_checked = false;
    if($request->filled('lunchtime'))
    {
        $lunch_checked = true;
    }
    $lunch_time_checked = false;
    if($request->filled('with-lunch'))
    {
        $lunch_time_checked = true;
        if($request->dosage_type == 'Tablet' )
        {
            $dose_value = $request->input('tablet_lunch_dose_value');
        }
        elseif($request->dosage_type == 'Capsule')
        {
            $dose_value = $request->input('capsule_lunch_dose_value');
    
        }elseif($request->dosage_type == 'Syrup')
        {
            $dose_value = $request->input('syrup_lunch_dose_value');
        }else
        {
            $dose_value = $request->input('lunch_dose_value');
        }
    }
    

    if($lunch_checked && $lunch_time_checked && $dose_value != null)
    {
        $lunch_item = [
            "time"  =>  "Lunch",
            "with"  =>  $request->input('with-lunch'),
            "dose_value"    =>  $dose_value
        ];

        array_push($dosage_timing, $lunch_item);
    }
     // return json_encode($dosage_timing);
   // dinner
   $dinner_checked = false;
   if($request->filled('dinnertime'))
   {
       $dinner_checked = true;
   }
   $dinner_time_checked = false;
   if($request->filled('with-dinner'))
   {
       $dinner_time_checked = true;
       if($request->dosage_type == 'Tablet' )
       {
           $dose_value = $request->input('tablet_dinner_dose_value');
       }
       elseif($request->dosage_type == 'Capsule')
       {
           $dose_value = $request->input('capsule_dinner_dose_value');
   
       }elseif($request->dosage_type == 'Syrup')
       {
           $dose_value = $request->input('syrup_dinner_dose_value');
       }else
       {
           $dose_value = $request->input('dinner_dose_value');
       }
   }

   if($dinner_checked && $dinner_time_checked && $dose_value != null)
   {
       $dinner_item = [
           "time"  =>  "Dinner",
           "with"  =>  $request->input('with-dinner'),
           "dose_value"    =>  $dose_value
       ];

       array_push($dosage_timing, $dinner_item);
   }

   //return json_encode($dosage_timing);
  


if($request->pre_scription_id)
{
    $data = [
        'medicine_name' => $request->medicine_name,
        'duration' => $request->duration,
        'dosage_type' => $request->dosage_type,
        'dosage_timing' => json_encode($dosage_timing),
        'pre_scription_id' => $request->pre_scription_id
    ];
    $getId = \App\Model\PreScriptionMedicine::insertGetId($data);
    $prescriptionmedicine = DB::table('pre_scription_medicines')->where('id',$getId)->first();
    $pre_scriptions = [];
    $items = [
        'medicine_name' => $prescriptionmedicine->medicine_name,
        'duration' => $prescriptionmedicine->duration,
        'dosage_type' => $prescriptionmedicine->dosage_type,
        'dosage_timing' =>$prescriptionmedicine->dosage_timing
    ];
    array_push($pre_scriptions,$items);
}
else
{
    $data = [
        'medicine_name' => $request->medicine_name,
        'duration' => $request->duration,
        'dosage_type' => $request->dosage_type,
        'dosage_timimg' => json_encode($dosage_timing),
        'request_id' => $request->request_id
    ];
    $getId = DB::table('prescriptiondummys')->insertGetId($data);
    $prescriptionmedicine = DB::table('prescriptiondummys')->where('id',$getId)->first();
    //return json_encode($prescriptionmedicine);
    $pre_scriptions = [];
    $items = [
        'medicine_name' => $prescriptionmedicine->medicine_name,
        'duration' => $prescriptionmedicine->duration,
        'dosage_type' => $prescriptionmedicine->dosage_type,
        'dosage_timimg' =>$prescriptionmedicine->dosage_timimg
    ];
    array_push($pre_scriptions,$items);
}



return response([ 'status' => 'success','message'=> 'Medicine Added successfully','pre_scription_id' =>$request->pre_scription_id, 'request_id'=> $request->request_id, 'pre_scriptions' => $pre_scriptions, 'medicine_name' => $prescriptionmedicine->medicine_name, 'duration' => $prescriptionmedicine->duration, 'dosage_type' => $prescriptionmedicine->dosage_type,  'id'=> $prescriptionmedicine->id]);


 }

 public function MedicineGetEdit($id, Request $request)
 {
    $getdata = DB::table('prescriptiondummys')->where('id',$id)->first();
    return json_encode($getdata);
  
 }
 public function PrescriptionMedicineGetEdit($med_id, $pre_id, Request $request)
 {
    $getdata = DB::table('pre_scription_medicines')->where('id',$med_id)->where('pre_scription_id',$pre_id)->first();
    return json_encode($getdata);
  
 }

 public function PrescriptionMedicineDelete($id,Request $request)
 {
    DB::table('prescriptiondummys')->where('id',$id)->delete();
    return 'true';
 }

 public function PrescriptionMedicineEdit(Request $request)
{
  //return json_encode($request->breakfast_dose_value);
  $id = $request->dummy_id;
  $dosage_timing = [];

  // breakfast
  $breakfast_checked = false;
  if($request->filled('breakfasttime'))
  {
      $breakfast_checked = true;
  }
  $breakfast_time_checked = false;
  if($request->filled('with-breakfast'))
  {
      $breakfast_time_checked = true;
      if($request->dosage_type == 'Tablet' )
      {
          $dose_value = $request->input('tablet_breakfast_dose_value');
      }
      elseif($request->dosage_type == 'Capsule')
      {
          $dose_value = $request->input('capsule_breakfast_dose_value');
  
      }elseif($request->dosage_type == 'Syrup')
      {
          $dose_value = $request->input('syrup_breakfast_dose_value');
      }else
      {
          $dose_value = $request->input('breakfast_dose_value');
      }
  }
 


  if($breakfast_checked && $breakfast_time_checked && $dose_value != null)
  {
      $breakfast_item = [
          "time"  =>  "Breakfast",
          "with"  =>  $request->input('with-breakfast'),
          "dose_value"    =>  $dose_value
      ];

      array_push($dosage_timing, $breakfast_item);
  }

  // return json_encode($dosage_timing);

  // lunch
  $lunch_checked = false;
  if($request->filled('lunchtime'))
  {
      $lunch_checked = true;
  }
  $lunch_time_checked = false;
  if($request->filled('with-lunch'))
  {
      $lunch_time_checked = true;
      if($request->dosage_type == 'Tablet' )
      {
          $dose_value = $request->input('tablet_lunch_dose_value');
      }
      elseif($request->dosage_type == 'Capsule')
      {
          $dose_value = $request->input('capsule_lunch_dose_value');
  
      }elseif($request->dosage_type == 'Syrup')
      {
          $dose_value = $request->input('syrup_lunch_dose_value');
      }else
      {
          $dose_value = $request->input('lunch_dose_value');
      }
  }
  

  if($lunch_checked && $lunch_time_checked && $dose_value != null)
  {
      $lunch_item = [
          "time"  =>  "Lunch",
          "with"  =>  $request->input('with-lunch'),
          "dose_value"    =>  $dose_value
      ];

      array_push($dosage_timing, $lunch_item);
  }
   // return json_encode($dosage_timing);
 // dinner
 $dinner_checked = false;
 if($request->filled('dinnertime'))
 {
     $dinner_checked = true;
 }
 $dinner_time_checked = false;
 if($request->filled('with-dinner'))
 {
     $dinner_time_checked = true;
     if($request->dosage_type == 'Tablet' )
     {
         $dose_value = $request->input('tablet_dinner_dose_value');
     }
     elseif($request->dosage_type == 'Capsule')
     {
         $dose_value = $request->input('capsule_dinner_dose_value');
 
     }elseif($request->dosage_type == 'Syrup')
     {
         $dose_value = $request->input('syrup_dinner_dose_value');
     }else
     {
         $dose_value = $request->input('dinner_dose_value');
     }
 }

 if($dinner_checked && $dinner_time_checked && $dose_value != null)
 {
     $dinner_item = [
         "time"  =>  "Dinner",
         "with"  =>  $request->input('with-dinner'),
         "dose_value"    =>  $dose_value
     ];

     array_push($dosage_timing, $dinner_item);
 }

 //return json_encode($dosage_timing);

 if($request->pre_scription_id != null && $request->medicine_id != null)
 {
    $data = [
        'medicine_name' => $request->medicine_name,
        'duration' => $request->duration,
        'dosage_type' => $request->dosage_type,
        'dosage_timing' => json_encode($dosage_timing),
        'pre_scription_id' => $request->pre_scription_id
      ];
      //return json_encode($data);
      $deletedata = DB::table('pre_scription_medicines')->where('id',$request->medicine_id)->where('pre_scription_id',$request->pre_scription_id)->delete();
      $getId = DB::table('pre_scription_medicines')->insertGetId($data);
      $prescriptionmedicine = DB::table('pre_scription_medicines')->where('id',$getId)->first();
      //return json_encode($prescriptionmedicine->dosage_timimg);
      $pre_scriptions = [];
      $items = [
          'medicine_name' => $prescriptionmedicine->medicine_name,
          'duration' => $prescriptionmedicine->duration,
          'dosage_type' => $prescriptionmedicine->dosage_type,
          'dosage_timing' =>$prescriptionmedicine->dosage_timing
      ];
      array_push($pre_scriptions,$items);
      

 }
 else{

$data = [
  'medicine_name' => $request->medicine_name,
  'duration' => $request->duration,
  'dosage_type' => $request->dosage_type,
  'dosage_timimg' => json_encode($dosage_timing),
  'request_id' => $request->request_id
];
//return json_encode($data);
$deletedata = DB::table('prescriptiondummys')->where('id',$id)->delete();
$getId = DB::table('prescriptiondummys')->insertGetId($data);
$prescriptionmedicine = DB::table('prescriptiondummys')->where('id',$getId)->first();
//return json_encode($prescriptionmedicine->dosage_timimg);
$pre_scriptions = [];
$items = [
    'medicine_name' => $prescriptionmedicine->medicine_name,
    'duration' => $prescriptionmedicine->duration,
    'dosage_type' => $prescriptionmedicine->dosage_type,
    'dosage_timimg' =>$prescriptionmedicine->dosage_timimg
];
array_push($pre_scriptions,$items);
 }
return response([ 'status' => 'success','message'=> 'Medicine Updated successfully', 'pre_scription_id'=>$request->pre_scription_id, 'request_id'=> $request->request_id, 'pre_scriptions' => $pre_scriptions, 'medicine_name' => $prescriptionmedicine->medicine_name, 'duration' => $prescriptionmedicine->duration, 'dosage_type' => $prescriptionmedicine->dosage_type,  'id'=> $prescriptionmedicine->id]);

 }

    
}
