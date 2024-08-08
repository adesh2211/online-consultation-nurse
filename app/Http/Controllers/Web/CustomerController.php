<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Config;
use App\Model\ServiceProviderSlot;
use Auth;
use Session;
use  App\User;
use Socialite, Exception;
use Intervention\Image\ImageManager;
use Carbon\Carbon;
use App\Model\Insurance;
use Carbon\CarbonPeriod;
use App\Model\UserInsurance;
use App\Model\City;
use App\Model\CategoryServiceProvider;
use App\Model\State;
use App\Model\Category;
use App\Model\EnableService;
use App\Notification;
use App\Model\Advertisement;
use App\Model\ServiceProviderSlotsDate;
use DateTimeZone;
use App\Model\Request as RequestData;
use App\Model\CustomUserField;
use App\Model\SpServiceType;
use App\Model\RequestHistory;
use App\Model\CustomInfo;
use App\Model\FilterType;
use App\Model\Service;
use App\Model\Payment;
use App\Model\Wallet;
use DB;
use App\Helpers\Helper;
use App\Model\Feedback;
use DateTime;
use App\Model\CategoryServiceType;
use Illuminate\Support\Facades\Validator;
use Google\Model;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Hash,Mail;
use Redirect,Response,File,Image;
use Illuminate\Support\Facades\URL;
use App\Model\Role;
use App\Model\Package;
use App\Model\ServiceProviderFilterOption;
use App\Model\UserPackage,App\Model\RequestDetail;
use App\Model\Profile;
use App\Model\Transaction;
use App\Model\RequestDate;
use App\Model\Card,App\Model\Coupon,App\Model\CouponUsed;
use App\Model\SocialAccount;
use App\Model\Image as ModelImage;
use App\Jobs\RequestReminder;
use App\Jobs\RequestSmsEmail;

use Razorpay\Api\Api;

class CustomerController extends Controller
{
    protected $UserController; 
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

    public function objArraySearch($array, $index, $value)
    {
        foreach($array as $arrayInf) {
            if($arrayInf->{$index} == $value) {
                return $arrayInf;
            }
        }
        return null;
    }

    public function viewExperts($id = "all", $service_id = "all" , Request $request)
    {
      //return $request->all();
        if(Auth::user()->profile)
        {
        $timezone = $request->header('timezone');
        if(!$timezone){
            $timezone = 'Asia/Kolkata';
        }
        $dateznow = new DateTime("now", new DateTimeZone($timezone));
        $datenow = $dateznow->format('Y-m-d H:i:s');
        $current_date = $dateznow->format('Y-m-d');
        $categorys = Category::where('enable','1')->where('deleted_at',NULL)->get();
        // if(\Config::get('client_connected') && \Config::get('client_data')->domain_name=='iedu'){
        //  $categorys = Category::where('enable','1')->where('parent_id',NULL)->where('deleted_at',NULL)->get();
            
        // }
        $filters = [];
        $doctors = [];
        $state_id = null;
        $state_name = null;
        if(isset($request->state)){
            $state_id = 0;
            $state_name = $request->state;
            $state = \App\Model\State::where('name',$request->state)->first();
            if($state){
                $state_id = $state->id;
                $state_name = $state->name;
            } 
        }
        
        $per_page = (isset($request->per_page)?$request->per_page:10);
        
        $raw_filters = Filtertype::with(['options' => function($query) {
            return $query->select(['id', 'option_name','filter_type_id','image','description','video','banner','price'])->orderBy('option_name','ASC');
        }])->groupby('filter_name')->get();
       
        foreach ($raw_filters as $key => $filter) {
            if($filter->options->count()>0){
              
               $filters[] = array(
                'id'=>$filter->id,
                'category_id'=>$filter->category_id,
                'filter_name'=>$filter->filter_name,
                'preference_name'=>$filter->preference_name,
                'is_multi'=>$filter->is_multi,
                'options'=>$filter->options,
               ); 
            }
       }
       
    
       $services = Service::where('enable','1')->where('need_availability','1')->get();

        /* for doctor Listing */

       
            $consultant_ids = User::whereHas('roles', function ($query) {
                $query->where('name','service_provider');
             })->where('account_verified','!=',null)->orderBy('id','DESC')->get();
            //  if($request->state !==null){
            //     $consultant_ids = User::whereHas('profile', function ($query) {
            //         $query->where('state',$request->state);
            //     })->orderBy('id','DESC')->get();
            // }
  //  return json_encode($consultant_ids);
      

        
         $search = null;

         if($request->has('search'))
         {
            if($request->search){
                $search = $request->search;

                // return $request->search;

                // return "search";
                $available = false;


               
                    $consultant_ids = User::whereHas('roles', function ($query) {
                        $query->where('name','service_provider');
                    })
                    
                     // ->whereLike('name', 'LIKE', "%{$request->search}%")
                    ->where('name', 'LIKE', "%" .$request->search . "%")
                    ->whereIn('id',$consultant_ids)
                    ->groupBy('id')
                    ->where('account_verified','!=',null)
                    ->orderBy('id','DESC')
                    ->get();
    
                   // return json_encode($consultant_ids);
             
             
                

             
             
            }
        }
         

        foreach($consultant_ids as $doctor)
        {
            $user_table = User::find($doctor->id);
            $doctor->doctordetail = $user_table->getDoctorDetail($doctor->id);
            $user_table->profile; 
            $doctor->categoryData = $user_table->getCategoryData($doctor->id);
            $doctor->additionals = $user_table->getAdditionals($doctor->id);
            //$doctor->insurances = $user_table->getInsurnceData($doctor->id);
            $doctor->getServices = $user_table->getServices($doctor->id);
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
           
            if($user_table->profile)
            {
                $doctor->profile->bio = $user_table->profile->about;
                $doctor->profile->qualification = $user_table->profile->qualification;
                $doctor->totalRating = $user_table->profile->rating;
                $doctor->profile->location = ["name"=>$user_table->profile->location_name,"lat"=>$user_table->profile->lat,"long"=>$user_table->profile->long];
                $data = [
                    'categoryData' => $doctor->categoryData,
                    'additionals' =>  $doctor->additionals,
                    'filters' => $doctor->filters,
                    'subscriptions' => $doctor->subscriptions,
                    'custom_fields' => $doctor->custom_fields,
                    'profile' => $doctor->profile,
                    'doctordetail' => $doctor->doctordetail,
                    'reviewcount' => $doctor->reviewcount,
                    'feedback' => $doctor->feedback,
                    'account_verified' => $doctor->account_verified,
                    'service_type' => $doctor->service_type,
                    'rating' => $doctor->totalRating,
                    'bio' => $doctor->profile->bio,
                    'qualification' => $doctor->profile->qualification,
                    'location' =>  $doctor->profile->location,
                    'getServices' => $doctor->getServices
                ];



                

                    if($id != "all" && $service_id != "all")
                    { 
                        $count = 0;
                    // $category_ids = \App\Model\Category::where('category_id', $id)->where('parent_id', $id)->get();

                        $in_course = \App\Model\SpCourse::where('course_id',$course_id)->groupBy('sp_id')->pluck('sp_id');
                        // check category
                        $in_category = \App\Model\CategoryServiceProvider::where('sp_id', $doctor->id)->where('category_id', $id)->exists();
                        if($in_category == true)
                        {
                            $count++;
                        }
                        // check service
                        // $check_service = new \App\Model\SpServiceType();
                        // $in_service = $check_service->checkServiceSubscribe($doctor->id, $service_id);
                        $get_match = $this->objArraySearch($doctor['getServices'],'service_id', $service_id);
                        if($get_match != null)
                        {
                            $count++;
                        }

                        if($count == 2)
                        {
                            array_push($doctors, $data);
                        }

                        $count = 0;

                    }
                    elseif($id != "all" && $service_id == "all")
                    { 
                        // filter by category only
                        $in_category = \App\Model\CategoryServiceProvider::where('sp_id', $doctor->id)->where('category_id', $id)->exists();
                        if($in_category == true)
                        {
                            array_push($doctors, $data);
                        }
                    }
                    elseif($id == "all" && $service_id != "all")
                    {
                        if(sizeof($doctor['getServices']) > 0)
                        {
                            $get_match = $this->objArraySearch($doctor['getServices'],'service_id', $service_id);
                            if($get_match != null)
                            {
                                array_push($doctors, $data);
                            }
                        }
                    }
                    else
                    { 
                        array_push($doctors, $data);
                    // return json_encode($doctors);
                    }
               
            }
           
        }
       // return json_encode($doctors);   
        // $data = json_decode($doctors[1]['getServices']);

        // return json_encode(is_array($data));

        // $data = $doctors[1]['getServices'];
      //  return json_encode($doctors);
        // return json_encode(is_array($data));


        $doctors = $this->paginate($doctors, 10);
        $states=\App\Model\State::where('country_id', '=', 101)->pluck('name', 'id');

        
        if($id != "all")
        {
            $doctors->withPath('/user/experts/'.$id);
        }
        else
        {
            $doctors->withPath('/user/experts/');
        }

        if(\Config::get('client_connected') && \Config::get('client_data')->domain_name=='iedu'){
            return view('vendor.iedu.detail')
            ->with(
                [
                    'courses'  => null,
                    'emsats' => null,
                    'categorys' =>  $categorys,
                    'services'  =>  $services,
                    'doctors'   =>  $doctors,
                    'filters'   =>  $filters,
                    'id'        =>  $id,
                    'course_id' =>  null,
                    'emsat_id' => null,
                    'service_id'    =>  $service_id,
                    'current_date' => $current_date,
                    'search'    =>  $search,
                    'states'  => $states
                ]
            );
        }

        //return json_encode($doctor['getServices']);

        return view('vendor.care_connect_live.category')
            ->with(
                [
                    'categorys' =>  $categorys,
                    'filters'   =>  $filters,
                    'services'  =>  $services,
                    'doctors'   =>  $doctors,
                    'id'        =>  $id,
                    'service_id'    =>  $service_id,
                    'current_date' => $current_date,
                    'search'    =>  $search,
                    'states'  => $states
                ]
            );
            

       }
        else{
            $user = User::where('id',Auth::user()->id)->first();
            return redirect('/edit/profile');
        }

    }




