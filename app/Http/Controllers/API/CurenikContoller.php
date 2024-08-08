<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


use App\User;
use App\Model\Category;
use App\Notification;
use Illuminate\Support\Facades\Auth;
use Validator,Hash,Mail,DB;
use DateTime,DateTimeZone;
use Redirect,Response,File,Image;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;
use Config;
use App\Model\EnableService;
use App\Model\ServiceProviderSlot;
use App\Model\ServiceProviderSlotsDate;
use App\Model\Office;
class CurenikContoller extends Controller
{
    /**
     * @SWG\Post(
     *     path="/add-clinic",
     *     description="Add Clinic",
     * tags={"Clinics"},
     *     security={
     *     {"Bearer": {}},
     *   },
     *  @SWG\Parameter(
     *         name="id",
     *         in="query",
     *         type="string",
     *         description="Clinic Id when update the clinic",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="name",
     *         in="query",
     *         type="string",
     *         description="Clinic Name",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="address",
     *         in="query",
     *         type="string",
     *         description="Clinic Address",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="logo",
     *         in="query",
     *         type="string",
     *         description="Clinic Logo",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="lat",
     *         in="query",
     *         type="string",
     *         description="Clinic Address Lat",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="long",
     *         in="query",
     *         type="string",
     *         description="Clinic Address Long",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="price",
     *         in="query",
     *         type="string",
     *         description="Clinic Price",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="availability",
     *         in="query",
     *         type="string",
     *         description="{'applyoption':'specific_day','day':2,'date':'2010-08-19','days':[true,false,true,true,true,true,false],'slots':[{'start_time':'11:00','end_time':'16:30'}]}",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="documents",
     *         in="query",
     *         type="string",
     *         description="[{'image':'img.png','type':'image'},{'image':'pdf.pdf','type':'pdf'}]",
     *         required=false,
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK",
     *     ),
     *     @SWG\Response(
     *         response=400,
     *         description="Please provide all data"
     *     )
     * )
     */

