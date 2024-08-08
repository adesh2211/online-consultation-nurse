<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Config,DB,Carbon\Carbon;
use Illuminate\Support\Str;

use App\User,App\Notification;
use Illuminate\Support\Facades\Auth;
use Validator;
use Hash;
use Mail;
use DateTime,DateTimeZone;
use Redirect,Response,File;
use Image;
use Illuminate\Support\Facades\URL;
use App\Helpers\Helper;
use App\Model\Role,App\Model\UserMasterPreference;
use App\Model\Profile;
use App\Model\CustomUserField;
use App\Model\Wallet;
use App\Model\SocialAccount;
class DoctorManagersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $doctormanagers = User::whereHas('roles', function ($query) {
                           $query->where('name','doctor_manager');
                        })->orderBy('id','DESC')->get();
        return view('admin.doctormanagers')->with(['doctormanagers'=>$doctormanagers]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function DoctorManagerList()
    {
        $user = Auth::user();
        $doctormanagers = User::whereHas('roles', function ($query) {
                           $query->where('name','doctor_manager');
                        })->where('created_by',$user->id)->orderBy('id','DESC')->get();
        return view('admin.doctormanagers.doctormanager_list')->with(['doctormanagers'=>$doctormanagers]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getDoctorManagerCreate()
    {
        $user = Auth::user();
        $consultants = User::whereHas('roles', function ($query) {
            $query->where('name','service_provider');
         })->where('account_verified','!=',null)->orderBy('id','DESC')->get();
        return view('admin.doctormanagers.doctormanager_add')->with('consultants',$consultants);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function postDoctorManagerCreate(Request $request)
    {
        //return $request->all();
        $rules = [
            'email' => 'required|email|unique:users,email',
            'name'=>'required',
            'phone'=>'required|unique:users,phone',
            'doctors'=>'required',
            // 'source'=>'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $admin = Auth::user();
        $datenow = new DateTime("now", new DateTimeZone('UTC'));
        $datenowone = $datenow->format('Y-m-d H:i:s');
        $input = $request->all();
        $input['password'] = bcrypt('password');
        $user = User::create($input);
        $user->assign_user = $request->doctors;
        // $user->provider_type = 'email';
        // $user->device_type = 'WEB';
        $user->created_by = $admin->id;
        $user->save();

        $role = Role::where('name','doctor_manager')->first();
        if($role){
            $user->roles()->attach($role);
        }
        
        return redirect('admin/doctormanagers');
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        if(!$user->hasrole('doctor_manager')){
           abort(404);
        }
        if($user->profile_image){
            $user->profile_image = url('/').'/media/'.$user->profile_image;
        }else{
            $user->profile_image = url('/').'/default/user.jpg';
        }
        return view('admin.doctormanagers.view')->with(['manager'=>$user]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
      
        return view('admin.doctormanager_update')->with(['manager'=>$user]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function getEditDoctorManager(Request $request,$user_id)
    {
        $consultants = User::whereHas('roles', function ($query) {
            $query->where('name','service_provider');
         })->where('account_verified','!=',null)->orderBy('id','DESC')->get();
        $user = User::where('id',$user_id)->first();
        return view('admin.doctormanagers.doctormanager_update')->with(['manager'=>$user,'consultants' => $consultants]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        if(!isset($request->account_verify_ajax)){
            $rules = [
                'name' => 'required',
            
            ];
            if(isset($request->phone)){
                $rules['phone'] = 'unique:users,phone,' . $user->id;
            }
            
            if(isset($request->email)){
                $rules['email'] = 'email|unique:users,email,' . $user->id;
            }
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }
           
            if(isset($request->email)){
                $user->email = $request->input('email');
            }
            $user->name = $request->input('name');
            $user->phone = $request->phone;
            $user->assign_user =$request->doctors;
            $user->save();
            return back()->with("status", "Doctor Manager updated");
        }else{
            if(!$user->account_verified){
                $admin = Auth::user();
                $dateznow = new DateTime("now", new DateTimeZone('UTC'));
                $datenow = $dateznow->format('Y-m-d H:i:s');
                $user->account_verified = $datenow;

                $notification = new Notification();
                $notification->sender_id = $admin->id;
                $notification->receiver_id = $user->id;
                $notification->module_id = $user->id;
                $notification->module ='users';
                $notification->notification_type ='PROFILE_APPROVED';
                $notification->message =__('Your Account has been approved');;
                $notification->save();
                $notification->push_notification(array($user->id),array('pushType'=>'PROFILE_APPROVED','message'=>__('Your Account has been approved')));
            }
            $user->save();
            return response()->json(['status'=>'success']);
        }
    }

    public function deleteDoctorManager(Request $request){
        $user_id = $request->user_id;
        $user = User::where('id', $user_id)->first(); // File::find($id)
      
        $role = \App\Model\Role::where('name','doctor_manager')->first();
         if($user->hasRole('doctor_manager')){
            $user->roles()->detach($role);
       
    
             $user->delete();
             return response()->json(['status'=>'success']);
        }
        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        //
    }
}