    public function courseExpertlisting($course_id= null,$id="all", $service_id="all", Request $request)
    {
      
        if(Auth::user()->profile)
        {
        if($course_id != null && $course_id != '')
        {           
                $timezone = $request->header('timezone');
                if(!$timezone){
                    $timezone = 'Asia/Kolkata';
                }
                $dateznow = new DateTime("now", new DateTimeZone($timezone));
                $datenow = $dateznow->format('Y-m-d H:i:s');
                $current_date = $dateznow->format('Y-m-d');
                $categorys = Category::where('enable','1')->where('deleted_at',NULL)->get();
                if(\Config::get('client_connected') && \Config::get('client_data')->domain_name=='iedu'){
                $categorys = Category::where('enable','1')->where('parent_id',NULL)->where('deleted_at',NULL)->get();
                    if($course_id){
                    // $course_ids = explode(",",$request->course_id);
                    $courses = \App\Model\Course::get();
                    }
                
                }
                $filters = [];
                $doctors = [];
                $state_id = null;
                $state_name = null;
                if(isset($request->state)){
                    $state_id = 0;
                    $state_name = $request->state;
                    $state = \App\Model\State::where('name',$request->state)->first();
                    if($state){
                        $state_id = $state->id;
                    } 
                }
                
                $per_page = (isset($request->per_page)?$request->per_page:10);
                
                $raw_filters = Filtertype::with(['options' => function($query) {
                    return $query->select(['id', 'option_name','filter_type_id','image','description','video','banner','price'])->orderBy('option_name','ASC');
                }])->groupby('filter_name')->get();
            
                foreach ($raw_filters as $key => $filter) {
                    if($filter->options->count()>0){
                    
                    $filters[] = array(
                        'id'=>$filter->id,
                        'category_id'=>$filter->category_id,
                        'filter_name'=>$filter->filter_name,
                        'preference_name'=>$filter->preference_name,
                        'is_multi'=>$filter->is_multi,
                        'options'=>$filter->options,
                    ); 
                    }
            }
            
            
            $services = Service::where('enable','1')->where('need_availability','1')->get();

                /* for doctor Listing */

                if(\Config::get('client_connected') && \Config::get('client_data')->domain_name=='iedu'){
                    $sp_ids =[];
                    if($course_id){
                        // $course_ids = explode(",",$request->course_id);
                        $sp_ids = \App\Model\SpCourse::where('course_id',$course_id)->groupBy('sp_id')->pluck('sp_id');
                    }
                
                // return $sp_ids;
                if($course_id != "all")
                {
                    //return 'ddf';
                        $consultant_ids = User::whereHas('roles', function ($query) {
                            $query->where('name','service_provider');
                        })
                        ->whereHas('spcourse', function ($query) use($sp_ids,$course_id) {
                            
                                    $query->whereIn('sp_id',$sp_ids);
                            
                                })
                        ->where('account_verified','!=',null)->orderBy('id','DESC')->get();
                }
                else
                {
                    $sp_ids = \App\Model\SpCourse::groupBy('sp_id')->pluck('sp_id');
                    $consultant_ids = User::whereHas('roles', function ($query) {
                        $query->where('name','service_provider');
                    })->whereHas('spcourse', function ($query) use($sp_ids,$course_id) {
                            
                        $query->whereIn('sp_id',$sp_ids);
                
                    })
                    
                    ->where('account_verified','!=',null)->orderBy('id','DESC')->get();
                }
                    
                }
                else
                {
                    $consultant_ids = User::whereHas('roles', function ($query) {
                        $query->where('name','service_provider');
                    })->where('account_verified','!=',null)->orderBy('id','DESC')->get();
            
            
                }


                
                $search = null;

                if($request->has('search'))
                {
                    if($request->search){
                        $search = $request->search;

                        // return $request->search;

                        // return "search";
                        $available = false;


                        if(\Config::get('client_connected') && \Config::get('client_data')->domain_name=='iedu'){
                            // $sp_ids =[];
                            if($course_id){
                                // $course_ids = explode(",",$request->course_id);
                                $sp_ids = \App\Model\SpCourse::where('course_id',$course_id)->groupBy('sp_id')->pluck('sp_id');
                            }
                            if($request->emsat_id){
                
                                $sp_ids = \App\Model\SpEmsat::where('emsat_id',$request->emsat_id)->groupBy('sp_id')->pluck('sp_id');
                            }
                
                            $consultant_ids = User::whereHas('roles', function ($query) {
                                $query->where('name','service_provider');
                            })
                            ->whereHas('spcourse', function ($query) use($sp_ids) {
                                        $query->whereIn('sp_id',$sp_ids);
                                    })
                            ->where('name', 'LIKE', "%" .$request->search . "%")
                            ->where('account_verified','!=',null)->orderBy('id','DESC')->get();
                        }
                        else
                        {
                            $consultant_ids = User::whereHas('roles', function ($query) {
                                $query->where('name','service_provider');
                            })
                            
                            // ->whereLike('name', 'LIKE', "%{$request->search}%")
                            ->where('name', 'LIKE', "%" .$request->search . "%")
                            ->whereIn('id',$consultant_ids)
                            ->groupBy('id')
                            ->where('account_verified','!=',null)
                            ->orderBy('id','DESC')
                            ->get();
            
                        // return json_encode($consultant_ids);
                    
                    
                        }

                    
                    
                    }
                }
                

                foreach($consultant_ids as $doctor)
                {
                    $user_table = User::find($doctor->id);
                    $doctor->doctordetail = $user_table->getDoctorDetail($doctor->id);
                    $user_table->profile; 
                    $doctor->categoryData = $user_table->getCategoryData($doctor->id);
                    $doctor->additionals = $user_table->getAdditionals($doctor->id);
                    //$doctor->insurances = $user_table->getInsurnceData($doctor->id);
                    $doctor->getServices = $user_table->getServices($doctor->id);
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
                
                    if($user_table->profile)
                    {
                        $doctor->profile->bio = $user_table->profile->about;
                        $doctor->profile->qualification = $user_table->profile->qualification;
                        $doctor->totalRating = $user_table->profile->rating;
                        $doctor->profile->location = ["name"=>$user_table->profile->location_name,"lat"=>$user_table->profile->lat,"long"=>$user_table->profile->long];
                        $data = [
                            'categoryData' => $doctor->categoryData,
                            'additionals' =>  $doctor->additionals,
                            'filters' => $doctor->filters,
                            'subscriptions' => $doctor->subscriptions,
                            'custom_fields' => $doctor->custom_fields,
                            'profile' => $doctor->profile,
                            'doctordetail' => $doctor->doctordetail,
                            'reviewcount' => $doctor->reviewcount,
                            'feedback' => $doctor->feedback,
                            'account_verified' => $doctor->account_verified,
                            'service_type' => $doctor->service_type,
                            'rating' => $doctor->totalRating,
                            'bio' => $doctor->profile->bio,
                            'qualification' => $doctor->profile->qualification,
                            'location' =>  $doctor->profile->location,
                            'getServices' => $doctor->getServices
                        ];



                        if(\Config::get('client_connected') && \Config::get('client_data')->domain_name=='iedu')
                        {

                            if($course_id != "all" && $id != "all" && $service_id != "all")
                            { 
                                $count = 0;
                            // $category_ids = \App\Model\Category::where('category_id', $id)->where('parent_id', $id)->get();

                                $in_course = \App\Model\SpCourse::where('course_id',$course_id)->groupBy('sp_id')->pluck('sp_id');
                                // check category
                                $in_category = \App\Model\CategoryServiceProvider::where('sp_id', $doctor->id)->where('category_id', $id)->exists();
                                if($in_category == true)
                                {
                                    $count++;
                                }
                                // check service
                                // $check_service = new \App\Model\SpServiceType();
                                // $in_service = $check_service->checkServiceSubscribe($doctor->id, $service_id);
                                $get_match = $this->objArraySearch($doctor['getServices'],'service_id', $service_id);
                                if($get_match != null)
                                {
                                    $count++;
                                }

                                if($count == 2)
                                {
                                    array_push($doctors, $data);
                                }

                                $count = 0;

                            }
                            elseif($course_id != "all" && $id != "all" && $service_id == "all")
                            { 
                                // filter by category only
                                $in_category = \App\Model\CategoryServiceProvider::where('sp_id', $doctor->id)->where('category_id', $id)->exists();
                                if($in_category == true)
                                {
                                    array_push($doctors, $data);
                                }
                            }
                            elseif($course_id != "all" && $id == "all" && $service_id != "all")
                            {
                                if(sizeof($doctor['getServices']) > 0)
                                {
                                    $get_match = $this->objArraySearch($doctor['getServices'],'service_id', $service_id);
                                    if($get_match != null)
                                    {
                                        array_push($doctors, $data);
                                    }
                                }
                            }
                            else
                            { 
                                array_push($doctors, $data);
                            // return json_encode($doctors);
                            }



                        }
                        else
                        {

                            if($id != "all" && $service_id != "all")
                            { 
                                $count = 0;
                            // $category_ids = \App\Model\Category::where('category_id', $id)->where('parent_id', $id)->get();

                                $in_course = \App\Model\SpCourse::where('course_id',$course_id)->groupBy('sp_id')->pluck('sp_id');
                                // check category
                                $in_category = \App\Model\CategoryServiceProvider::where('sp_id', $doctor->id)->where('category_id', $id)->exists();
                                if($in_category == true)
                                {
                                    $count++;
                                }
                                // check service
                                // $check_service = new \App\Model\SpServiceType();
                                // $in_service = $check_service->checkServiceSubscribe($doctor->id, $service_id);
                                $get_match = $this->objArraySearch($doctor['getServices'],'service_id', $service_id);
                                if($get_match != null)
                                {
                                    $count++;
                                }

                                if($count == 2)
                                {
                                    array_push($doctors, $data);
                                }

                                $count = 0;

                            }
                            elseif($id != "all" && $service_id == "all")
                            { 
                                // filter by category only
                                $in_category = \App\Model\CategoryServiceProvider::where('sp_id', $doctor->id)->where('category_id', $id)->exists();
                                if($in_category == true)
                                {
                                    array_push($doctors, $data);
                                }
                            }
                            elseif($id == "all" && $service_id != "all")
                            {
                                if(sizeof($doctor['getServices']) > 0)
                                {
                                    $get_match = $this->objArraySearch($doctor['getServices'],'service_id', $service_id);
                                    if($get_match != null)
                                    {
                                        array_push($doctors, $data);
                                    }
                                }
                            }
                            else
                            { 
                                array_push($doctors, $data);
                            // return json_encode($doctors);
                            }
                        }
                    }
                
                }
            // return json_encode($doctors);   
                // $data = json_decode($doctors[1]['getServices']);

                // return json_encode(is_array($data));

                // $data = $doctors[1]['getServices'];
            //  return json_encode($doctors);
                // return json_encode(is_array($data));
            
                $doctors = $this->paginate($doctors, 10);
                
                if($id != "all")
                {
                    $doctors->withPath('/user/experts/'.$id);
                }
                else
                {
                    $doctors->withPath('/user/experts/');
                }

                if(\Config::get('client_connected') && \Config::get('client_data')->domain_name=='iedu'){
                    return view('vendor.iedu.detail')
                    ->with(
                        [
                            'courses'  => $courses,
                            'emsats' => null,
                            'categorys' =>  null,
                            'filters'   =>  $filters,
                            'services'  =>  $services,
                            'doctors'   =>  $doctors,
                            'id'        =>  null,
                            'emsat_id'  => null,
                            'course_id' =>  $course_id,
                            'service_id'    =>  null,
                            'current_date' => $current_date,
                            'search'    =>  $search,
                            'booking_type' => 'course',
                            'booking_id' => $course_id
                        ]
                    );
                }

                //return json_encode($doctor['getServices']);

                return view('vendor.care_connect_live.category')
                    ->with(
                        [
                            'categorys' =>  $categorys,
                            'filters'   =>  $filters,
                            'services'  =>  $services,
                            'doctors'   =>  $doctors,
                            'id'        =>  $id,
                            'service_id'    =>  $service_id,
                            'current_date' => $current_date,
                            'search'    =>  $search
                        ]
                    );
        }          

       }
        else{
            $user = User::where('id',Auth::user()->id)->first();
            return redirect('/edit/profile');
        }

    }



    public function emsatExpertlisting($emsat_id= null,$id="all", $service_id="all", Request $request)
    {
      if($emsat_id != null && $emsat_id != '')
      {
        if(Auth::user()->profile)
        {
        $timezone = $request->header('timezone');
        if(!$timezone){
            $timezone = 'Asia/Kolkata';
        }
        $dateznow = new DateTime("now", new DateTimeZone($timezone));
        $datenow = $dateznow->format('Y-m-d H:i:s');
        $current_date = $dateznow->format('Y-m-d');
        $categorys = Category::where('enable','1')->where('deleted_at',NULL)->get();
        if(\Config::get('client_connected') && \Config::get('client_data')->domain_name=='iedu'){
         $categorys = Category::where('enable','1')->where('parent_id',NULL)->where('deleted_at',NULL)->get();
             if($emsat_id){
            // $course_ids = explode(",",$request->course_id);
            $emsats = \App\Model\Emsat::get();
            }
           
        }
        $filters = [];
        $doctors = [];
        $state_id = null;
        $state_name = null;
        if(isset($request->state)){
            $state_id = 0;
            $state_name = $request->state;
            $state = \App\Model\State::where('name',$request->state)->first();
            if($state){
                $state_id = $state->id;
            } 
        }
        
        $per_page = (isset($request->per_page)?$request->per_page:10);
        
        $raw_filters = Filtertype::with(['options' => function($query) {
            return $query->select(['id', 'option_name','filter_type_id','image','description','video','banner','price'])->orderBy('option_name','ASC');
        }])->groupby('filter_name')->get();
       
        foreach ($raw_filters as $key => $filter) {
            if($filter->options->count()>0){
              
               $filters[] = array(
                'id'=>$filter->id,
                'category_id'=>$filter->category_id,
                'filter_name'=>$filter->filter_name,
                'preference_name'=>$filter->preference_name,
                'is_multi'=>$filter->is_multi,
                'options'=>$filter->options,
               ); 
            }
       }
       
    
       $services = Service::where('enable','1')->where('need_availability','1')->get();

        /* for doctor Listing */

        if(\Config::get('client_connected') && \Config::get('client_data')->domain_name=='iedu'){
           // $sp_ids =[];
            if($emsat_id){
                // $course_ids = explode(",",$request->course_id);
                 $sp_ids = \App\Model\SpEmsat::where('emsat_id',$emsat_id)->groupBy('sp_id')->pluck('sp_id');
            }
           
           // return $sp_ids;
           if($emsat_id != "all")
           {
               //return 'ddf';
                $consultant_ids = User::whereHas('roles', function ($query) {
                    $query->where('name','service_provider');
                })
                ->whereHas('emsat', function ($query) use($sp_ids,$emsat_id) {
                    
                            $query->whereIn('sp_id',$sp_ids);
                    
                        })
                ->where('account_verified','!=',null)->orderBy('id','DESC')->get();
           }
           else
           {
            $sp_ids = \App\Model\SpEmsat::groupBy('sp_id')->pluck('sp_id');

            $consultant_ids = User::whereHas('roles', function ($query) {
                $query->where('name','service_provider');
             }) ->whereHas('emsat', function ($query) use($sp_ids,$emsat_id) {
                    
                $query->whereIn('sp_id',$sp_ids);
        
            })
             
             ->where('account_verified','!=',null)->orderBy('id','DESC')->get();
           }
            
        }
        else
        {
            $consultant_ids = User::whereHas('roles', function ($query) {
                $query->where('name','service_provider');
             })->where('account_verified','!=',null)->orderBy('id','DESC')->get();
    
    
        }


        
         $search = null;

         if($request->has('search'))
         {
            if($request->search){
                $search = $request->search;

                // return $request->search;

                // return "search";
                $available = false;


                if(\Config::get('client_connected') && \Config::get('client_data')->domain_name=='iedu'){
                    // $sp_ids =[];
                    
                     if($emsat_id){
         
                         $sp_ids = \App\Model\SpEmsat::where('emsat_id',$emsat_id)->groupBy('sp_id')->pluck('sp_id');
                     }
         
                     $consultant_ids = User::whereHas('roles', function ($query) {
                         $query->where('name','service_provider');
                      })
                       ->whereHas('emsat', function ($query) use($sp_ids) {
                                 $query->whereIn('sp_id',$sp_ids);
                             })
                       ->where('name', 'LIKE', "%" .$request->search . "%")
                      ->where('account_verified','!=',null)->orderBy('id','DESC')->get();
                 }
                 else
                 {
                    $consultant_ids = User::whereHas('roles', function ($query) {
                        $query->where('name','service_provider');
                    })
                    
                     // ->whereLike('name', 'LIKE', "%{$request->search}%")
                    ->where('name', 'LIKE', "%" .$request->search . "%")
                    ->whereIn('id',$consultant_ids)
                    ->groupBy('id')
                    ->where('account_verified','!=',null)
                    ->orderBy('id','DESC')
                    ->get();
    
                   // return json_encode($consultant_ids);
             
             
                 }

             
             
            }
        }
         

        foreach($consultant_ids as $doctor)
        {
            $user_table = User::find($doctor->id);
            $doctor->doctordetail = $user_table->getDoctorDetail($doctor->id);
            $user_table->profile;
            if($emsat_id){
                $sp_emsat = \App\Model\SpEmsat::where('emsat_id',$emsat_id)->where('sp_id',$user_table->id)->first();
                if($sp_emsat){
                    $doctor->price = $sp_emsat->price;
                }
            } 
            $doctor->categoryData = $user_table->getCategoryData($doctor->id);
            $doctor->additionals = $user_table->getAdditionals($doctor->id);
            //$doctor->insurances = $user_table->getInsurnceData($doctor->id);
            $doctor->getServices = $user_table->getServices($doctor->id);
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
           
            if($user_table->profile)
            {
                $doctor->profile->bio = $user_table->profile->about;
                $doctor->profile->qualification = $user_table->profile->qualification;
                $doctor->totalRating = $user_table->profile->rating;
                $doctor->profile->location = ["name"=>$user_table->profile->location_name,"lat"=>$user_table->profile->lat,"long"=>$user_table->profile->long];
                $data = [
                    'categoryData' => $doctor->categoryData,
                    'additionals' =>  $doctor->additionals,
                    'filters' => $doctor->filters,
                    'subscriptions' => $doctor->subscriptions,
                    'custom_fields' => $doctor->custom_fields,
                    'profile' => $doctor->profile,
                    'doctordetail' => $doctor->doctordetail,
                    'reviewcount' => $doctor->reviewcount,
                    'feedback' => $doctor->feedback,
                    'account_verified' => $doctor->account_verified,
                    'service_type' => $doctor->service_type,
                    'rating' => $doctor->totalRating,
                    'bio' => $doctor->profile->bio,
                    'qualification' => $doctor->profile->qualification,
                    'location' =>  $doctor->profile->location,
                    'getServices' => $doctor->getServices
                ];



                if(\Config::get('client_connected') && \Config::get('client_data')->domain_name=='iedu')
                {

                    if($emsat_id != "all" && $id != "all" && $service_id != "all")
                    { 
                        $count = 0;
                    // $category_ids = \App\Model\Category::where('category_id', $id)->where('parent_id', $id)->get();

                        $in_emsat = \App\Model\SpEmsat::where('emsat_id',$emsat_id)->groupBy('sp_id')->pluck('sp_id');
                        // check category
                        $in_category = \App\Model\CategoryServiceProvider::where('sp_id', $doctor->id)->where('category_id', $id)->exists();
                        if($in_category == true)
                        {
                            $count++;
                        }
                        // check service
                        // $check_service = new \App\Model\SpServiceType();
                        // $in_service = $check_service->checkServiceSubscribe($doctor->id, $service_id);
                        $get_match = $this->objArraySearch($doctor['getServices'],'service_id', $service_id);
                        if($get_match != null)
                        {
                            $count++;
                        }

                        if($count == 2)
                        {
                            array_push($doctors, $data);
                        }

                        $count = 0;

                    }
                    elseif($emsat_id != "all" && $id != "all" && $service_id == "all")
                    { 
                        // filter by category only
                        $in_category = \App\Model\CategoryServiceProvider::where('sp_id', $doctor->id)->where('category_id', $id)->exists();
                        if($in_category == true)
                        {
                            array_push($doctors, $data);
                        }
                    }
                    elseif($emsat_id != "all" && $id == "all" && $service_id != "all")
                    {
                        if(sizeof($doctor['getServices']) > 0)
                        {
                            $get_match = $this->objArraySearch($doctor['getServices'],'service_id', $service_id);
                            if($get_match != null)
                            {
                                array_push($doctors, $data);
                            }
                        }
                    }
                    else
                    { 
                        array_push($doctors, $data);
                    // return json_encode($doctors);
                    }



                }
                else
                {

                    if($id != "all" && $service_id != "all")
                    { 
                        $count = 0;
                    // $category_ids = \App\Model\Category::where('category_id', $id)->where('parent_id', $id)->get();

                        $in_course = \App\Model\SpCourse::where('course_id',$course_id)->groupBy('sp_id')->pluck('sp_id');
                        // check category
                        $in_category = \App\Model\CategoryServiceProvider::where('sp_id', $doctor->id)->where('category_id', $id)->exists();
                        if($in_category == true)
                        {
                            $count++;
                        }
                        // check service
                        // $check_service = new \App\Model\SpServiceType();
                        // $in_service = $check_service->checkServiceSubscribe($doctor->id, $service_id);
                        $get_match = $this->objArraySearch($doctor['getServices'],'service_id', $service_id);
                        if($get_match != null)
                        {
                            $count++;
                        }

                        if($count == 2)
                        {
                            array_push($doctors, $data);
                        }

                        $count = 0;

                    }
                    elseif($id != "all" && $service_id == "all")
                    { 
                        // filter by category only
                        $in_category = \App\Model\CategoryServiceProvider::where('sp_id', $doctor->id)->where('category_id', $id)->exists();
                        if($in_category == true)
                        {
                            array_push($doctors, $data);
                        }
                    }
                    elseif($id == "all" && $service_id != "all")
                    {
                        if(sizeof($doctor['getServices']) > 0)
                        {
                            $get_match = $this->objArraySearch($doctor['getServices'],'service_id', $service_id);
                            if($get_match != null)
                            {
                                array_push($doctors, $data);
                            }
                        }
                    }
                    else
                    { 
                        array_push($doctors, $data);
                    // return json_encode($doctors);
                    }
                }
            }
           
        }
       // return json_encode($doctors);   
        // $data = json_decode($doctors[1]['getServices']);

        // return json_encode(is_array($data));

        // $data = $doctors[1]['getServices'];
      //  return json_encode($doctors);
        // return json_encode(is_array($data));


        $doctors = $this->paginate($doctors, 10);
        
        if($id != "all")
        {
            $doctors->withPath('/user/experts/'.$id);
        }
        else
        {
            $doctors->withPath('/user/experts/');
        }

        if(\Config::get('client_connected') && \Config::get('client_data')->domain_name=='iedu'){
            return view('vendor.iedu.detail')
            ->with(
                [
                    'courses'  => null,
                    'emsats' => $emsats,
                    'categorys' =>  null,
                    'filters'   =>  $filters,
                    'services'  =>  $services,
                    'doctors'   =>  $doctors,
                    'id'        => null,
                    'emsat_id'  => $emsat_id,
                    'course_id' =>  null,
                    'service_id'    =>  null,
                    'current_date' => $current_date,
                    'search'    =>  $search,
                    'booking_type' => 'emsat',
                    'booking_id' => $emsat_id
                ]
            );
        }

        //return json_encode($doctor['getServices']);

        return view('vendor.care_connect_live.category')
            ->with(
                [
                    'categorys' =>  $categorys,
                    'filters'   =>  $filters,
                    'services'  =>  $services,
                    'doctors'   =>  $doctors,
                    'id'        =>  $id,
                    'service_id'    =>  $service_id,
                    'current_date' => $current_date,
                    'search'    =>  $search
                ]
            );
            

       }
        else{
            $user = User::where('id',Auth::user()->id)->first();
            return redirect('/edit/profile');
        }
      }

    }

