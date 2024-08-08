<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Config;
use App\Model\Category;
use App\Helpers\Helper;
use App\Model\Country;
use App\Model\State;
use Twilio\Jwt\ClientToken;
use Twilio\Rest\Client;
use App\Model\Wallet;
use Auth;
use Hash;
use Carbon\Carbon;
use App\Model\Banner;
use App\Model\UserRole;
use App\User;
use App\Model\Role;
use App\Model\Verification;
use Storage;
use Exception;
use DateTime,DateTimeZone,DB;
use Illuminate\Support\Str;
use App\Http\Controllers\SmsController;
use App\Model\EnableService;
use App\Model\Profile;
use App\Model\CategoryServiceProvider;
use App\Model\CategoryServiceType;
use App\Model\FilterType;
use App\Http\Controllers\Web\UserController;
use App\Model\AdditionalDetail;
use App\Model\ServiceProviderFilterOption;
use App\Model\Service;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    protected $UserController;
    public function __construct(UserController $UserController)
    {
        $this->UserController = $UserController;
       
    }
    public function profileSetupOne($id, Request $request)
    {
        $user = User::where('id',$id)->first();
        $profile = Profile::where('user_id',$user->id)->first();
        $states=\App\Model\State::where('country_id', '=', 101)->pluck('name', 'id');

        $language  = DB::table('master_preferences')
        ->join('master_preferences_options', 'master_preferences.id', '=', 'master_preferences_options.preference_id')->where('master_preferences.name','=','Languages')
        ->select('master_preferences.id as preferid', 'master_preferences_options.name as optname', 'master_preferences_options.id as optid')
        ->get();

        $Gender  = DB::table('master_preferences')
        ->join('master_preferences_options', 'master_preferences.id', '=', 'master_preferences_options.preference_id')->where('master_preferences.name','=','Gender')
        ->select('master_preferences.id as preferid', 'master_preferences_options.name as optname', 'master_preferences_options.id as optid')
        ->get();

        $getuserpreference = \App\Model\UserMasterPreference::where('user_id',$user->id)->get();
        //  return json_encode($getuserpreference);

        if($user)
        {
            if(\Config::get('client_connected') && \Config::get("client_data")->domain_name == "iedu")
            {
                return view('vendor.iedu.tutor-sign-up')->with('id', $id)->with('user', $user)->with('profile', $profile)->with('Gender',$Gender)->with('language',$language)->with('getuserpreference',$getuserpreference);

            }
            return view('vendor.care_connect_live.profile-setup')->with('id', $id)->with('user', $user)->with('states', $states)->with('profile', $profile)->with('Gender',$Gender)->with('language',$language)->with('getuserpreference',$getuserpreference);
        }
        else
        {
            return "not found";
        }

    }
    public function profileStepTwoUploadDocuments(Request $request)
    {
        

        $fetch_category = \App\Model\AdditionalDetail::get();
        foreach($fetch_category as $cat)
        {
            $fetch_docs = \App\Model\SpAdditionalDetail::where('sp_id', Auth::id())->where('additional_detail_id',$cat->id)->get();
            if($fetch_docs)
            {
                $cat->documents = $fetch_docs;
            }
            else
            {
                $cat->documents = null;
            }
            
        }
        
       // return json_encode($fetch_category);
       
        return view('vendor.iedu.tutor-sign-up-5')->with('fetch_category',$fetch_category);

    }
    public function profileStepTwo($id, Request $request)
    {
        $per_page = (isset($request->per_page)?$request->per_page:10);
        
        if(\Config::get('client_connected') && \Config::get("client_data")->domain_name == "iedu")
        {
           

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
        }
        else
        {
            $categories = Category::where(['enable'=>'1','parent_id'=>null])
            ->orderBy('id',"ASC")
            ->get();

        }
      // return json_encode($categories);
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
        if(\Config::get('client_connected') && \Config::get("client_data")->domain_name == "iedu")
        {
            return view('vendor.iedu.tutor-sign-up-4')->with('id', $id)->with('categories',$categories)->with('cat_info', $info);
        }
        else
        {
            return view('vendor.care_connect_live.profile-setup-2')->with('id', $id)->with('categories',$categories)->with('cat_info', $info);
        }

         

       
      
      

    }

    public function profileStepTwoCourse(Request $request)
    {
       $user = Auth::user();
        $Course = \App\Model\Course::orderBy('id', 'desc')->get();

        $selected_ids = [];

        foreach ($Course as $item)
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
        
       
        return view('vendor.iedu.tutor-sign-up-2')
            ->with([
                'Courses'   =>  $Course,
                'selected_ids'  =>  $selected_ids
            ]);
    }



    public function profileStepTwoEmsat(Request $request)
    {
        $user = Auth::user();
        // $sp_emsat = \App\Model\SpEmsat::where(['sp_id'=>$user->id])->get();
        

        $per_page = (isset($request->per_page)?$request->per_page:10);
        $Emsats = \App\Model\Emsat::select('id','title','icon','question','marks')->orderBy('id', 'desc')->get();

        foreach ($Emsats as $item)
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
        
        return view('vendor.iedu.tutor-sign-up-3')->with([
            'emsats'=>$Emsats
        ]);
    }


    public function profileStepThree($id, Request $request)
    {
        

        // get if any sp_additional_fields filled
        $fetch_selected_cat = \App\Model\CategoryServiceProvider::where('sp_id', Auth::id())->first();
        if($fetch_selected_cat)
        {
            // check all required docs of selected category is filled
            
            // get all sub_cats
            $all_details = [];
            $fetch_all_details = \App\Model\AdditionalDetail::where('category_id', $fetch_selected_cat->category_id)->where('is_enable', true)->select('id')->get();
            if($fetch_all_details)
            {
                foreach ($fetch_all_details as $item)
                {
                    array_push($all_details, $item->id);
                }
            }

            // unique elements only
            $all_details = array_unique($all_details);

            // get all docs filled
            $all_details_filled = [];
            $fetch_all_filled_details = \App\Model\SpAdditionalDetail::where('sp_id', Auth::id())->select('additional_detail_id')->get();
            if($fetch_all_filled_details)
            {
                foreach ($fetch_all_filled_details as $item)
                {
                    array_push($all_details_filled, $item->additional_detail_id);
                }
            }
            // unique elements only
            $all_details_filled = array_unique($all_details_filled);

            // return json_encode($all_details_filled);
            // return json_encode(sizeof($all_details));

            // check array length is > 0
            if(sizeof($all_details) > 0)
            {
                // check diff
                $needed_details = array_values(array_diff($all_details, $all_details_filled));

                // if diff = 0, show next step
                if(sizeof($needed_details) == 0)
                {
                    // all ok
                    // nothing to do here
                }
                else
                {
                    //return "two";
                    return redirect('/profile/profile-step-two/'.Auth::id())->with('status.error', 'Please Select a Category and Upload All Required Documents');
                }
            }
        }
        else
        {
            return redirect('/profile/profile-step-two/'.Auth::id())->with('status.error', 'Please Select a Category and Upload Required Documents');
        }

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
                   return view('vendor.care_connect_live.profile-setup-3')->with('id', $id)->with('filters',$filters)->with('selectedserviceType',$selectedserviceType);
            }
  
           
          
          
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage()], 500);
        }
      

    }

    public function setFiltersForServiceProvider(Request $request)
    {
        //return json_encode($request->all());
        try{
             $user = Auth::user();
            // if(!$user->hasrole('service_provider')){
            //     return response(array('status' => "error", 'statuscode' => 400, 'message' =>'Invalid role, must be as service_provider'), 400);
            // }
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
                return redirect('/profile/profile-step-four/'.Auth::id());
            }   
            else{
                
                return redirect('/profile/profile-step-three/'.Auth::id())->with('status.error', 'Please Set Preference'); 
            }
       
            
            // redirect('');
        }
        catch(Exception $ex)
        {
            return $ex;
        }
    }

    public function profileStepFouravailbility($id, Request $request)
    {
        $user = Auth::user();
        $fetch_selected_cat = \App\Model\CategoryServiceProvider::where('sp_id', Auth::id())->first();
        $unit_price = EnableService::where('type','unit_price')->first();
        $service_ids = Service::where('enable',1)->pluck('id')->toArray();
        $slot_duration = EnableService::where('type','slot_duration')->first();
        if($fetch_selected_cat)
        {
            $add_details = \App\Model\SpAdditionalDetail::where('sp_id', Auth::id())->get();
            if(sizeof($add_details)>0)
            {
                $services = CategoryServiceType::where([
                    'category_id'   =>  $fetch_selected_cat->category_id,
                    'is_active'     =>  "1"
                ])->orderBy('id', 'asc')->first();
                
                return view('vendor.iedu.tutor-sign-up-6')->with('category_id',$fetch_selected_cat->category_id)->with('service_id',$services->service_id);
            }
            else
            {
                return redirect('/profile/profile-step-two-upload-documents/'.$user->id)->with('status.error','Please Upload Documents');  
            }
        }
        else
        {

             return redirect('/profile/profile-step-two/'.$user->id)->with('status.error','Please Choose Subjects.');

        }
      

        
    }

    public function profileStepFour($id, Request $request)
    {
         // get if any sp_additional_fields filled
         $fetch_selected_cat = \App\Model\CategoryServiceProvider::where('sp_id', Auth::id())->first();
        
        // get if any sp_additional_fields filled

        $fetch_filter = \App\Model\ServiceProviderFilterOption::where('sp_id', Auth::id())->get();
        
        $info = [];

        if($fetch_filter)
        {
            foreach ($fetch_filter as $doc)
            {
                $filter_id = $doc->filter_type_id;

                // fetch main category
                $fetch_cat_id = \App\Model\FilterType::where('id', $filter_id)->where('category_id',$fetch_selected_cat->category_id)->first();
             
                if($fetch_cat_id)
                {
                    $cat_id = $fetch_cat_id->category_id;

                    array_push($info, $cat_id);
                }
            }
        }

        if(sizeof($info) == 0)
        {
            return redirect('/profile/profile-step-three/'.Auth::id())->with('status.error', 'Please Set Preference');
        }

       $cat_info = $info[0];

        $services = [];
        $service_id = null;
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
                if(\Config::get('client_connected') && \Config::get("client_data")->domain_name == "care_connect_live")
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
        return view('vendor.care_connect_live.profile-setup-4')
            ->with('id', $id)
            ->with('services_data',$services_data)
            ->with('cat_info' ,$cat_info);
   }

   public function getDocCategories(Request $request)
   {
       
       $cat_id = $request->get('cat_id');

       $sub_categories = \App\Model\AdditionalDetail::where('category_id', $cat_id)
               ->where('is_enable','=','1')
               ->orderBy('name',"ASC")
               ->get();

       return json_encode($sub_categories);
   }

   public function getDocsByCategories(Request $request)
   {
       $cat_id = $request->get('cat_id');

       // get all additional details id's
       $sub_categories = \App\Model\AdditionalDetail::where('category_id', $cat_id)
               ->where('is_enable','=','1')
               ->orderBy('name',"ASC")
               ->select('id')
               ->get();

       $all_cat_ids = [];

       foreach ($sub_categories as $sub_cat)
       {
           $sp_id = $sub_cat->id;
           array_push($all_cat_ids, $sp_id);
       }

       // return json_encode($all_cat_ids);
       
       // get all docs filled by user - based on additonal id
       

       // $sub_categories = \App\Model\AdditionalDetail::where('category_id', $cat_id)
       //         ->where('is_enable','=','1')
       //         ->orderBy('name',"ASC")
       //         ->get();

       $add_details = \App\Model\SpAdditionalDetail::whereIn('additional_detail_id', $all_cat_ids)
        ->where('sp_id', Auth::id())
        ->get();

       foreach ($add_details as $item)
       {
           $item->cat_name = null;

           $fetch_cat_info = \App\Model\AdditionalDetail::where('id', $item->additional_detail_id)->first();
           if($fetch_cat_info)
           {
               $item->cat_info = $fetch_cat_info->name;
           }

           if($item)
           {
                $item->file_name = Storage::disk('spaces')->temporaryUrl('thumbs/'.$item->file_name, now()->addMinutes(15));
           }
       }

       return json_encode($add_details);

   }
   

   public function getDocs(Request $request)
   {
       return 'fff';
       // get all additional details id's
       $sub_categories = \App\Model\AdditionalDetail::where('is_enable','=','1')
               ->orderBy('name',"ASC")
               ->select('id')
               ->get();

       

       foreach ($sub_categories as $sub_cat)
       {
           $sp_id = $sub_cat->id;
           array_push($all_cat_ids, $sp_id);
       }

      
       $add_details = \App\Model\SpAdditionalDetail::whereIn('additional_detail_id', $all_cat_ids)
        ->where('sp_id', Auth::id())
        ->get();

       foreach ($add_details as $item)
       {
           $item->cat_name = null;

           $fetch_cat_info = \App\Model\AdditionalDetail::where('id', $item->additional_detail_id)->first();
           if($fetch_cat_info)
           {
               $item->cat_info = $fetch_cat_info->name;
           }

           if($item)
           {
                $item->file_name = Storage::disk('spaces')->temporaryUrl('thumbs/'.$item->file_name, now()->addMinutes(15));
           }
       }

       return json_encode($add_details);

   }

   public function postAddDoc(Request $request)
   {
       //return $request->input('doc_category');
       // return json_encode(Auth::user());
       // return json_encode($request->all());

       // check image added or not
       $filename = null;
       if ($request->hasfile('image_uploads'))
       {
           if ($image = $request->file('image_uploads'))
           {
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
           }
       }

       // SpAdditionalDetail
       $sp_add_details = new \App\Model\SpAdditionalDetail();
       $sp_add_details->sp_id = Auth::id();
       $sp_add_details->title = $request->input('title');
       $sp_add_details->description = $request->input('description');
   
       $sp_add_details->file_name = $filename;

       $sp_add_details->additional_detail_id = $request->input('doc_category');

       $sp_add_details->status = 'in_progress';
       $sp_add_details->save();
       

       // fetch doc
       $fetch_doc_id = \App\Model\AdditionalDetail::where('id', $request->input('doc_category'))->first();  

       if(\Config::get('client_connected') && \Config::get("client_data")->domain_name != "iedu")
        {
    
            // save user selected category
            $check_sp = \App\Model\CategoryServiceProvider::where('sp_id',Auth::id())->first();
            if($check_sp)
            {
                
                \App\Model\CategoryServiceProvider::where('sp_id', Auth::id())
                ->update(
                    [
                        'category_id' => $fetch_doc_id->category_id
                    ]
                    );
            }
            else
            {
                    $insert_new_record = new \App\Model\CategoryServiceProvider();
                    $insert_new_record->sp_id = Auth::id();
                    $insert_new_record->category_id = $fetch_doc_id->category_id;
                    $insert_new_record->save();
            }
            
            // check if all docs are filles (bsaed on category)
            $docs_cat_id = $fetch_doc_id->category_id;

            // get all sub_cats
            $all_details = [];
            $fetch_all_details = \App\Model\AdditionalDetail::where('category_id', $docs_cat_id)->where('is_enable', true)->select('id')->get();
            if($fetch_all_details)
            {
                foreach ($fetch_all_details as $item)
                {
                    array_push($all_details, $item->id);
                }
            }
            // unique elements only
            $all_details = array_unique($all_details);

            // get all docs filled
            $all_details_filled = [];
            $fetch_all_filled_details = \App\Model\SpAdditionalDetail::where('sp_id', Auth::id())->select('additional_detail_id')->get();
            if($fetch_all_filled_details)
            {
                foreach ($fetch_all_filled_details as $item)
                {
                    array_push($all_details_filled, $item->additional_detail_id);
                }
            }
            // unique elements only
            $all_details_filled = array_unique($all_details_filled);

            // return json_encode($all_details_filled);
            // return json_encode(sizeof($all_details));

            // check array length is > 0
            if(sizeof($all_details) > 0)
            {
                    // check diff
                    $needed_details = array_values(array_diff($all_details, $all_details_filled));
                    $user = User::where('id',Auth::user()->id)->update([
                        'account_step' =>  '2'
                    ]);
                    

                    // if diff = 0, show next step
                    if(sizeof($needed_details) == 0)
                    {
                        return redirect('/profile/profile-step-three/'.Auth::id())->with('status.success', 'All Document Uploaded Successfully');         
                    }
                    else
                    {
                        // else, pass needed doc id and show popup
                        // return $needed_details[0];
                        return redirect('/profile/profile-step-two/'.Auth::id())
                        ->with('status.success', 'Document Uploaded Successfully')
                        ->with('next_needed_doc_id', $needed_details[0])
                        ->with('needed_cat_id', $docs_cat_id);

                    }
            }
        }
        if(\Config::get('client_connected') && \Config::get("client_data")->domain_name == "iedu")
        {
         return redirect('/profile/profile-step-two-upload-documents/'.Auth::id())->with('status.success', 'Document Uploaded Successfully');
        }
        else
        {
            return redirect('/profile/profile-step-two/'.Auth::id())->with('status.success', 'Document Uploaded Successfully');
        }
       
    }

   public function postEditDoc(Request $request)
   {
       // return json_encode($request->all());

       // check if exists
       $doc_info = \App\Model\SpAdditionalDetail::where('id', $request->input('doc_id'))->where('sp_id', Auth::id())->first();
       if($doc_info == null)
       {
           return redirect('/profile/profile-step-two/'.Auth::id())->with('status.error', 'Not Found');
       }

       if($doc_info->file_name != null)
       {
           $filename = $doc_info->file_name;
       }
       else
       {
           $filename = null;
       }

       if($request->hasfile('image_uploads'))
       {
           if ($image = $request->file('image_uploads'))
           {
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
           }
       }

       \App\Model\SpAdditionalDetail::where('id', $request->input('doc_id'))->where('sp_id', Auth::id())->update([
           'title'         =>  $request->input('title'),
           'description'   =>  $request->input('description'),
           'file_name'     =>  $filename
       ]);
       if(\Config::get('client_connected') && \Config::get("client_data")->domain_name == "iedu")
       {
        return redirect('/profile/profile-step-two-upload-documents/'.Auth::id())->with('status.success', 'File Updated');
       }
       else
       {
        return redirect('/profile/profile-step-two/'.Auth::id())->with('status.success', 'File Updated');
       }

       

       //return json_encode($request->all());
   }

   public function deleteDoc($id, Request $request)
   {
       // check doc is owned by logged in user and then delete
       \App\Model\SpAdditionalDetail::where([
           "id"    =>  $id,
           "sp_id" =>  Auth::id()
       ])->delete();
       
       // redirect to step with message
       if(\Config::get('client_connected') && \Config::get("client_data")->domain_name == "iedu")
       {
        return redirect('/profile/profile-step-two-upload-documents/'.Auth::id())->with('status.success', 'File Removed');
       }
       else
       {
        return redirect('/profile/profile-step-two/'.Auth::id())->with('status.success', 'File Removed');
       }
      
   }

   public function getEditDoc($id, Request $request)
   {
       // get doc (check owned by current user)
       $add_detail = \App\Model\SpAdditionalDetail::where([
           "id"    =>  $id,
           "sp_id" =>  Auth::id()
       ])->first();

       // get categroy name

       $add_detail->additional_detail_name = null;

       $category_name = \App\Model\AdditionalDetail::where('id', $add_detail->additional_detail_id)->first();
       if($category_name)
       {
           $add_detail->additional_detail_name = $category_name->name;
       }

       if($add_detail)
       {
           $add_detail->file_name = Storage::disk('spaces')->temporaryUrl('thumbs/'.$add_detail->file_name, now()->addMinutes(15));
       }

       return json_encode($add_detail);
   }

   public function editProfile(Request $request)
   {
       // echo '<pre>';
       // print_r($request->all());
       // exit;
       // return json_encode($request->all());

   
       try
       {
           $user = $request->user_id;
           if(!$user)
           {
           $isValid =  Validator::make($request->all(), [
               'title'	=>	'required',
               'name'	=>	'required',
               'email' =>  'unique:users,email,'.$request->user_id,
               'password' => 'required|min:8|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|confirmed',
               'dob'   =>  'required' ,
               'working_since' => 'required',
               'qualification' => 'required',
               'bio' => 'required',
               'state' => 'required'
           ]);
           }
           else{
            $isValid =  Validator::make($request->all(), [
                'title'	=>	'required',
                'name'	=>	'required',
                'email' =>  'unique:users,email,'.$request->user_id,
                'dob'   =>  'required' ,
                'working_since' => 'required',
                'qualification' => 'required',
                'bio' => 'required',
                'state' => 'required'
            ]);
           }

          
            
           if($isValid->fails()) {
               return redirect('/profile/profile-setup-one/'.$request->input('user_id'))->withErrors($isValid)->withInput();

           }
           else {
               
               $input = $request->all();
               //return json_encode($input);
               $user = User::where('id',$request->user_id)->first();
               $profile = Profile::where('user_id',$request->user_id)->first();  
              
               if($profile)
               {
                    if($request->hasfile('profile_image')) 
                    {
                    
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
               if(isset($request->city)){
                $profile->city = $request->city;  
            }
            if(isset($request->state)){
                $profile->state = $request->state;  
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
               if(isset($request->city)){
                $pro->city = $request->city;  
                }
                if(isset($request->state)){
                    $pro->state = $request->state;  
                }
               if(isset($request->qualification)){
                $pro->qualification = $request->qualification;  
                 }
               $pro->user_id = $user->id;
               $pro->save();

                 }
               if(isset($request->phone)){
                   $user->phone = $request->input('phone');
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
               if($request->step == '1')
               {
                    if(\Config::get('client_connected') && \Config::get("client_data")->domain_name == "iedu")
                    {
                        return redirect('/profile/profile-step-two-course/'.$request->user_id);
                    }
                  return redirect('/profile/profile-step-two/'.$request->user_id);
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
        }
       catch(\Exception $ex)   
       {
           return $ex;
           return "test";
       }
   }

public function updateServiceTypes(Request $request)
{
    if(isset($input['category_services_type'])){
        // $delete = SpServiceType::where(['sp_id'=>$user->id])->delete();
        foreach ($input['category_services_type'] as $category_service_type) {
            $spservicetype = SpServiceType::firstOrCreate([
                'sp_id'=>$user->id,
                'category_service_id'=>$category_service_type['id']
            ]);
            if(isset($category_service_type['clinic_address'])){
                $address = \App\Model\CustomInfo::firstOrCreate([
                    'info_type'=>'service_address',
                    'ref_table'=>'sp_service_types',
                    'ref_table_id'=>$spservicetype->id,
                    'status'=>'success',
                ]);
                $address->raw_detail = json_encode($category_service_type['clinic_address']);
                $address->save();
            }
            if($spservicetype){
                $service = CategoryServiceType::where('id',$category_service_type['id'])->first();
                $spservicetype->available = $category_service_type['available'];
                if($category_service_type['available']=="1")
                    $spservicetype->minimmum_heads_up = $category_service_type['minimmum_heads_up'];
                if($service->price_fixed!==null){
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
            }
        }
    }
}


public function addAvailbility(Request $request)
{
    // echo '<pre>';
   //   print_r($request->all());
    // exit;

    //return json_encode($request->all());
    if(\Config::get('client_connected') && \Config::get("client_data")->domain_name == "iedu")
    {
        $unit_price = EnableService::where('type','unit_price')->first();
        $service_ids = Service::where('enable',1)->pluck('id')->toArray();
        $slot_duration = EnableService::where('type','slot_duration')->first();
     
        $get_category_service = DB::table('category_service_types')->where('service_id', $request->service_id)->where('category_id',$request->category_id)->first();
        // return json_encode($get_category_service);
        $item = new \App\Model\SpServiceType();
        $item->sp_id = Auth::id();
        $item->category_service_id = $get_category_service->id;
        $item->price = $request->price;
        $item->duration = $slot_duration->value;
    
        $item->available = '1';
    
        $item->save();
    }


    $timezone = $request->header('timezone');
    if(!$timezone){
        $timezone = 'Asia/Kolkata';
    }

    foreach($request->options as $option)
    {
        foreach($request->start_time as $key => $start)
        {
            // echo 'week-day '.$option.' --- Start Time '.$start.' --- End Time '.$request->end_time[$key].'<br>';

            $service_provider_slot = new \App\Model\ServiceProviderSlot;
            $service_provider_slot->service_provider_id = Auth::user()->id;
            $service_provider_slot->service_id =  $request->service_id;
            $service_provider_slot->category_id = $request->category_id;
            $start_time = Carbon::parse($start,$timezone)->setTimezone('UTC')->format('H:i:s');
            $end_time = Carbon::parse($request->end_time[$key],$timezone)->setTimezone('UTC')->format('H:i:s');
            $service_provider_slot->start_time = $start_time;
            $service_provider_slot->end_time = $end_time;
            $service_provider_slot->day = $option;
            $service_provider_slot->save();

            // $sp_availbility = new \App\Model\SpAvailability;
            // $sp_availbility->user_id = Auth::user()->id;
            // $sp_availbility->service_id =  $request->service_id;
            // $sp_availbility->start_time = $start;
            // $sp_availbility->end_time = $request->end_time[$key];
            // $sp_availbility->day = $option;
            // $sp_availbility->save();
        }
    }
    //print_r( $service_provider_slot);  die('ghjj');
    if(\Config::get('client_connected') && \Config::get("client_data")->domain_name != "iedu")
    {
        $data = array(
            'status'    =>  'success',
            'message'   =>  'Availbility Added Successfully',
            'userid'    =>  Auth::user()->id
        );
        return json_encode($data);
    }
    else
    {
        return redirect('user/requests');
    }
    //return redirect('/profile/profile-step-four/'.Auth::id())->with('status.success', 'Availbility Added Successfully');
}

public function editAvailbility($id, Request $request)
{
    $fetch_avails = \App\Model\ServiceProviderSlot::where('service_provider_id', Auth::id())->where('service_id', $id)->get();
    //$fetch_avails = \App\Model\SpAvailability::where('user_id', Auth::id())->where('service_id', $id)->get();
    //return json_encode($fetch_avails);
    $days = [];
    $start_slots = [];
    $end_slots = [];

    if($fetch_avails)
    {
        foreach ($fetch_avails as $item)
        {
            // $start_time = Carbon::parse($item->start_time,$timezone)->setTimezone('UTC')->format('H:i:s');
            // $end_time = Carbon::parse($item->end_time,$timezone)->setTimezone('UTC')->format('H:i:s');
            array_push($days, $item->day);
            // array_push($start_slots, $start_time);
            // array_push($end_slots, $end_time);

            array_push($start_slots, $item->start_time);
            array_push($end_slots, $item->end_time);
        }
        
        if(sizeof($days) > 0)
        {
            $days = array_values(array_unique($days));
        }

        if(sizeof($start_slots) > 0)
        {
            $start_slots = array_values(array_unique($start_slots));
        }

        if(sizeof($end_slots) > 0)
        {
            $end_slots = array_values(array_unique($end_slots));
        }
    }

    $data = [
        "days"  =>  $days,
        "start_slots"   =>  $start_slots,
        "end_slots"     =>  $end_slots
    ];

    return json_encode($data);
}

public function postEditAvailbility(Request $request)
{
    // remove old
    \App\Model\ServiceProviderSlot::where('service_provider_id', Auth::id())->where('service_id', $request->input('service_id'))->where('category_id', $request->input('category_id'))->delete();
  //  \App\Model\SpAvailability::where('user_id', Auth::id())->where('service_id', $request->input('service_id'))->delete();

    // save again

  //  print_r($request->start_time); die();
 // return $request->all();

    foreach($request->options as $option)
    {
        foreach($request->start_time as $key => $start)
        { 
            // echo 'week-day '.$option.' --- Start Time '.$start.' --- End Time '.$request->end_time[$key].'<br>';

            // $sp_availbility = new \App\Model\SpAvailability;
            // $sp_availbility->user_id = Auth::user()->id;
            // $sp_availbility->service_id =  $request->service_id;
            // $sp_availbility->start_time = $start;
            // $sp_availbility->end_time = $request->end_time[$key];
            // $sp_availbility->day = $option;
            // $sp_availbility->save();

            $service_provider_slot = new \App\Model\ServiceProviderSlot;
            $service_provider_slot->service_provider_id = Auth::user()->id;
            $service_provider_slot->service_id =  $request->service_id;
            $service_provider_slot->category_id = $request->category_id;
            $service_provider_slot->start_time = Carbon::parse($slot['slot_start'],$timezone)->setTimezone('UTC')->format('H:i:s');
            $service_provider_slot->end_time = Carbon::parse($request->end_time[$key],$timezone)->setTimezone('UTC')->format('H:i:s');
            $service_provider_slot->day = $option;
            $service_provider_slot->save();

        }
    }
    
    $data = array(
        'status'    =>  'success',
        'message'   =>  'Availbility Updated Successfully',
        'userid'    =>  Auth::user()->id
    );

    return json_encode($data);
}

public function postUpdateServiceTypeAvail(Request $request)
{
     //return json_encode($request->all());
    $get_category_service = DB::table('category_service_types')->where('service_id', $request->input('service_id'))->where('category_id',$request->category_id)->first();
    $item =  \App\Model\SpServiceType::where('category_service_id',$get_category_service->id)->where('sp_id',Auth::id())->first();
    $item->price = $request->price;
    //$item->duration = $request->duration;
   // $item->available = '1';

    $item->save();

    return json_encode("done");
}

public function postAddServiceTypeAvail(Request $request)
{
     //return json_encode($request->all());
    $get_category_service = DB::table('category_service_types')->where('service_id', $request->input('service_id'))->where('category_id',$request->category_id)->first();
   // return json_encode($get_category_service);
    $item = new \App\Model\SpServiceType();
    $item->sp_id = Auth::id();
    $item->category_service_id = $get_category_service->id;
    $item->minimmum_heads_up = 5;
    $item->price = $request->price;
    $item->duration = $request->duration;
 
    $item->available = '1';

    $item->save();
    

    return json_encode("done");
}

public function postRemoveServiceTypeAvail(Request $request)
{    
    $get_category_service = DB::table('category_service_types')->where('service_id', $request->input('service_id'))->where('category_id',$request->category_id)->first();
   
    \App\Model\SpServiceType::where('category_service_id', $get_category_service->id)->where('sp_id', Auth::id())->delete();

    return json_encode("done");
}

public function submitServiceType(Request $request)
{
    //return json_encode($request->all());
    // $user = Auth::user();
    // if(!$user->hasrole('service_provider')){
    //     return response(array('status' => "error", 'statuscode' => 400, 'message' =>'Invalid user role, must be role as service_provider'), 400);
    // }

    // get all checked items
    $get_all_checked_services = \App\Model\SpServiceType::where('sp_id', Auth::id())->get();
    
    //return json_encode($get_all_checked_services);

    $count = 0;

    foreach($get_all_checked_services as $checked_service)
    {
        $cat_service_id = $checked_service->category_service_id;
        $cat_service_type = \App\Model\CategoryServiceType::where('id', $cat_service_id)->first();
        if($cat_service_type)
        {
            $cat_id = $cat_service_type->category_id;
            $service_id = $cat_service_type->service_id;

            // check availibilty filled
            $avail_filled = \App\Model\ServiceProviderSlot::where('service_provider_id', Auth::id())->where('service_id', $service_id)->exists();
          //  $avail_filled = \App\Model\SpAvailability::where('user_id', Auth::id())->where('service_id', $service_id)->exists();
            if($avail_filled == false)
            {
                return  redirect('/profile/profile-step-four/'.Auth::id())->with('status.error', 'Please Select Availbility');
            }
            else
            {
                $count++;
            }
        }
    }

    // atleast one availibilty is filled
    if($count > 0)
    {
        return redirect('/user/requests')->with('status.info','Your Profile submit for Approval');
    }
    else
    {
        return  redirect('/profile/profile-step-four/'.Auth::id())->with('status.error', 'Please Select Availbility');
    }
}

public function getjistitest(Request $request)
{ 
    return view('vendor.care_connect_live.jisttest');
}

public static function postspcourses(Request $request) {
    // return $request->all();

    try{
       
            $rules = [
                    'course_id'=>'required',    
            ];
            $validator = Validator::make($request->all(),$rules);
            if ($validator->fails()) {
                return redirect('/profile/profile-step-two-course/'.Auth::user()->id)->with('status.error','Please Choose Course');
            }
       
        $input = $request->all();
        $user=Auth::user();
        if(isset($input['course_id'])){
            $coursearray=explode(',',$input['course_id']);
            $data=[];
            \App\Model\SpCourse::where(['sp_id'=>$user->id])->delete();
            foreach ($coursearray as $courses) {
                $spcourse = \App\Model\SpCourse::firstOrCreate([
                    'sp_id'=>$user->id,
                    'course_id'=>$courses,
                ]);

                $data[]=$spcourse;
            }
            return redirect('/profile/profile-step-two-emsat/'.Auth::user()->id);
        }

       
    }catch(Exception $ex){
        return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage()], 500);
    }
}



public static function postspemsats(Request $request) {
    

    try{
        
        $input = $request->all();
        $user=Auth::user();
       

        $items = [];

        foreach($request->input('price') as $key => $price)
        {
            if($price != null)
            {
                $item = [
                    "id"    =>  $request->input('id')[$key],
                    "price" =>  $price
                ];

                array_push($items, $item);
            }
        }

        $not_delete = [];
        foreach($items as $item)
        {
            $not_delete[] = $item['id'];
            $sp_em = \App\Model\SpEmsat::firstOrCreate([
                'emsat_id'  =>  $item['id'],
                'sp_id' =>  $user->id]
            );
            $sp_em->price = $item['price'];
            $sp_em->save();
        }
        \App\Model\SpEmsat::where('sp_id',$user->id)->whereNotIn('emsat_id',$not_delete)->delete();

        return redirect('/profile/profile-step-two/'.Auth::user()->id);
        
    }catch(Exception $ex){
        return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage()], 500);
    }
}


public function postCategories(Request $request)
{
    //return $request->all();
    // save user selected category
    $user= Auth::user();
 
        if($request->category_id)
        {
            foreach($request->category_id as $category)
            {
                $insert_new_record = new \App\Model\CategoryServiceProvider();
                $insert_new_record->sp_id = $user->id;
                $insert_new_record->category_id = $category;
                $insert_new_record->save();
            }
            return redirect('/profile/profile-step-two-upload-documents/'.$user->id);
        }
        else
        {
            return redirect('/profile/profile-step-two/'.$user->id)->with('status.error','Please Choose Subjects.');

        }
    
    

}
      
}
?>