    public function addClinic(Request $request){
        $user = Auth::user();
        $rules = [
        	'name' => 'required',
        	'address' => 'required',
        	'logo' => 'required',
        	'lat' => 'required',
        	'long' => 'required',
        	'price' => 'required',
    	];
    	if(isset($request->id)){
    		$rules['id'] = 'required|exists:offices,id';
    	}
        $validator = Validator::make($request->all(),$rules);
        if ($validator->fails()) {
            return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                $validator->getMessageBag()->first()), 400);
        }
        $input = $request->all();
        $service_id = \App\Model\Service::getServiceIdByMainType('clinic_visit');
        $category = $user->getCategoryData($user->id);
        $category_id = ($category)?$category->id:1;
        if(isset($input['id'])){
    		$office = Office::where('id',$input['id'])->first();
        }else{
    		$office = new Office();
    		$office->sp_id = $user->id;
	    	$office->service_id = $service_id;
	    	$office->category_id = ($category)?$category->id:null;
        }
    	$office->name = $input['name'];
    	$office->address = $input['address'];
    	$office->logo = $input['logo'];
    	$office->lat = $input['lat'];
    	$office->long = $input['long'];
    	$office->price = $input['price'];
    	$office->save();

    	$timezone = $request->header('timezone');
        if(!$timezone){
            $timezone = 'Asia/Kolkata';
        }

        if(isset($input['documents']) && is_array($input['documents'])){
            \App\Model\Image::where(['module_table'=>'clinics','module_table_id'=>$office->id])->delete();
            foreach ($input['documents'] as $key => $document) {
                $image = new \App\Model\Image();
                $image->module_table = 'clinics';
                $image->module_table_id = $office->id;
                $image->type = $document['type'];
                $image->image_name = $document['image'];
                $image->save();
            }
        }

    	if(isset($input['availability']) && is_array($input['availability'])){
	    	$availability = $input["availability"];
	        if($availability['applyoption']=='weekwise'){
	            if(isset($availability['days'])){
	            	ServiceProviderSlot::where([
                        'service_provider_id'=>$user->id,
                        'office_id'=>$office->id,
                    ])->delete();
	                foreach ($availability['days'] as $day=>$flag) {
	                    if($flag){
	                       foreach ($availability['slots'] as $slot) {
	                            $start_time = Carbon::parse($slot['start_time'],$timezone)->setTimezone('UTC')->format('H:i:s');
	                            $end_time = Carbon::parse($slot['end_time'],$timezone)->setTimezone('UTC')->format('H:i:s');
	                            $spavailability = new ServiceProviderSlot();
	                            $spavailability->service_provider_id = $user->id;
	                            $spavailability->service_id = $service_id;
	                            $spavailability->category_id = $category_id;
	                            $spavailability->start_time = $start_time;
	                            $spavailability->end_time = $end_time;
	                            $spavailability->day = $day;
	                            $spavailability->office_id = $office->id;
	                            $spavailability->save();
	                       }
	                    }
	                }
	            }
	        }else if($availability['applyoption']=='multiple_days'){ 
	            if(isset($availability['days'])){
	                foreach ($availability['days'] as $day=>$flag) {
	                    if($flag){
	                    	ServiceProviderSlot::where([
                                'service_provider_id'=>$user->id,
                        		'office_id'=>$office->id,
                                'day'=>$day,
                            ])->delete();
	                       foreach ($availability['slots'] as $slot) {
	                            $start_time = Carbon::parse($slot['start_time'],$timezone)->setTimezone('UTC')->format('H:i:s');
	                            $end_time = Carbon::parse($slot['end_time'],$timezone)->setTimezone('UTC')->format('H:i:s');
	                            $spavailability = new ServiceProviderSlot();
	                            $spavailability->service_provider_id = $user->id;
	                            $spavailability->service_id = $service_id;
	                            $spavailability->category_id = $category_id;
	                            $spavailability->start_time = $start_time;
	                            $spavailability->end_time = $end_time;
	                            $spavailability->office_id = $office->id;
	                            $spavailability->day = $day;
	                            $spavailability->save();
	                       }
	                    }
	                }
	            }
	        }else if($availability['applyoption']=='specific_date'){
	        	ServiceProviderSlotsDate::where([
                    'service_provider_id'=>$user->id,
                     'office_id'=>$office->id,
                    'date'=>$availability['date'],
                ])->delete();
	           foreach ($availability['slots'] as $slot) {
	                $start_time = Carbon::parse($slot['start_time'],$timezone)->setTimezone('UTC')->format('H:i:s');
	                $end_time = Carbon::parse($slot['end_time'],$timezone)->setTimezone('UTC')->format('H:i:s');
	                $spavailability = new ServiceProviderSlotsDate();
	                $spavailability->service_provider_id = $user->id;
	                $spavailability->service_id = $service_id;
	                $spavailability->category_id = $category_id;
	                $spavailability->start_time = $start_time;
	                $spavailability->end_time = $end_time;
	                $spavailability->date = $availability['date'];
	                $spavailability->office_id = $office->id;
	                $spavailability->save();
	           }
	        }else if($availability['applyoption']=='specific_day'){ 
	            
	            $weekMap = ['SU'=>0,'MO'=>1,'TU'=>2,'WE'=>3,'TH'=>4,'FR'=>5,'SA'=>6];
	            $day = strtoupper(substr(Carbon::parse($availability['date'])->format('l'), 0, 2));
	            $day_number = $weekMap[$day];
	            ServiceProviderSlot::where([
                    'service_provider_id'=>$user->id,
                    'day'=>$day_number,
                    'office_id'=>$office->id,
                ])->delete();
	           foreach ($availability['slots'] as $slot) {
	                $start_time = Carbon::parse($slot['start_time'],$timezone)->setTimezone('UTC')->format('H:i:s');
	                $end_time = Carbon::parse($slot['end_time'],$timezone)->setTimezone('UTC')->format('H:i:s');
	                $spavailability = new ServiceProviderSlot();
	                $spavailability->service_provider_id = $user->id;
	                $spavailability->service_id = $service_id;
	                $spavailability->category_id = $category_id;
	                $spavailability->start_time = $start_time;
	                $spavailability->end_time = $end_time;
	                $spavailability->day = $day_number;
	                $spavailability->office_id = $office->id;
	                $spavailability->save();
	           }
	        }else if($availability['applyoption']=='weekdays'){ //monday-to-friday 
	            $weekdays = [1,2,3,4,5];
	            ServiceProviderSlot::where([
                    'service_provider_id'=>$user->id,
                    'office_id'=>$office->id,
                ])->whereIn('day',$weekdays)->delete();
	            foreach ($weekdays as $day) {
	               foreach ($availability['slots'] as $slot) {
	                    $start_time = Carbon::parse($slot['start_time'],$timezone)->setTimezone('UTC')->format('H:i:s');
	                    $end_time = Carbon::parse($slot['end_time'],$timezone)->setTimezone('UTC')->format('H:i:s');
	                    $spavailability = new ServiceProviderSlot();
	                    $spavailability->service_provider_id = $user->id;
	                    $spavailability->service_id = $service_id;
	                    $spavailability->category_id = $category_id;
	                    $spavailability->start_time = $start_time;
	                    $spavailability->end_time = $end_time;
	                    $spavailability->day = $day;
	                    $spavailability->office_id = $office->id;
	                    $spavailability->save();
	               }
	            }
	        }

    	}
    	$office = Office::getClinics($user->id);
    	if(isset($input['id'])){
    		$message = __('Clinic Updated');
    	}else{
    		$message = __('Clinic Added');
    	}
    	return response([
    		'status' => "success",
    		'statuscode' => 200,
    		'data'=>['clinic_addresses'=>$office],
    		'message' => $message
    	], 200);
    }

	/**
     * @SWG\Delete(
     *     path="/delete-clinic/{id}",
     *     description="Delete Clinic",
     * tags={"Clinics"},
     *     security={
     *     {"Bearer": {}},
     *   },
     *  @SWG\Parameter(
     *         name="id",
     *         in="query",
     *         type="string",
     *         description="Clinic Id",
     *         required=true,
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK",
     *     ),
     *     @SWG\Response(
     *         response=400,
     *         description="Please provide all data"
     *     )
     * )
     */

    public function deleteClinic(Request $request,$id){
        $user = Auth::user();
        $input = $request->all();
        $input['id'] = $id;
        $rules = ['id'=>'required|exists:offices,id'];
        $validator = Validator::make($input,$rules);
        if ($validator->fails()) {
            return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                $validator->getMessageBag()->first()), 400);
        }
    	ServiceProviderSlot::where([
            'service_provider_id'=>$user->id,
            'office_id'=>$id
        ])->delete();
        ServiceProviderSlotsDate::where([
            'service_provider_id'=>$user->id,
             'office_id'=>$id
        ])->delete();
    	Office::where('id',$id)->delete();
    	$office = Office::getClinics($user->id);
    	$message = __('clinic deleted');
    	return response([
    		'status' => "success",
    		'statuscode' => 200,
    		'data'=>['clinic_addresses'=>$office],
    		'message' => $message
    	], 200);
    }
}