    public function getWallet(Request $request)
    {
        $user = Auth::user();
        $balance = 0;
        $wallet = Wallet::where('user_id',$user->id)->first();
        if($wallet){
            $balance = $wallet->balance;
        }
        $payments = [];
            $transaction_type=null;
            
	    	$payments = Payment::where('to',$user->id)->
            whereHas('transaction', function ($query) use($transaction_type) {
                            if($transaction_type){
                                $query->where('transaction_type', $transaction_type);
                            }
                            $query->where('status','!=','pending');
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
            $payments = $this->paginate($payments, 5);
            //return $payments;
            if(\Config::get('client_connected') && \Config::get('client_data')->domain_name=='iedu')
            {
                return view('vendor.iedu.wallet')->with('balance',$balance)->with('payments',$payments);
            }
            else
            {
                return view('vendor.care_connect_live.wallet')->with('balance',$balance)->with('payments',$payments);
            }
        
    }

    public function postWalletOrderId(Request $request)
    {
        try
        {
            $userId = Auth::user()->id;
            $amount = $request->input('amount');
            $api = new Api("rzp_test_Aal6QDJNaVoFUs", "liwfX8HlvSqUaki1qCvBFWJP");

            $order  = $api->order->create([
                'receipt'         =>    $userId,
                'amount'          =>    $request->input('amount') * 100,
                'currency'        =>    'INR'
            ]);

            return $order['id'];
        }
        catch(\Exception $ex)
        {
            return "error";
        }
    }

    public function postWallet(Request $request)
    {
        // return json_encode($request->all());

        // validate payment and save to db
        if(\Config::get('client_connected') && \Config::get('client_data')->domain_name=='iedu')
        {
            $transaction =  Transaction::create(array(
                'order_id'  =>  '1',
                'amount'    =>  $request->balance,
                'transaction_type'  =>  'add_money',
                //'status' => 'pending',
                'status'    =>  'success',
               // 'transaction_id'    =>  $request->input('razorpay_order_id'),
                'transaction_id'    => uniqid()  ,
                'module_table'  => 'add_money',
                'wallet_id' => Auth::user()->wallet->id,
                'amount'    =>  $request->amount ,
                'closing_balance'   => Auth::user()->wallet->balance
            ));

          
            if($transaction)
            {
                $transaction->walletdata->increment('balance', $transaction->amount);
                $transaction->closing_balance = $transaction->walletdata->balance;
                $transaction->save();
                
                $payment = Payment::create(array('from'=>1,'to'=>Auth::user()->id,'transaction_id'=>$transaction->id));
            }
            return "done";
        }
        else
        {

            try
            {
                $user = Auth::user();
                $requires_source_action = true;
                $transaction_type = 'deposit';
                if($user->hasrole('service_provider')){
                    $transaction_type = 'add_money';
                }
                $api = new Api("rzp_test_Aal6QDJNaVoFUs", "liwfX8HlvSqUaki1qCvBFWJP");
                $attributes  = array(
                    'razorpay_signature'    =>  $request->input('razorpay_signature'),
                    'razorpay_payment_id'   =>  $request->input('razorpay_payment_id'),
                    'razorpay_order_id'     =>  $request->input('razorpay_order_id')
                );

                $order = $api->utility->verifyPaymentSignature($attributes);

                // return "done";

                // fetch order id
                $fetch_order = $api->order->fetch($request->input('razorpay_order_id'));

                if($fetch_order['status'] == "paid")
                {
                    // add amount to wallet

                    Transaction::create(array(
                        'order_id'  =>  $request->input('razorpay_order_id'),
                        'amount'    =>  $request->balance,
                        'transaction_type'  =>  'add_money',
                        //'status' => 'pending',
                        'status'    =>  'success',
                    // 'transaction_id'    =>  $request->input('razorpay_order_id'),
                        'transaction_id'    =>  $request->input('razorpay_payment_id'),
                        'module_table'  => 'add_money',
                        'wallet_id' => Auth::user()->wallet->id,
                        'amount'    =>  $fetch_order['amount_paid'] / 100,
                        'closing_balance'   => Auth::user()->wallet->balance
                    ));

                    $transaction = Transaction::where('transaction_id', $request->input('razorpay_payment_id'))->first();
                    if($transaction)
                    {
                        $transaction->walletdata->increment('balance', $transaction->amount);
                        $transaction->closing_balance = $transaction->walletdata->balance;
                        $transaction->save();
                        
                        $payment = Payment::create(array('from'=>1,'to'=>Auth::user()->id,'transaction_id'=>$transaction->id));
                    }
                    if(Session::has('originUrl'))
                    {
                        return session('originUrl');
                    }
                    

                    return "done";
                }

                return "error";
            }
            catch(\Exception $ex)
            {
                return $ex;
            }
            catch(\Razorpay\Api\Errors\SignatureVerificationError $e){      
                return $e->getMessage();
            }
        }
    }

    public function getSlotsold( Request $request)
    {
        $input = $request->all();

        if(\Config::get('client_connected') && \Config::get('client_data')->domain_name=='physiotherapist'){
                $rules = [
                    'doctor_id' => 'required|exists:users,id',
                    'category_id' => 'required|exists:categories,id',
                ];
            }else{
                $rules = [
                    'doctor_id' => 'required|exists:users,id',
                    'service_id' => 'required|exists:services,id',
                    'category_id' => 'required|exists:categories,id',
                ];
            }
            if(!isset($request->applyoption)){
                $rules['date'] = 'required|date_format:Y-m-d';
            }
            $validator = Validator::make($request->all(),$rules);
            if ($validator->fails()) {
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                    $validator->getMessageBag()->first()), 400);
            }
            if(!isset($input['service_id'])){
                $service = CategoryServiceType::where('category_id',$input['category_id'])->first();
                $input['service_id'] = $service->service_id;
            }
            $timezone = $request->header('timezone');
            if(!$timezone){
                $timezone = 'Asia/Kolkata';
            }
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
           $feature = Helper::getClientFeatureExistWithFeatureType('Dynamic Sections','Master Interval');
           $duration_by_setting = true;
           $slots = [];
           $slot_duration = EnableService::where('type','slot_duration')->first();
           $add_mins  = 30 * 60;
           if($slot_duration){
               $add_mins = $slot_duration->value * 60;
           }
        
           if($feature){
               $slots =  Helper::getMasterSlots();
           }
          
           if(count($slots) > 0){
               $duration_by_setting = false;
               $sp_slots = $slots;
           }else{
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
                   //return json_encode($sp_slots);
               }
           }
           $dateznow = new DateTime("now", new DateTimeZone($timezone));
           $datenow = $dateznow->format('Y-m-d H:i:s');
           $current_date = $dateznow->format('Y-m-d');
           $currentTime    = strtotime ($datenow);
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
                      if($duration_by_setting){
                           $endDT = $starttime + $add_mins;
                           $end_time_new = date ("h:i a", $endtime);
                      }else{
                           $endDT = $endtime;
                           $end_time_new = date ("h:i a", $endtime);
                      }
                      // $starttime += $add_mins; // to check endtie=me
                      // $endtime_slot = date ("H:i:s", $starttime);

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
                           $array_of_time[] = ["time"=>$time,"end_time"=>$end_time_new,"available"=>$available];
                      }else if($input['date'] > $current_date){
                           $time = date ("h:i a", $starttime);
                           $array_of_time[] = ["time"=>$time,"end_time"=>$end_time_new,"available"=>$available];
                      }
                      if($duration_by_setting){
                           $starttime += $add_mins;
                      }else{
                           $starttime += 60*60;
                      }
                   }
                   $sp_slot_array[] = array('start_time'=>$start_time,'end_time'=>$end_time);
               }
           }
          
           // return view('vendor.care_connect_live.schedule_chat')->with(['slots'=>$sp_slot_array,'interval'=>$array_of_time,'date'=>$input['date']]  );
          return response(['status' => "success", 'statuscode' => 200,'message' => __('Slot List '), 'data' =>['slots'=>$sp_slot_array,'interval'=>$array_of_time,'date'=>$input['date']]], 200);
       }
       
     //   return view('vendor.care_connect_live.schedule_chat');

    }

    public function getSchedule(Request $request)
    {
        
        $sp_id = $request->doctor_id;
        if(Config::get('client_connected')&&Config::get('client_data')->domain_name=='iedu')
        {
            $sp_id = $request->expert_id;
        }
        $sp_data = \App\User::getDoctorDetail($sp_id);
        $timezone = $request->header('timezone');
        if(!$timezone){
            $timezone = 'Asia/Kolkata';
        }
        $dateznow = new DateTime("now", new DateTimeZone($timezone));
        $datenow = $dateznow->format('Y-m-d H:i:s');
        $current_date = $dateznow->format('Y-m-d');
        $currentTime    = strtotime ($datenow);
       
        $effectiveDate = date('Y-m-d', strtotime("+3 months", strtotime($current_date)));

        $period = CarbonPeriod::create($current_date, $effectiveDate);
        
        $weekMap = ['SU'=>0,'MO'=>1,'TU'=>2,'WE'=>3,'TH'=>4,'FR'=>5,'SA'=>6];
        $data = [];
       
        // Iterate over the period
        foreach ($period as $date) {
            $dates = $date->format('Y-m-d');
            $day = strtoupper(substr(Carbon::parse($date)->format('l'), 0, 2));

            $item = [
                'date' => $dates,
                'day'  => $day
            ];
           array_push($data,$item);
        }

        $doctor_id = $request->doctor_id;
        $category_id = $request->category_id;
        $service_id = $request->service_id;

       //return json_encode($sp_data);

        // Convert the period to an array of dates
      //  $dates = $period->toArray();
      // print_r($days); die();
      if(Config::get('client_connected')&&Config::get('client_data')->domain_name=='iedu')
      {
        return view('vendor.iedu.schedule_chat')->with('sp_data',$sp_data)->with('data',$data)->with(['doctor_id'=> $doctor_id, 'category_id'=> $category_id, 'service_id'=> $service_id]);
      }
      else
      {
        return view('vendor.care_connect_live.schedule_chat')->with('sp_data',$sp_data)->with('data',$data)->with(['doctor_id'=> $doctor_id, 'category_id'=> $category_id, 'service_id'=> $service_id]);
      }
       
    }



    public function getSlots(Request $request)
    {
       
        $timezone = $request->header('timezone');
        if(!$timezone){
            $timezone = 'Asia/Kolkata';
        }
        $duration_by_setting = true;
        $user = Auth::user();
        $input = $request->all();
        $from_date = $request->date.' 00:00:00';
	    $end_date = $request->date.' 23:59:59';
        $booked_slots = [];
        $get_booked_slots = \App\Model\Request::where('from_user',$user->id)
                                ->whereBetween('booking_date', [$from_date, $end_date])
                                ->whereHas('requesthistory',function($query) use($request){
                                   
                                        return $query->whereNotIn('status',['canceled','failed']);
                                  
                                })
                                ->get();
        if($get_booked_slots->count() > 0)
        {
                foreach($get_booked_slots as $bookslot)
                {
                    
                    $booking_date =  Carbon::parse($bookslot->booking_date)->format('Y-m-d');
                    $booking_t = Carbon::parse($bookslot->booking_date,'UTC')->setTimezone($timezone);
                    $booking_time = $booking_t->Format('h:i a');
                    $booking = $booking_date.$booking_time;
                    array_push($booked_slots,$booking);
                }
        }
       //return  $booked_slots;
        $day_number = [];
        $slot_duration = EnableService::where('type','slot_duration')->first();
        $add_mins  = 30 * 60;
        if($slot_duration)
        {
            $add_mins = $slot_duration->value * 60;
        }
        //$day = '';
       
            $sp_id = $request->doctor_id;
       
      
       
         $getData = \App\User::getDoctorDetail($sp_id);
        
         $weekMap = ['SU'=>0,'MO'=>1,'TU'=>2,'WE'=>3,'TH'=>4,'FR'=>5,'SA'=>6];
       
         $sp_slot_array = [];
        //  if(Config::get('client_connected')&&Config::get('client_data')->domain_name=='care_connect_live')
        //  {
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
         //}
        
            
                //    $day = strtoupper(substr(Carbon::parse($request->input('date'))->format('l'), 0, 2));
                //    $day_number = $weekMap[$day];
                //    $sp_slots = ServiceProviderSlot::where([
                //        'service_provider_id'=>$request->input('doctor_id'),
                //        'service_id'=>$request->input('service_id'),
                //        'day'=>$day_number,
                //        'category_id'=>$request->input('category_id'),
                //    ])->get();
                  //   return json_encode($sp_slots);
                
              
       
        $array_of_time = array ();
        $morning_array = [];
        $afternoon_array = [];
        $evening_array = [];

        $dateznow = new DateTime("now", new DateTimeZone($timezone));
        $datenow = $dateznow->format('Y-m-d H:i:s');
        $current_date = $dateznow->format('Y-m-d');
        $currentTime    = strtotime ($datenow);
       
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
                   if($duration_by_setting){
                        $endDT = $starttime + $add_mins;
                        $end_time_new = date ("h:i a", $endtime);
                   }else{
                        $endDT = $endtime;
                        $end_time_new = date ("h:i a", $endtime);
                   }
                   // $starttime += $add_mins; // to check endtie=me
                   // $endtime_slot = date ("H:i:s", $starttime);

                   $endtime_slot = date("H:i:s", $endDT);
                   $start_time_slot_utcdate = Carbon::parse($request->input('date').' '.$starttime_slot,$timezone)->setTimezone('UTC');
                   $starttime_slot_one_m = Carbon::parse($request->input('date').' '.$starttime_slot_one_m,$timezone)->setTimezone('UTC');
                   $end_time_slot_utcdate = Carbon::parse($request->input('date').' '.$endtime_slot,$timezone)->setTimezone('UTC');
                    //print_r($end_time_slot_utcdate);
                   $exist = \App\Model\Request::where('to_user',$request->input('doctor_id'))
                   // ->where('booking_date','<=',$end_time_slot_utcdate)
                    ->where('booking_date','=',$start_time_slot_utcdate)
                    ->orWhereBetween('booking_end_date',[$starttime_slot_one_m,$end_time_slot_utcdate])
                   ->whereHas('requesthistory', function ($query) {
                        $query->where('status','!=','canceled');
                    })
                   ->get();

                   //return json_encode($exist);
                   $available = true;
                   if(Config::get('client_connected') && (Config::get('client_data')->domain_name=='care_connect_live')){
                    if($exist->count()>5){
                        $available = false;
                    }
               }
                else
                {
                    if($exist->count()>0){
                        $available = false;
                   }
                }
               
                   if(isset($sp_slot->working_today) && $sp_slot->working_today=='n'){
                        $available = false;
                   }

                   $H = date("H", $starttime);
                   $mode = null;
                   if($H < 12){
                    $mode = "morning";
                   }elseif($H > 11 && $H < 18){
                    $mode = "afternoon";
                   }elseif($H > 17){
                    $mode = "evening";
                   }

                   
                    // print_r($input['date']);die;
                //    if($current_date == $request->input('date') && $starttime>=$currentTime){
                //         $time = date ("h:i a", $starttime);
                //         $array_of_time[] = [
                //             "time"  =>  $time,
                //             "end_time"  =>  $end_time_new,
                //             "available" =>  $available,
                //             "mode" =>  $mode
                //         ];
                //    }else if($request->date > $current_date){
                //         $time = date ("h:i a", $starttime);
                //         $array_of_time[] = [
                //             "time"  =>  $time,
                //             "end_time"  =>  $end_time_new,
                //             "available" =>  $available,
                //             "mode" =>  $mode
                //         ];
                //    }

                    $time = date ("h:i a", $starttime);
                    if(\Config::get('client_connected') && \Config::get('client_data')->domain_name=='care_connect_live'){
                        $check_slot_booked = $input['date'].$time;
                        if(in_array($check_slot_booked, $booked_slots))
                        {
                            $booked = true;
                        }
                        else
                        {
                            $booked = false;
                        }
    
                        // fix available
                        if($available == true && $booked == false)
                        {
                            $available = true;
                        }
                        else
                        {
                            $available = false;
                        }
                    }

                    
                  
                    
                    
                    
                    $item = [
                        "time"  =>  $time,
                        "end_time"  =>  $end_time_new,
                        "available" =>  $available
                        // "booked"    =>  $booked
                        // "mode" =>  $mode
                    ];
                    

                    if($mode == "morning")
                    {
                        array_push($morning_array, $item);
                    }
                    if($mode == "afternoon")
                    {
                        array_push($afternoon_array, $item);
                    }
                    if($mode == "evening")
                    {
                        array_push($evening_array, $item);
                    }

                    $data = [
                        "morning"   =>  $morning_array,
                        "afternoon" =>  $afternoon_array,
                        "evening"   =>  $evening_array
                    ];
                    

                   if($duration_by_setting){
                        $starttime += $add_mins;
                   }else{
                        $starttime += 60*60;
                   }
                }

                // if already in array
                // return json_encode($booked_slots);
              
                // $check_slot_booked = $request->date.$start_time;

                // // $check_slot_booked = json_encode($check_slot_booked);

                // // echo $check_slot_booked;
                // // die();
                


                // if(in_array($check_slot_booked, $booked_slots))
                // {
                //     $available = false;
                // }
                // else
                // {
                //     $available = true;
                // }
               


                // $sp_slot_array[] = array('start_time' => $start_time, 'end_time' => $end_time, 'available' => $available);
              // return json_encode($sp_slot_array);
            }
        }
        else{
            $data = [
                "morning"   =>  '',
                "afternoon" =>  '',
                "evening"   => ''
            ];
        }
       
        return json_encode($data);
       // print_r($getDate); die();
        //return $day_number;       
                  
          
              

        
        

    }


    public function checkSlotFullOrNot($slot_duration,$timezone,$add_slot_second,$input,$request_data){
       
        $dateznow = new DateTime("now", new DateTimeZone('UTC'));
        $date = self::roundToNearestMinuteInterval($dateznow,$slot_duration->value);
        $datenow = $date->format('Y-m-d H:i:s');
        $user_time_zone_slot = Carbon::parse($datenow)->setTimezone($timezone)->format('h:i a');
        $user_time_zone_date = Carbon::parse($datenow)->setTimezone($timezone)->format('Y-m-d');
        $end_time_slot_utcdate = Carbon::parse($datenow)->addSeconds($add_slot_second)->setTimezone('UTC')->format('Y-m-d H:i:s');
        $max_slot = '5';
        $exist = \App\Model\Request::where('to_user',$input['consultant_id'])
        ->whereBetween('booking_date', [$datenow, $end_time_slot_utcdate])
        ->whereHas('requesthistory', function ($query) {
            $query->where('status','!=','canceled');
        })
        ->where(function($query2) use ($request_data){
            if(isset($request_data->id))
                $query2->where('id','!=',$request_data->id);
        })
        ->get();
        
         if($exist->count()>5){
            return false;
        }else{
            return array('user_time_zone_slot'=>$user_time_zone_slot,'count'=>$exist->count(),'user_time_zone_date'=>$user_time_zone_date,'datenow'=>$datenow);

        }
      
        
    }
    public static function roundToNearestMinuteInterval(\DateTime $dateTime, $minuteInterval = 30)
    {
        return $dateTime->setTime(
        $dateTime->format('H'),
            ceil($dateTime->format('i') / $minuteInterval) * $minuteInterval,
            0
        );
    }



    public function confirmRequest(Request $request)
    {

        $user = Auth::user();
            if(!$user->hasrole('customer')){
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>'Invalid Valid user role must be role as customer'), 400);
            }
           
            $input = $request->all();
            $request_data = null;
            if(isset($request->request_id)){
                $request_data = \App\Model\Request::where('id',$request->request_id)->first();
                $dateznow = new DateTime("now", new DateTimeZone('UTC'));
                $datenow = $dateznow->format('Y-m-d H:i:s');
                $next_hour_time = strtotime($datenow) + 3600;
                if(strtotime($request_data->booking_date)<=$next_hour_time){
                    return response(array('status' => "error", 'statuscode' => 400, 'message' =>__('Request could not Re-Scheduled becuase request going live into next hour')), 400);
                }
                $input['consultant_id'] = $request_data->to_user;
                $input['service_id'] = $request_data->service_id;
            }

            $timezone = $request->header('timezone');
            if(!$timezone){
                $timezone = 'Asia/Kolkata';
            }
 
            $consult = User::find($input['consultant_id']);
            if(!$consult || !$consult->hasrole('service_provider')){
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>__('Consultant not found')), 400);
            }
            $category_id = $consult->getCategoryData($input['consultant_id']);
            $categoryservicetype_id = CategoryServiceType::where(['category_id'=>$category_id->id,'service_id'=>$input['service_id']])->first();
            // print_r($categoryservicetype_id);die;
            $spservicetype_id = null;
            if($categoryservicetype_id){
                $spservicetype_id = \App\Model\SpServiceType::where(['category_service_id'=>$categoryservicetype_id->id,'sp_id'=>$input['consultant_id']])->first();
            }
            if(!$spservicetype_id){
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>__("Service not found into the $category_id->name category")), 400);
            }
            $slot_duration = \App\Model\EnableService::where('type','slot_duration')->first();
            $unit_price = \App\Model\EnableService::where('type','unit_price')->first();
            $per_minute = $spservicetype_id->price/$unit_price->value;
            $slot_minutes = $slot_duration->value;
            $add_slot_second = $slot_duration->value * 60;
            if($request_data){
                $total_charges = $request_data->requesthistory->total_charges;
            }else{
                $total_charges = $slot_minutes * $per_minute;
                if(Config::get('client_connected')&&Config::get('client_data')->domain_name=='care_connect_live' && $categoryservicetype_id->price_fixed!=null){
                    $total_charges = $categoryservicetype_id->price_fixed;
                }
            }
            $grand_total = $slot_minutes * $per_minute;
            if(Config::get('client_connected')&&Config::get('client_data')->domain_name=='care_connect_live' && $categoryservicetype_id->price_fixed!=null){
                $grand_total = $categoryservicetype_id->price_fixed;
            }
            $discount = 0;
            $timezone = $request->header('timezone');
            if(!$timezone){
                $timezone = 'Asia/Kolkata';
            }
            if($request->schedule_type=='schedule'){
            $connect_now_validation_disable = false;
            if(Config::get('client_connected') && (Config::get('client_data')->domain_name=='mp2r' || Config::get('client_data')->domain_name=='food')){
                $connect_now_validation_disable = true;
            }
		    $datenow = Carbon::parse($request->date.' '.$request->time,$timezone)->setTimezone('UTC')->format('Y-m-d H:i:s');
		    $datenow2 = Carbon::parse($request->date.' '.$request->time,$timezone)->addSeconds(60)->setTimezone('UTC')->format('Y-m-d H:i:s');
                $end_time_slot_utcdate = Carbon::parse($datenow)->addSeconds($add_slot_second)->setTimezone('UTC')->format('Y-m-d H:i:s');
                $user_time_zone_slot = Carbon::parse($datenow)->setTimezone($timezone)->format('h:i a');
                $user_time_zone_date = Carbon::parse($datenow)->setTimezone($timezone)->format('Y-m-d');
                // print_r($datenow);
                $exist = \App\Model\Request::where('to_user',$input['consultant_id'])
                ->whereBetween('booking_date', [$datenow2, $end_time_slot_utcdate])
                ->whereHas('requesthistory', function ($query) use($connect_now_validation_disable) {
                    $query->where('status','!=','canceled');
                    if($connect_now_validation_disable)
                        $query->where('schedule_type','!=','instant');

                })
                ->where(function($query2) use ($request_data){
                    if(isset($request_data->id))
                        $query2->where('id','!=',$request_data->id);
                })
                ->get();
                if($exist->count()>0){
                    return response(array('status' => "error", 'statuscode' => 400, 'message' =>__("Request could not create $request->time slot already full")), 400);
                }
            }else{
                $data = [];
                while ($data==false) {
                    $data = $this->checkSlotFullOrNot($slot_duration,$timezone,$add_slot_second,$input,$request_data);
                    $slot_duration->value = $slot_duration->value + 30;
                }
                $user_time_zone_date = $data['user_time_zone_date'];
                $user_time_zone_slot = $data['user_time_zone_slot'];
                $datenow = $data['datenow'];

            }
            $service_tax = 0;
            $tax_percantage = 15;
            if(Config::get('client_connected') && Config::get('client_data')->domain_name=='homedoctor'){
                $tax_percantage = 15;
                $service_tax = round(($total_charges * $tax_percantage)/100,2);
                $grand_total = $service_tax + $total_charges;
            }
            $coupon_validation = [];
            $coupon = false;
            if(isset($request->coupon_code) && $request_data==null){
                $coupon_validation = self::couponCodeValidation($request->coupon_code,$user,$total_charges,$service_tax);
                if($coupon_validation['status']=='error'){
                   return response($coupon_validation,400);
                }
                if($coupon_validation['status']=='success'){
                    $coupon = true;
                    $grand_total = $coupon_validation['grand_total'];
                    $discount = $coupon_validation['discount'];
                }
            }
            if(isset($input['package_id'])){
                $subscribe = Helper::isSusbScribe([
                    'user_id'=>$user->id,
                    'package_id'=>$input['package_id']
                ]);
                if(!$subscribe){
                    $package = Package::where('id',$input['package_id'])->first();
                    if($package){
                        $grand_total = $package->price;
                        $total_charges = $package->price;
                        $discount = 0;
                    }
                }else{
                    $grand_total = 0;
                    $total_charges = 0;
                    $discount = 0;
                }
            }
            if(isset($input['payment_type'])){
                $grand_total = 0;
                $total_charges = 0;
                $discount = 0;
            }
            $is_paid = $this->checkIsPaid($user->id,$input['consultant_id'],$datenow);
            $left_minute = 0;
            if(!$is_paid){
                $startTime = Carbon::now();
                $finishTime = Carbon::now()->addDays(7);
                $left_minute = $finishTime->diffInSeconds($startTime)/60;
                $grand_total = 0;
                $discount = $total_charges;
            }
            $minimum_balance_value = null;
            $minimum_balance = \App\Model\EnableService::where('type','minimum_balance')->first();
            if($minimum_balance)
                $minimum_balance_value = $minimum_balance->value;
            return response(['status' => "success", 'statuscode' => 200,'message' => __('Booking confirmed'), 'data'=>[
                'is_paid'=>$is_paid,
                'left_minute'=>$left_minute,
                'total'=>$total_charges,
                'service_tax'=>$service_tax,
                'tax_percantage'=>$tax_percantage,
                'discount'=>$discount,
                'grand_total'=>$grand_total,
                'book_slot_time'=>$user_time_zone_slot,
                'book_slot_date'=>$user_time_zone_date,
                'coupon'=>$coupon,
                'minimum_balance'=>$minimum_balance_value]], 200);
            return response(array('status' => "error", 'statuscode' => 400, 'message' =>__($message)), 400);

    }

    private function checkIsPaid($user_id,$doctor_id,$booking_date){
        $is_paid = true;
        $request_data = RequestData::where(['from_user'=>$user_id,'to_user'=>$doctor_id])
        ->whereHas('requesthistory', function ($query) {
            $query->whereNotIn('status',['failed','canceled']);
        })->latest()->first();
        // print_r($request_data->requesthistory->status);die;
        if($request_data && $request_data->is_paid && ($request_data->requesthistory->status=='in-progress' || $request_data->requesthistory->status=='accept' || $request_data->requesthistory->status=='completed')){
            $created_at_7d = strtotime($request_data->booking_date) + 604800;
            $current_d = strtotime($booking_date);
            if($current_d<=$created_at_7d)
                $is_paid = false;
        }
        return $is_paid;
    }

    public static function couponCodeValidation($coupon_code,$user,$total_charges,$service_tax=0)
    {
        $total_charges = $total_charges + $service_tax;
        $dateznow = new DateTime("now", new DateTimeZone('UTC'));
        $current_date = $dateznow->format('Y-m-d');
        $coupon = Coupon::where('end_date','>=',$current_date)->where('coupon_code',strtoupper($coupon_code))->first();
        if(!$coupon){
            return array('status' => "error", 'statuscode' => 400, 'message' =>__("Applied Coupon Code was Expired"));
        }
        if($total_charges<$coupon->minimum_value){
            return array('status' => "error", 'statuscode' => 400, 'message' =>__("Coupon code not APPLIED required minimum price value is $coupon->minimum_value and your cart price is $total_charges"));
        }
        $used = CouponUsed::where(['user_id'=>$user->id,'coupon_id'=>$coupon->id])->first();
        if($used){
            return array('status' => "error", 'statuscode' => 400, 'message' =>__("Coupon Code Already Used"));
        }
        $used_count = CouponUsed::where(['coupon_id'=>$coupon->id])->get();
        if($used_count->count() >= $coupon->limit){
            return array('status' => "error", 'statuscode' => 400, 'message' =>__("Used Coupon limit full"));
        }
        $discount = 0;
        if($coupon->percent_off){
            $discount = ($total_charges * $coupon->percent_off)/100;
            if($discount<0){
                $discount = 0;
            }
        }
        if($coupon->value_off){
            $discount =  $coupon->value_off;
            if($discount<0){
                $discount = 0;
            }
        }
        if($discount>$coupon->maximum_discount_amount){
            $discount = $coupon->maximum_discount_amount;
        }
        $total_charges = $total_charges -  $discount;
        if($total_charges<0){
            $total_charges = 0;
        }
        return array('status' => "success",'discount'=>(int)$discount,'grand_total'=>(int)$total_charges,'coupon_id'=>$coupon->id);
    }

    public function insertRequestDetail($request_id,$input){
        $requestdetail= RequestDetail::firstOrCreate(['request_id'=>$request_id]);
        if($requestdetail){
            $requestdetail->first_name =  isset($input['first_name'])?$input['first_name']:null;
            $requestdetail->last_name =  isset($input['last_name'])?$input['last_name']:null;
            $requestdetail->service_for =  isset($input['service_for'])?$input['service_for']:null;
            $requestdetail->home_care_req =  isset($input['home_care_req'])?$input['home_care_req']:null;
            $requestdetail->service_address =  isset($input['service_address'])?$input['service_address']:null;
            $requestdetail->lat =  isset($input['lat'])?$input['lat']:null;
            $requestdetail->long =  isset($input['long'])?$input['long']:null;
            $requestdetail->reason_for_service =  isset($input['reason_for_service'])?$input['reason_for_service']:null;
            $requestdetail->country_code =  isset($input['country_code'])?$input['country_code']:null;
            $requestdetail->phone_number =  isset($input['phone_number'])?$input['phone_number']:null;
        }
        if(isset($input['duties'])){
            $duties_raw = [
                "duties"=>explode(",",$input['duties'])
            ];
            $custom_info = new CustomInfo();
            $custom_info->raw_detail = json_encode($duties_raw);
            $custom_info->info_type = 'duties';
            $custom_info->ref_table = 'requests';
            $custom_info->ref_table_id = $request_id;
            $custom_info->status = 'success';
            $custom_info->save();
        }
        $requestdetail->save();
    }


    public function createRequest(Request $request)
    {
        //return $request->all();
        $url = request()->headers->get('referer');
        session(['originUrl' => $url]);
        $user = Auth::user();
            $input = $request->all();
            $total_hours = 0;
            $request_data = null;
            
            if(isset($request->request_id)){
                $request_data = \App\Model\Request::where('id',$request->request_id)->first();
                $input['consultant_id'] = $request_data->to_user;
                $input['service_id'] = $request_data->service_id;
                $dateznow = new DateTime("now", new DateTimeZone('UTC'));
                $datenow = $dateznow->format('Y-m-d H:i:s');
                $next_hour_time = strtotime($datenow) + 3600;
                if(strtotime($request_data->booking_date)<=$next_hour_time){
                    return response(array('status' => "error", 'type' => 'alert', 'statuscode' => 400, 'message' =>__('Request could not Re-Scheduled becuase request going live into next hour')), 400);
                }
            }
            $consult = User::find($input['consultant_id']);
            if(!$consult || !$consult->hasrole('service_provider')){
                return response(array(
                    'status' => "error",
                    'statuscode' => 400,
                    'type' => 'alert',
                    'message' =>__('Consultant not found')),400);
            }

            if(Config::get('client_connected')&&Config::get('client_data')->domain_name=='iedu' )
            {
                $spservicetype_id = \App\Model\SpServiceType::where([
                    'sp_id'=>$input['consultant_id']
                ])->first();
    
                if(!$spservicetype_id){
                    return response(array(
                        'status' => "error",
                        'statuscode' => 400,
                        'message' =>__("Service not found")
                        ), 400);
                }
                $categoryservicetype_id = CategoryServiceType::where([
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
            }
            else
            {
                $category_id = $consult->getCategoryData($input['consultant_id']);
                if(!$category_id){
                    return response(array(
                        'status' => "error",
                        'statuscode' => 400,
                        'type' => 'alert',
                        'message' =>__("This Vendor have not assigned any Category")
                    ), 400);
                }
                $categoryservicetype_id = CategoryServiceType::where(['category_id'=>$category_id->id,'service_id'=>$input['service_id']])->first();
                $spservicetype_id = null;
                if($categoryservicetype_id){
                    $spservicetype_id = \App\Model\SpServiceType::where(['category_service_id'=>$categoryservicetype_id->id,'sp_id'=>$input['consultant_id']])->first();
                }
                if(!$spservicetype_id){
                    return response(array(
                        'status' => "error",
                        'statuscode' => 400,
                        'type' => 'alert',
                        'message' =>__("Service not found into the $category_id->name category")
                        ), 400);
                }

            }
            
           
            $slot_duration = \App\Model\EnableService::where('type','slot_duration')->first();
            $unit_price = \App\Model\EnableService::where('type','unit_price')->first();
            $per_minute = $spservicetype_id->price/$unit_price->value;
            $slot_minutes = $slot_duration->value;
            $add_slot_second = $slot_duration->value * 60;
            if($request_data){
                $total_charges = $request_data->requesthistory->total_charges;
                $grand_total= $g_total = $request_data->requesthistory->total_charges;
            }else{
                $total_charges = $slot_minutes * $per_minute;
                $grand_total= $g_total = $slot_minutes * $per_minute;
                if(Config::get('client_connected')&&Config::get('client_data')->domain_name=='iedu' )
                {
                        if($input['booking_type']=='emsat'){
                            $emsat = \App\Model\Emsat::where('id',$input['booking_id'])->first();
                            if(!$emsat){
                                return response(array(
                                    'status' => "error",
                                    'statuscode' => 400,
                                    'message' =>__("Emsat not found")
                                ), 400);
                            }
                            $sp_emsat = \App\Model\SpEmsat::where(['emsat_id'=>$emsat->id,'sp_id'=>$input['consultant_id']])->first();
                            $total_charges = $sp_emsat->price;
                            $grand_total= $g_total = $sp_emsat->price;
                        }
                        
                }
                if(Config::get('client_connected')&&Config::get('client_data')->domain_name=='care_connect_live' && $categoryservicetype_id->price_fixed!=null){
                    $total_charges = $categoryservicetype_id->price_fixed;
                    $grand_total= $g_total = $categoryservicetype_id->price_fixed;
                }
            }
            //return $grand_total;
            $service_tax = 0;
            $tax_percantage = 0;

            if(Config::get('client_connected')&&Config::get('client_data')->domain_name=='iedu')
            {
                $transaction_fee = \App\Model\EnableService::where('type','service_charge')->first();
                if($transaction_fee){
                    $tax_percantage = $transaction_fee->value;
                    $service_tax = round(($total_charges * $tax_percantage)/100,2);
                    $grand_total = $service_tax + $total_charges;
                }

            }
            //return $grand_total;
            $discount = 0;
            $timezone = $request->header('timezone');
            if(!$timezone){
                $timezone = 'Asia/Kolkata';
            }
            $input['timezone'] = $timezone;
            $input['service_tax'] = $service_tax;
            $input['tax_percantage'] = $tax_percantage;
            $coupon_validation = [];
            $coupon = false;
            if(isset($request->coupon_code) && $request_data==null){
                $coupon_validation = self::couponCodeValidation($request->coupon_code,$user,$total_charges,$service_tax);
                if($coupon_validation['status']=='error'){
                   return response($coupon_validation,400);
                }
                if($coupon_validation['status']=='success'){
                    $coupon = true;
                    $grand_total= $g_total = $coupon_validation['grand_total'];
                    $discount = $coupon_validation['discount'];
                }
            }

            if(Config::get('client_connected')&&Config::get('client_data')->domain_name!='iedu')
            {
                if(isset($input['package_id'])){
                    $subscribe = Helper::isSusbScribe([
                        'user_id'=>$user->id,
                        'package_id'=>$input['package_id']
                    ]);
                    if(!$subscribe){
                        $package = Package::where('id',$input['package_id'])->first();
                        if($package){
                            $g_total = $package->price;
                            $grand_total = 0;
                            $total_charges = $package->price;
                            $discount = 0;
                        }
                    }else{
                        $grand_total = $g_total = 0;
                        $total_charges = 0;
                        $discount = 0;
                    }
                }

                if(isset($input['payment_type'])){
                    $grand_total = $g_total = 0;
                    $total_charges = 0;
                    $discount = 0;
                }
            }
            
            $wallet_type = 'user_wallet';
            $user_wallet = $user;
            // if(Helper::chargeFromSP()){
            //     $user_wallet = $consult;
            //     $wallet_type = 'vendor_wallet';
            // }
            $user_time_zone_slot = '';
            $user_time_zone_date = '';
            if($request->schedule_type=='schedule'){
                    if($request->has('request_type')){
                        
                    }else{
                        $connect_now_validation_disable = false;
                        if(Config::get('client_connected') && (Config::get('client_data')->domain_name=='mp2r' || Config::get('client_data')->domain_name=='food')){
                            $connect_now_validation_disable = true;
                        }
                        
                        $datenow = Carbon::parse($request->current_date.' '.$request->time,$timezone)->setTimezone('UTC')->format('Y-m-d H:i:s');
                        $datenow2 = Carbon::parse($request->current_date.' '.$request->time,$timezone)->addSeconds(60)->setTimezone('UTC')->format('Y-m-d H:i:s');
                        $end_time_slot_utcdate = Carbon::parse($datenow)->addSeconds($add_slot_second)->setTimezone('UTC')->format('Y-m-d H:i:s');
                        $end_time_slot_utcdate2 = Carbon::parse($datenow)->addSeconds($add_slot_second-1)->setTimezone('UTC')->format('Y-m-d H:i:s');
                        $user_time_zone_slot = Carbon::parse($datenow)->setTimezone($timezone)->format('h:i a');
                        $user_time_zone_date = Carbon::parse($datenow)->setTimezone($timezone)->format('Y-m-d');
                        if(Config::get('client_connected') && (Config::get('client_data')->domain_name=='care_connect_live'))
                         {
                            $max_slot = '5';
                            $exist = \App\Model\Request::where('to_user',$input['consultant_id'])
                            ->whereBetween('booking_date', [$datenow2, $end_time_slot_utcdate])
                                ->whereHas('requesthistory', function ($query) use($connect_now_validation_disable) {
                                    $query->where('status','!=','canceled');
                                    $query->where('status','!=','failed');
                                    if($connect_now_validation_disable)
                                        $query->where('schedule_type','!=','instant');
                
                                })
                                ->where(function($query2) use ($request_data){
                                    if(isset($request_data->id))
                                        $query2->where('id','!=',$request_data->id);
                                })
                            ->get();
                            //return json_encode($exist);
                            if($exist->count()>5){
                                return response(array('status' => "error", 'statuscode' => 400, 'message' =>__("Request could not create $request->time slot already full")), 400);
                            }
                        }
                        else
                        {
                            $exist = \App\Model\Request::where('to_user',$input['consultant_id'])
                            ->whereBetween('booking_date', [$datenow, $end_time_slot_utcdate2])
                            ->whereHas('requesthistory', function ($query) use($connect_now_validation_disable) {
                                $query->where('status','!=','canceled');
                                $query->where('status','!=','failed');
                                if($connect_now_validation_disable)
                                    $query->where('schedule_type','!=','instant');
                            })
                            ->where(function($query2) use ($request_data){
                                if(isset($request_data->id))
                                    $query2->where('id','!=',$request_data->id);
                            })
                            ->get();
                            if($exist->count()>0){
                                return response(array('status' => "error", 'statuscode' => 400, 'message' =>__("Request could not create $request->time slot already full")), 400);
                            }
                        }
                    }
            }else{
                $data = [];
                while ($data==false) {
                    $data = $this->checkSlotFullOrNot($slot_duration,$timezone,$add_slot_second,$input,$request_data);
                    $slot_duration->value = $slot_duration->value + $slot_minutes;
                }
                $user_time_zone_date = $data['user_time_zone_date'];
                $user_time_zone_slot = $data['user_time_zone_slot'];
                $datenow = $data['datenow'];
            }
            $is_paid = $this->checkIsPaid($user->id,$input['consultant_id'],$datenow);
            if(!$is_paid){
                $grand_total = $g_total = 0;
                $discount = $total_charges;
            }

            if(Config::get('client_connected')&&Config::get('client_data')->domain_name!='iedu')
            {
                if($request->has('request_step') && $request->request_step=='confirm'){
                    return response([
                        'status' => "success",
                        'statuscode' => 200,
                        'type' => 'popup',
                        'message' => __('Booking Confirmed'),
                        'data'=>[
                            'total'=>$total_charges,
                            'discount'=>$discount,
                            'grand_total'=>$grand_total,
                            'service_tax'=>$service_tax,
                            'tax_percantage'=>$tax_percantage,
                            'coupon'=>$coupon]
                        ], 200);
                }else if($request->has('request_step') && $request->request_step=='create'){
                        $input['balance'] = $grand_total;
                        $input['grand_total'] = $grand_total;
                        $input['total_hours'] = $total_hours;
                        $input['total_charges'] = $total_charges + $service_tax;
                        $input['discount'] = $discount;
                        $input['per_minute'] = $per_minute;
                        $input['user'] = $user;
                        $input['coupon_validation'] = $coupon_validation;
                        if(Config::get('client_connected') && Config::get('client_data')->domain_name=='homedoctor'){
                            if($user_wallet->wallet->balance<$grand_total){
                                    return response([
                                        'status' => "error",
                                        'statuscode' => 200,
                                        'message' => __('Request Not Created '),
                                        'data'=>['amountNotSufficient'=>true,
                                        'wallet_type'=>$wallet_type]
                                    ], 200);
                            }
                            $response =  Helper::createPaymentByAlRajahiBank($input,$user);
                        }else{
                            $response =  Helper::createPayment($input,$user);
                        }
                        return response($response,$response['statuscode']);
                }else{ 
                    if(!$request->has('request_type')){
                        if(Config::get('client_connected') && (Config::get('client_data')->domain_name=='mp2r' || Config::get('client_data')->domain_name=='food')){

                        }else{
                            // print_r($user_wallet->wallet);die;
                            $minimum_balance = \App\Model\EnableService::where('type','minimum_balance')->first();
                            if($user_wallet->wallet->balance<$grand_total && !$minimum_balance){
                                $amnt = $grand_total - $user_wallet->wallet->balance;
                                if($request_data==null)
                                    return response([
                                        'status' => "error",
                                        'statuscode' => 200,
                                        'message' => __("Request could not be created, need to add money $amnt"),
                                        'data'=>['amountNotSufficient'=>true,
                                        'wallet_type'=>$wallet_type,'minimum_balance'=>null,'message'=>"Request could not be created, need to add money $amnt"]
                                    ], 200);
                            }
                            if($minimum_balance && $minimum_balance->value && $user_wallet->wallet->balance<($minimum_balance->value + $grand_total)){
                                $amnt = ($minimum_balance->value + $grand_total) - $user_wallet->wallet->balance;
                                $currency = \App\Model\EnableService::where('type','currency')->first();
                                if($request_data==null)
                                    return response([
                                        'status' => "error",
                                        'statuscode' => 200,
                                        'message' => __("Request could not be created, need to add money $amnt to maintain balance $minimum_balance->value"),
                                        'data'=>['amountNotSufficient'=>true,
                                        'wallet_type'=>$wallet_type,'minimum_balance'=>$minimum_balance->value,'message'=>"Request could not be created, need to add money $amnt $currency->value"]
                                    ], 200);
                            }
                        }
                    }
                }
             }
             if(Config::get('client_connected')&&Config::get('client_data')->domain_name=='iedu')
             {
                $minimum_balance = \App\Model\EnableService::where('type','minimum_balance')->first();
                if($user_wallet->wallet->balance<$grand_total && !$minimum_balance && $category->payment_type=='online'){
                    $amnt = $grand_total - $user_wallet->wallet->balance;
                    if($request_data==null)
                        return response([
                            'status' => "error",
                            'statuscode' => 200,
                            'message' => __("Request could not be created, need to add money $amnt"),
                            'data'=>['amountNotSufficient'=>true,
                            'wallet_type'=>$wallet_type,'minimum_balance'=>null,'message'=>"Request could not be created, need to add money $amnt"]
                        ], 200);
                }
              $minimum_balance = \App\Model\EnableService::where('type','minimum_balance')->first();
                if($minimum_balance && $minimum_balance->value && $user_wallet->wallet->balance<($minimum_balance->value + $grand_total)){
                    $amnt = ($minimum_balance->value + $grand_total) - $user_wallet->wallet->balance;
                    $currency = \App\Model\EnableService::where('type','currency')->first();
                    if($request_data==null)
                        return response([
                            'status' => "error",
                            'statuscode' => 200,
                            'message' => __("Request could not be created, need to add money $amnt to maintain balance $minimum_balance->value"),
                            'data'=>['amountNotSufficient'=>true,
                            'wallet_type'=>$wallet_type,'minimum_balance'=>$minimum_balance->value,'message'=>"Request could not be created, need to add money $amnt $currency->value"]
                        ], 200);
                }
             }
            // }
            $message = 'Something went wrong';
            if($request_data){
                if(Config::get('client_connected')&&Config::get('client_data')->domain_name!='iedu')
                {
                    if($request->has('filter_id')){
                        $request_data->request_category_type = 'filter_option';
                        $request_data->request_category_type_id = $input['filter_id'];
                    }
                }
                $request_data->booking_date = $datenow;
                $request_data->requesthistory->schedule_type = $request->schedule_type;
                $request_data->requesthistory->save();
                $request_data->save();
                $notification = new Notification();
                $notification->sender_id = $user->id;
                $notification->receiver_id = $input['consultant_id'];
                $notification->module_id = $request_data->id;
                $notification->module ='request';
                $notification->notification_type ='RESCHEDULED_REQUEST';
                $notification->message =__('notification.rescheduled_text', ['user_name' => $user->name]);
                $notification->save();
               // $notification->push_notification(array($input['consultant_id']),array('pushType'=>'RESCHEDULED_REQUEST','request_id'=>$request_data->id,'message'=>__('notification.rescheduled_text', ['user_name' => $user->name])));
                return response(['status' => "success", 'statuscode' => 200,'message' => __('Request Re-Scheduled'),'data'=>['amountNotSufficient'=>false]], 200);
            }else{
                $second_oponion = false;
                $sr_request = new \App\Model\Request();
                // if($is_paid){
                //     $sr_request->is_paid = 1;
                // }else{
                //     $sr_request->is_paid = 0;
                // }
                $sr_request->from_user = $user->id;
                $sr_request->booking_date = $datenow;

                $sr_request->to_user = $input['consultant_id'];
                $sr_request->service_id = $input['service_id'];
                $sr_request->sp_service_type_id = ($spservicetype_id)?$spservicetype_id->id:null;
          
                    if($request->has('request_type')){
                        $sr_request->request_type = $input['request_type'];
                        $sr_request->total_hours = $total_hours;
                        $sr_request->payment = 'pending';
                    }
                    if($request->has('filter_id')){
                        $sr_request->request_category_type = 'filter_option';
                        $sr_request->request_category_type_id = $input['filter_id'];
                    }
                    
                    if($sr_request->save()){
                        if($request->has('option_ids')){
                            $this->insertRequestSymptoms($sr_request,$input);
                        }

                    

                        if(isset($request->second_oponion) && ($request->second_oponion==='true'||$request->second_oponion===true)){
                            $second_oponion = true;
                            $sr_request->request_type = 'second_oponion';
                            $sr_request->save();
                            $this->addSecondOponion($sr_request,$input);
                        }
                        $this->insertRequestDetail($sr_request->id,$input);
                        /* Requests Dates Saving... */
                        if($request->has('request_type')){
                            $dates = explode(',',$input['dates']);
                            foreach ($dates as $key => $date) {
                                $start_time_multi = Carbon::parse($date.' '.$input['start_time'],$timezone)->setTimezone('UTC')->format('Y-m-d H:i:s');
                                $end_time_multi = Carbon::parse($date.' '.$input['end_time'],$timezone)->setTimezone('UTC')->format('Y-m-d H:i:s');
                                $requestdate  = new RequestDate();
                                $requestdate->request_id = $sr_request->id;
                                $requestdate->start_date_time = $start_time_multi;
                                $requestdate->end_date_time = $end_time_multi;
                                $requestdate->save();
                            }
                        }
                        $requesthistory = new \App\Model\RequestHistory();
                        $requesthistory->duration = 0;
                        $requesthistory->discount = $discount;
                        $requesthistory->service_tax = $service_tax;
                        $requesthistory->tax_percantage = $tax_percantage;
                        $requesthistory->without_discount = $total_charges;
                        $requesthistory->total_charges = $grand_total;
                        $requesthistory->schedule_type = $request->schedule_type;
                        $requesthistory->status = 'pending';
                        $requesthistory->source_from = 'WEB';
                        $requesthistory->request_id = $sr_request->id;
                        if(isset($coupon_validation['status']) && $coupon_validation['status']=='success'){
                            $requesthistory->coupon_id = $coupon_validation['coupon_id'];
                            $couponused = new CouponUsed();
                            $couponused->user_id =  $user->id;
                            $couponused->coupon_id =  $coupon_validation['coupon_id'];
                            $couponused->save();
                        }
                        if($requesthistory->save()){
                            $used_packages = $subscribe_plan =false;
                            if(isset($input['package_id'])){
                                $used_packages = true;
                                $subscribe = Helper::isSusbScribe([
                                    'user_id'=>$user->id,
                                    'package_id'=>$input['package_id']
                                ]);
                                if(!$subscribe){
                                    $subscribepackage = Helper::subscribePackage([
                                        'user_id'=>$user->id,
                                        'package_id'=>$input['package_id']]);
                                    if($subscribepackage){
                                        $grand_total = 0;
                                        $total_charges = 0;
                                        $discount = 0;
                                    }else{
                                        $used_packages = false;
                                    }
                                }else{
                                    $grand_total = 0;
                                    $total_charges = 0;
                                    $discount = 0;
                                }
                            }
                            if(isset($input['payment_type'])){
                                $subscribe_plan = true;
                                $grand_total = 0;
                                $total_charges = 0;
                                $discount = 0;
                            }
                            if($used_packages){
                                $requesthistory->module_table = 'packages';
                                $requesthistory->module_id = $input['package_id'];
                                $requesthistory->save();

                                $userpackage  = UserPackage::where([
                                    'user_id'=>$user->id,
                                    'package_id'=>$input['package_id'],
                                ])->first();
                                $userpackage->decrement('available_requests',1);
                            }else if($subscribe_plan){
                                $requesthistory->module_table = 'subscribe_plans';
                                $requesthistory->module_id = null;
                                $requesthistory->save();
                            }else{
                                if($wallet_type=='vendor_wallet'){
                                    $withdrawal_to = array(
                                        'balance'=>$grand_total,
                                        'user'=>$sr_request->cus_info,
                                        'sp'=>$sr_request->sr_info,
                                        'from_id'=>1,
                                        'request_id'=>$sr_request->id,
                                        'status'=>'succeeded'
                                    );
                                    Transaction::createWithdrawalFromSP($withdrawal_to);
                                }else{
                                    $status = 'succeeded';
                                    if($request->has('request_type')){
                                        $status = 'user-pending';
                                    }
                                    $withdrawal_to = array(
                                        'balance'=>$grand_total,
                                        'user'=>$sr_request->cus_info,
                                        'from_id'=>$sr_request->sr_info->id,
                                        'request_id'=>$sr_request->id,
                                        'status'=>$status
                                    );
                                    Transaction::createWithdrawal($withdrawal_to);
                                    $deposit_to = array(
                                        'balance'=>$grand_total,
                                        'user'=>$sr_request->sr_info,
                                        'from_id'=>$sr_request->cus_info->id,
                                        'request_id'=>$sr_request->id,
                                        'status'=>'vendor-pending'
                                    );
                                    Transaction::createDeposit($deposit_to);
                                }
                            }
                        }
                        $service_type = \App\Model\Service::where('id',$input['service_id'])->first();
                        $notification = new Notification();
                        $notification->sender_id = $user->id;
                        $notification->receiver_id = $input['consultant_id'];
                        $notification->module_id = $sr_request->id;
                        $notification->module ='request';
                        $notification->notification_type ='NEW_REQUEST';
                        $message = __('notification.new_req_text', ['user_name' => $user->name,'service_type'=>($service_type)?($service_type->type):'']);
                        $notification->message =$message;
                        $notification->save();
                        // $push_data = ["template_name" => 'booking', "consultant_id" =>$input['consultant_id'], "user_id" => $user->id, "request_id"=>$sr_request->id, "service_id" => $input['service_id'], "category_id" => $request->category_id ];
                        // $get_email_template = DB::table('templates')->where('template_name', 'booking' )->first();
    
                        // $get_user_details = DB::table('users')->where('id', $user->id)->first();
    
                        // $get_consultant_details = DB::table('users')->where('id', $input['consultant_id'] )->first();
    
                        // $get_booking_details = DB::table('requests')->where('id', $sr_request->id)->first();
    
                        // $get_service_details = DB::table('services')->where('id', $input['service_id'] )->first();
    
                        // $template_text = $get_email_template->message;
    
                        // if($get_user_details)
                        // {
                        //     $template_text = str_replace("%booking_date", $get_booking_details->booking_date, $template_text);
                        //     $template_text = str_replace("%type", $get_service_details->type, $template_text);
                        //     $template_text = str_replace("%doctor_name", $get_consultant_details->user_name, $template_text);
                        //     $template_text = str_replace("%doctor_email", $get_consultant_details->email, $template_text);
                        //     $template_text = str_replace("%user_name", $get_user_details->user_name, $template_text);
                        //     $template_text = str_replace("%email", $get_user_details->email, $template_text);
                        // }
           
                       
                        // if($get_email_template)
                        // {
                        //     // $user_name = 'abc';
                        //     // $data = [
                        //     //     'user_name'	=>	$user_name
                        //     // ];
    
                        //     $data = [
                        //         'msg_text'	=>	$template_text
                        //     ];
    
                        //     $emails_to = array(
                        //         'email' =>  $get_user_details->email,
                        //         'name'  =>  $get_user_details->user_name,
                        //         'subject'	=>	$get_email_template->template_name
                        //     );
                            
                        //     \Mail::send('emails.generic', $data, function($message) use ($emails_to)
                        //     {
                        //         $message->to($emails_to['email'], $emails_to['name'])->subject($emails_to['subject']);
                        //     });
                        // }
                        if(Config::get('client_connected')&&Config::get('client_data')->domain_name=='care_connect_live'){ 
                      
                        $push_data = ["template_name" => 'booking', "consultant_id" =>$input['consultant_id'], "user_id" => $user->id, "request_id"=>$sr_request->id, "service_id" => $input['service_id'], "category_id" => $request->category_id ];
                     
    
                        $job = (new RequestSmsEmail($push_data));
                        dispatch($job);
                        }
                        $notification->push_notification(array($input['consultant_id']),array(
                            'request_id'=>$sr_request->id,
                            'pushType'=>'NEW_REQUEST',
                            'is_second_oponion'=>$second_oponion,
                            'message'=>$message
                        ));
                        return response(['status' => "success", 'statuscode' => 200,'message' => __('New Request Created '),'data'=>[
                            'amountNotSufficient'=>false,
                            'total_charges'=>$total_charges,
                            'service_tax'=>$service_tax,
                            'is_paid'=>$is_paid,
                            'tax_percantage'=>$tax_percantage,
                            'book_slot_time'=>$user_time_zone_slot,
                            'book_slot_date'=>$user_time_zone_date,
                            'is_second_oponion'=>$second_oponion,
                            'request'=>['id'=>$sr_request->id],
                        ]], 200);
                }

                if(Config::get('client_connected') && Config::get('client_data')->domain_name =='iedu')
                {
                    $sr_request->request_category_type = $input['booking_type'];
                    $sr_request->request_category_type_id = $input['booking_id'];
                    $sr_request->to_user = isset($input['consultant_id']) ? $input['consultant_id'] : '';
                    $sr_request->service_id =isset($input['service_id']) ? $input['service_id'] : '';;
                    $sr_request->sp_service_type_id = isset($spservicetype_id)?$spservicetype_id->id:null;
                    if($sr_request->save()){
                        $sr_request->booking_end_date = $end_time_slot_utcdate;
                        $sr_request->save();
                        $this->insertRequestDetail($sr_request->id,$input);
                        $requesthistory = new \App\Model\RequestHistory();
                        $requesthistory->duration = 0;
                        $requesthistory->discount = $discount;
                        $requesthistory->service_tax = $service_tax;
                        $requesthistory->tax_percantage = $tax_percantage;
                        $requesthistory->without_discount = $total_charges;
                        $requesthistory->total_charges = $grand_total;
                        $requesthistory->schedule_type = $request->schedule_type;
                        $requesthistory->status = 'pending';
                        $requesthistory->request_id = $sr_request->id;
                        if(isset($coupon_validation['status']) && $coupon_validation['status']=='success'){
                            $requesthistory->coupon_id = $coupon_validation['coupon_id'];
                            $couponused = new CouponUsed();
                            $couponused->user_id =  $user->id;
                            $couponused->coupon_id =  $coupon_validation['coupon_id'];
                            $couponused->save();
                        }
                        if($requesthistory->save()){
                        $used_packages = $subscribe_plan =false;
                        $status = 'succeeded';
                        if($request->has('request_type')){
                            $status = 'user-pending';
                        }
                        $withdrawal_to = array(
                            'balance'=>$grand_total,
                            'user'=>$sr_request->cus_info,
                            'from_id'=>$sr_request->sr_info->id,
                            'request_id'=>$sr_request->id,
                            'status'=>$status,
                            // 'category_payment'=>$category->payment_type,
                        );
                        Transaction::createWithdrawal($withdrawal_to);
                        $deposit_to = array(
                            'balance'=>$total_charges,
                            'user'=>$sr_request->sr_info,
                            'from_id'=>$sr_request->cus_info->id,
                            'request_id'=>$sr_request->id,
                            'status'=>'vendor-pending'
                        );
                        Transaction::createDeposit($deposit_to);
                        $service_type = \App\Model\Service::where('id',$input['service_id'])->first();
                        $notification = new Notification();
                        $notification->sender_id = $user->id;
                        $notification->receiver_id = $input['consultant_id'];
                        $notification->module_id = $sr_request->id;
                        $notification->module ='request';
                        $notification->notification_type ='NEW_REQUEST';
                        $message = __('notification.new_req_text', ['user_name' => $user->name,'service_type'=>($service_type)?($service_type->type):'']);
                        $notification->message =$message;
                        $notification->save();
                        $notification->push_notification(array($input['consultant_id']),array(
                            'request_id'=>$sr_request->id,
                            'pushType'=>'NEW_REQUEST',
                            'is_second_oponion'=>$second_oponion,
                            'message'=>$message
                        ));
                    }
                }
                
                    return response(['status' => "success", 'statuscode' => 200,'message' => __('New Request Created '),'data'=>[
                        'amountNotSufficient'=>false,
                        'total_charges'=>$total_charges,
                        'service_tax'=>$service_tax,
                        'is_paid'=>$is_paid,
                        'tax_percantage'=>$tax_percantage,
                        'book_slot_time'=>$user_time_zone_slot,
                        'book_slot_date'=>$user_time_zone_date,
                        'is_second_oponion'=>$second_oponion,
                        'request'=>['id'=>$sr_request->id],
                    ]], 200);
                }
            }

    }

    public function checkCoupon(Request $request)
    {
        // check coupon is valid and not used
        // check min value, limit, category and service id
        // check max discount

        $user = Auth::user();
        if(!$user->hasrole('customer')){
            return response(array('status' => "error", 'statuscode' => 400, 'message' =>'Invalid Valid user role must be role as customer'), 400);
        }

        $coupon_validation = self::couponCodeValidation($request->coupon_code, $user, $request->total, "0");

        return json_encode($coupon_validation);
    }


    public function postCancelRequest(Request $request)
    {
        $user = Auth::user();
        $customer = false;
        if($user->hasrole('customer')){
            $customer = true;
          // return response([]);
        }
        $input = $request->all();
        $request_data = null;
        $request_data = \App\Model\Request::where('id',$request->request_id)->first();
        $input['consultant_id'] = $request_data->to_user;
        $input['customer_id'] = $request_data->from_user;
        $input['service_id'] = $request_data->service_id;
        $dateznow = new DateTime("now", new DateTimeZone('UTC'));
        $datenow = $dateznow->format('Y-m-d H:i:s');
        $next_hour_time = strtotime($datenow) + 3600;
        if($request_data->requesthistory->status!='pending' && $request_data->requesthistory->status!='accept'){
            return response(array('status' => "error", 'statuscode' => 400, 'message' =>__('Request could not Cancel becuase request status is '.$request_data->requesthistory->status)), 400);
        }
        if(Config::get('client_connected')&&Config::get('client_data')->domain_name=='healtcaremydoctor'){
        }else{
            if(Config::get('client_connected')&&Config::get('client_data')->domain_name=='intely'){
                $next_hour_time = strtotime($datenow) + (3600*4);
                if(strtotime($request_data->booking_date)<=$next_hour_time){
                    return response(array('status' => "error", 'statuscode' => 400, 'message' =>__('Request could not Cancel becuase request going live into 4 hours')), 400);
                }
            }else{
                if(strtotime($request_data->booking_date)<=$next_hour_time){
                    return response(array('status' => "error", 'statuscode' => 400, 'message' =>__('Request could not Cancel becuase request going live into next hour')), 400);
                }
            }
        }
        if($request_data->requesthistory->total_charges){

            if(Helper::chargeFromSP()){
                if($customer){
                    $deposit_to = array(
                        'balance'=>$request_data->requesthistory->total_charges,
                        'user'=>$request_data->cus_info,
                        'sp'=>$request_data->sr_info,
                        'from_id'=>1,
                        'request_id'=>$request_data->id,
                        'status'=>'succeeded'
                    );
                    \App\Model\Transaction::createRefundForSP($deposit_to);
                }
            }else{
                $deposit_to = array(
                    'balance'=>$request_data->requesthistory->total_charges,
                    'user'=>$request_data->cus_info,
                    'from_id'=>$request_data->sr_info->id,
                    'request_id'=>$request_data->id,
                    'status'=>'succeeded'
                );
                \App\Model\Transaction::createRefund($deposit_to);
            }
        }
        $message = __('notification.can_req_text', ['user_name' => $user->name]);
        if(isset($input['cancel_reason'])){
            $request_data->requesthistory->cancel_reason = $input['cancel_reason'];
        }


         if(\Config::get('client_connected') && \Config::get('client_data')->domain_name=='care_connect_live'){
                //reorder token number
             
               $get_data = \App\Model\Request::where('to_user',Auth::user()->id)
                              // ->where('id','!=',$request->request_id)
                               ->where('booking_date',$request_data->booking_date)
                               ->whereHas('requesthistory', function ($query) {
                                   $query->whereNotIn('status',['failed','completed']);
                               })->orderby('id','asc')
                               ->where('token_number', '!=', NULL)->get();
                $get_selected_data = \App\Model\Request::where('to_user',Auth::user()->id)
                ->where('id','=',$request->request_id)
                ->where('booking_date',$request_data->booking_date)
                ->whereHas('requesthistory', function ($query) {
                    $query->whereNotIn('status',['failed','completed']);
                })
                ->where('token_number', '!=', NULL)->first();
                // $i = 1;

                $current_token_selected = $get_selected_data->token_number;

                foreach($get_data as $record)
                {
                    
                    if($record->id == $request->request_id)
                    {
                        
                            $update_token_number = Null;
                        
                    }
                    else
                    {
    

                        if($current_token_selected != null)
                        {
                            $update_token_number = $record->token_number;

                            if($record->token_number > $current_token_selected)
                            {
                                $update_token_number = $record->token_number - 1;
                            }
                        }


                        
                    }
                              

                  
                   $update_token = \App\Model\Request::where('to_user', Auth::user()->id)
                                   ->where('booking_date',$request_data->booking_date)
                                   ->where('id',$record->id)
                                   ->whereHas('requesthistory', function ($query) {
                                       $query->whereNotIn('status',['failed','completed']);
                                   })
                                   ->update([
                                       'token_number'  => $update_token_number
                                       ]);
                   if($record->id != $request->request_id && $record->token_number > $current_token_selected)
                   {
                   $status = ucwords(strtolower(str_replace('_', ' ', 'token updated')));
                   $notification = new Notification();
                   $notification->sender_id = $user->id;
                   if($customer){
                       $notification->receiver_id = $record->to_user;
                   }else{
                       $notification->receiver_id = $record->from_user;
                   }
                   $notification->module_id = $record->id;
                   $notification->module ='request';
                   $notification->notification_type = strtoupper($status);
                   $notification->message =__('notification.token_update_req', ['token_number' => $update_token_number]);
                   $notification->save();
                   $notification->push_notification(
                       array($notification->receiver_id),
                       array('pushType'=>strtoupper($status),
                           'message'=>__('notification.token_update_req', ['token_number' => $update_token_number]),
                           'request_time'=>$record->booking_date,
                           'service_type'=>$record->servicetype->type,
                           'sender_name'=>$user->name,
                           'sender_image'=>$user->profile_image,
                           'request_id'=>$record->id,
                           'call_id'=>'',
                           'token_number' => $update_token_number
                       ));
                   }
              //$i--;   
               }

           }
        $request_data->requesthistory->status = 'canceled';
        $request_data->requesthistory->save();
        // Request Log for status change
        $request_log = new \App\Model\RequestLog();
        $request_log->request_id = $request_data->id;
        $request_log->type = 'status_change';
        $request_log->request_status = 'canceled';
        $request_log->updated_by = $user->id;
        $request_log->role = 'service_provider';
        if($customer){
            $request_log->role = 'customer';
        }
        $request_log->save();

        $notification = new Notification();
        $notification->sender_id = $user->id;
        if($customer){
            $notification->receiver_id = $input['consultant_id'];
        }else{
            $notification->receiver_id = $input['customer_id'];
        }
        $notification->module_id = $request_data->id;
        $notification->module ='request';
        $notification->notification_type ='CANCELED_REQUEST';
        $notification->message =$message;
        $notification->save();
       // $notification->push_notification(array($notification->receiver_id),array('pushType'=>'CANCELED_REQUEST','request_id'=>$request_data->id,'message'=>$message));
        return response(['status' => "success", 'statuscode' => 200,'message' => $message,'data'=>['amountNotSufficient'=>false]], 200);
    } 

    private function millisecsBetween($dateOne, $dateTwo, $abs = true) {
        $func = $abs ? 'abs' : 'intval';
        return $func(strtotime($dateOne) - strtotime($dateTwo)) * 1000;
    }


    function formatSeconds( $seconds )
        {
        $hours = 0;
        $milliseconds = str_replace( "0.", '', $seconds - floor( $seconds ) );

        if ( $seconds > 3600 )
        {
            $hours = floor( $seconds / 3600 );
        }
        $seconds = $seconds % 3600;


        return str_pad( $hours, 2, '0', STR_PAD_LEFT )
            . gmdate( ':i:s', $seconds )
            . ($milliseconds ? ".$milliseconds" : '')
        ;
        }


    
    public function getwaitingroom(Request $request,$request_id,Advertisement $advertisement)
    {
        $dateznow = new DateTime("now", new DateTimeZone('UTC'));
        $datenow = $dateznow->format('Y-m-d');
        $advertisement = $advertisement->newQuery();
        $waiting_time = 0;
        $request_status = \App\Model\Request::select('id','service_id','join_time','from_user','to_user','booking_date','created_at','booking_date as bookingDateUTC','request_type','total_hours','user_by_hours','verified_hours','token_number')->where('id',$request_id)->first();
      
       
        if(Auth::check() && Auth::user()->hasRole('customer'))
        {
            $current_time = new DateTime();
            $booking_time = new DateTime($request_status->booking_date);

            $book_time = strtotime($booking_time->format('Y-m-d H:i:s'));
            $curr_time = strtotime($current_time->format('Y-m-d H:i:s'));
            if($request_status->requesthistory->status == 'completed'&& $request_status->join_time != null)
            {
                //return 'gg';
                $waiting_time = 0;
    
            }
            elseif($curr_time >= $book_time && $request_status->join_time == null)
            {
                $join_time = $current_time->format('Y-m-d H:i:s');
                $update_request = \App\Model\Request::where('id',$request_id)->update(['join_time' => $join_time]);
                $waiting_time = 1000;    

            }
            elseif($request_status->join_time != null)
            { 
            $join_time = new DateTime($request_status->join_time);

            // return json_encode($join_time);

                $diff = $this->millisecsBetween($current_time->format('Y-m-d H:i:s'), $join_time->format('Y-m-d H:i:s'));
                $waiting_time = $diff;
            }
            else
            {
                $waiting_time = 0;
            }
                $waiting_time = $waiting_time/1000;
             //$waiting_time =  $this->formatSeconds($waiting_time/1000);
             if($request_status->join_time != null && $request_status->requesthistory->status != 'completd')
             {
                $request_status->from_user = User::select('name','email','id','profile_image','phone','country_code')->with(['profile'])->where('id',$request_status->from_user)->first();
                $request_status->to_user = User::select('name','email','id','profile_image','phone','country_code')->with(['profile'])->where('id',$request_status->to_user)->first();
                $manager_ids = User::whereHas('roles', function ($query) {
                    $query->where('name','doctor_manager');
                 })->where('assign_user','!=',null)->get();
                 foreach($manager_ids as $manage)
                 {
                   
                        $assign_user = json_decode($manage->assign_user);
                        foreach($assign_user as $assnuser)
                        {
                            if($assnuser==$request_status->to_user->id)
                            {
                                 
                                $notification = new Notification();
                                $notification->sender_id = $request_status->from_user->id;
                                $notification->receiver_id =$manage->id;
                                $notification->module_id = $request->request_id;
                                $notification->module ='request';
                                $notification->notification_type ='JOIN_REQUEST';
                                $notification->message =__('notification.join_req_text', ['user_name' => $request_status->from_user->name]);;
                                $notification->save();
                            }
                        }
                    
                 }
                 
                
                 $notification = new Notification();
                 $notification->sender_id = $request_status->from_user->id;
                 $notification->receiver_id = $request_status->to_user->id;
                 $notification->module_id = $request->request_id;
                 $notification->module ='request';
                 $notification->notification_type ='JOIN_REQUEST';
                 $notification->message =__('notification.join_req_text', ['user_name' => $request_status->from_user->name]);;
                 $notification->save();
                 $notification->push_notification(array($notification->receiver_id),array('pushType'=>'JOIN_REQUEST','request_id'=>$request->request_id,'message'=>__('notification.accept_req_text', ['user_name' => $request_status->from_user->name])));
            
           }
            
           // $waiting_time = floor($waiting_time/6000);
            
        }
     

        if(Auth::check()){

             $user = Auth::user();
           
            if($user && $user->hasRole('customer'))
            { 
                $advertisement->where(function($q) use($datenow) {
                    $q->where('end_date', '>=', $datenow)
                         ->orWhere('start_date', '>=', $datenow);
                    })
                        ->where(function($query) use ($request){
                           $query->orwhere('user_id',Auth::user()->id)->orWhere('banner_type','user');
                           
                    });
               
            }

            if($user && $user->hasRole('service_provider'))
            {
                
                $advertisement->where(function($q) use($datenow) {
                    $q->where('end_date', '>=', $datenow)
                         ->orWhere('start_date', '>=', $datenow);
                    })
                        ->where(function($query) use ($request){
                           
                            $query->orwhere('sp_id',Auth::user()->id)->orWhere('banner_type','service_provider')->orWhere('banner_type','category');
                           
                    });
               
            }
            
        }
        else
        {
         
            $advertisement->where(function($q) use($datenow) {
                $q->where('end_date', '>=', $datenow)
                        ->orWhere('start_date', '>=', $datenow);
                });
        }
     
       
         $advertisements = $advertisement->where('enable',1)->orderBy('id','asc')->get();
        foreach ($advertisements as $key => $advertisement) {
            $advertisement->image = json_decode($advertisement->image);
            $advertisement->video = json_decode($advertisement->video);
            // $banner->position = strval($banner->position);
            if($advertisement->banner_type=='category'){
                $advertisement->category;
                $subcategory = Category::where('parent_id',$advertisement->category_id)->where('enable','=','1')->count();
                if($subcategory > 0){
                   $advertisement->category->is_subcategory = true;
                }else{
                    $advertisement->category->is_subcategory = false;
                }
                $advertisement->category->is_filters = false;
                if($advertisement->category->filters->count() > 0){
                    $advertisement->category->is_filters = true;
                }
            }elseif ($advertisement->banner_type=='service_provider') {
                $advertisement->service_provider;
            }
            elseif ($advertisement->banner_type=='user') {
                $advertisement->user;
            }
        }
        $requests = \App\Model\Request::where('id',$request_id)->first();
        $requests->service_type = $requests->servicetype->type;
            $requests->to_user = User::select('name','email','id','profile_image','phone','country_code')->with(['profile'])->with('profile')->where('id',$requests->to_user)->first();
            $requests->to_user->categoryData = $requests->to_user->getCategoryData($requests->to_user->id);
//return $requests;
        return view('vendor.care_connect_live.waitingroom')->with('advertisements',$advertisements)->with('requests',$requests)->with('request_id',$request_id)->with('waiting_time',$waiting_time);

    }

    public static function getRequestByCustomer(Request $request) {
       // return $request;
       
            $user = Auth::user();
            $from_date = null;
            $end_date = null;
          
            $timezone = $request->header('timezone');
            if(!$timezone){
                $timezone = 'Asia/Kolkata';
            }
            $dateznow = new DateTime("now", new DateTimeZone($timezone));
            $datenow = $dateznow->format('Y-m-d H:i:s');
            $current_date = $dateznow->format('Y-m-d');
            if($request->date == '' || $request->date == null)
            {
                $request->date = $current_date;
            }
            $categories = Category::where(array('enable'=>1,'parent_id'=>null))->get();
            $requests = [];
            $service_type = isset($request->service_type)?$request->service_type:'all';
            $service_id = isset($request->service_id)?$request->service_id:null;
            $per_page = (isset($request->per_page)?$request->per_page:10);
            $requests = \App\Model\Request::select('id','service_id','from_user','to_user','booking_date','created_at','booking_date as bookingDateUTC','request_type','token_number','request_category_type','request_category_type_id')
            ->whereHas('servicetype', function($query) use ($service_type,$service_id){
                if($service_type!=='all')
                    return $query->where('type', $service_type);
                if($service_id)
                    return $query->where('id', $service_id);
            })
            ->when('booking_date', function($query) use ($request,$timezone){
                if(isset($request->date)){
                    $from_date = $request->date.' 00:00:00';
                    $end_date = $request->date.' 23:59:59';
                    $fromUTC = Carbon::parse($from_date, $timezone)->setTimezone('UTC');
                    $toUTC = Carbon::parse($end_date, $timezone)->setTimezone('UTC');
                   return $query->whereBetween('booking_date', [$fromUTC, $toUTC]);
                }
            })
            ->where('from_user',$user->id)->orderBy('id', 'desc')->get();
            foreach ($requests as $key => $request_status) {
                // $request_status->is_second_oponion = false;
                // if($request_status->request_type=='second_oponion'){
                //     $request_status->is_second_oponion = true;
                //     $request_status->second_oponion = $request_status->getSecondOponion($request_status);
                // }
                $request_status->is_prescription = false;    
                if($request_status->prescription){
                    $request_status->is_prescription = true;
                    //unset($request_status->prescription);    
                }
                $date = Carbon::parse($request_status->booking_date,'UTC')->setTimezone($timezone);
                $request_status->booking_date = $date->isoFormat('D MMMM YYYY, h:mm:ss a');
                $request_status->time = $date->isoFormat('h:mm a');
                $dateznow = new DateTime("now", new DateTimeZone('UTC'));
                $datenow = $dateznow->format('Y-m-d H:i:s');
                if(\Config::get('client_connected') && (\Config::get('client_data')->domain_name=='care_connect_live')){
                    $next_hour_time = strtotime($datenow);
                }else{
                    $next_hour_time = strtotime($datenow) + 3600;
                }
                $request_history = $request_status->requesthistory;
                if($request_history){
                    $request_status->duration = $request_history->duration;
                    $request_status->price = $request_history->total_charges;
                    $request_status->status = $request_history->status;
                    $request_status->schedule_type = $request_history->schedule_type;
                }
                $request_status->extra_detail = RequestData::getExtraRequestInfo($request_status->id,$timezone);
                if(strtotime($request_status->bookingDateUTC)>=$next_hour_time && $request_status->status=='pending'){
                    $request_status->canReschedule = true;
                    $request_status->canCancel = true;
                }else{
                    $request_status->canReschedule = false;
                    $request_status->canCancel = false;
                }
                $request_status->service_type = $request_status->servicetype->type;
                $request_status->from_user = User::select('name','email','id','profile_image','phone','country_code')->with(['profile'])->where('id',$request_status->from_user)->first();
                $request_status->to_user = User::select('name','email','id','profile_image','phone','country_code')->with(['profile'])->with('profile')->where('id',$request_status->to_user)->first();
                $request_status->to_user->categoryData = $request_status->to_user->getCategoryData($request_status->to_user->id);
               
                
                unset($request_status->requesthistory);
                unset($request_status->servicetype);
                $request_status = RequestData::getMoreData($request_status);
              //  if(Config::get('client_connected') && (Config::get('client_data')->domain_name=='care_connect_live')){
                    $calling_type = $request_status->servicetype->type;
                    if($calling_type != '' || $calling_type != null)
                    {
                    
                        $request_status->join = 'true';
                        $main_service_type = ($request_status->servicetype->service_type)?$request_status->servicetype->service_type:$request_status->servicetype->type;
                        $action = $request_status->servicetype->type;
                    
                            if(strtolower($main_service_type)=='call'||strtolower($main_service_type)=='video call'  || strtolower($main_service_type)=='audio_call' || strtolower($main_service_type)=='video_call') {
                                if($request_status->status == 'accept')
                                {
                    
                                    $request_status->join = 'true';
                                }
                                if($request_status->status == 'completed')
                                {
                                    $request_status->join = 'false';
                                }
                            }
                        
                    }
               // }
               // return json_encode($requests);
            }
        //  return json_encode($requests);
            //$per_page = $requests->perPage();
         //  $requests = $this->paginate($requests, $per_page);
         //  $data->withPath('/user/appointments');
         if(\Config::get('client_connected') && \Config::get('client_data')->domain_name=='iedu'){
            return view('vendor.iedu.appointments')->with(['requests'=>$requests, 'current_date' => $current_date, 'categories' => $categories]);
         }
         else
         {
            return view('vendor.care_connect_live.appointments')->with(['requests'=>$requests, 'current_date' => $current_date, 'categories' => $categories]);

         }
        
    }
    public static function postAddReview(Request $request) {
       //return $request->all();
            $user = Auth::user();
            if(!$user->hasrole('customer')){
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>'Invalid Valid user role must be role as customer'), 400);
            }
         
            // if($request->rating){
            //     $rules['rating'] = "required|max:5";
            // }
            // $validator = Validator::make($request->all(),$rules);
            // if ($validator->fails()) {
            //     return response(array('status' => "error", 'statuscode' => 400, 'message' =>
            //         $validator->getMessageBag()->first()), 400);
            // }
            $message = 'Something went wrong';
            $consultant = User::find($request->consultant_id);
            if($consultant){
                $feeback = new \App\Model\Feedback();
                $feeback->from_user = $user->id;
                $feeback->consultant_id = $request->consultant_id;
                $feeback->request_id = $request->request_id;
                $feeback->rating = (isset($request->rating)?$request->rating:0.5);
                $feeback->comment = isset($request->review)?$request->review:null;
                if($feeback->save()){
                    \App\Model\Feedback::updateReview($request->consultant_id);
                }
                return response(['status' => "success", 'statuscode' => 200,'message' => __('Review added '),'data'=>[]], 200);
            }else{
                $message = 'Doctor not Found';
            }
            return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                    $validator->getMessageBag()->first()), 400);
            
            return response(array('status' => "error", 'statuscode' => 400, 'message' =>__($message)), 400);
       
    }
}
?>