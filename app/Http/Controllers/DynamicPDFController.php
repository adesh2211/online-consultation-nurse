<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\User;
use Illuminate\Support\Facades\Auth;
use Validator,Hash,Mail,DB;
use DateTime,DateTimeZone;
use Redirect,Response,File,Image;
use App\Helpers\Helper;
use App\Model\Request as RequestTable;
use App\Model\PreScription,App\Model\PreScriptionMedicine,App\Model\Image as ModelImage;
use Socialite,Exception;
use Carbon\Carbon;
use App\Notification;
class DynamicPDFController extends Controller
{
	/**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function pdfview(Request $request)
    {
    	if($request->has('request_id')){
    		Helper::connectByClientKey($request->client_id);
	        $requesttable = RequestTable::where('id',$request->request_id)->first();
	        $app_detail = \App\Model\AppDetail::orderBy('id','DESC')->first();
	        $pre_scription = null;
	        if($requesttable){
	        	$pre_scription =  PreScription::where('request_id',$requesttable->id)->orderBy("id","DESC")->first();
	        	$requesttable->background_color = null;
	        	if($app_detail){
	        		$requesttable->background_color = $app_detail->background_color;
	        	}
		        $requesttable->medicines = [];
		        $requesttable->pre_scription = $pre_scription;
		        if($requesttable->pre_scription && $requesttable->pre_scription->type=="digital"){
		        	if($requesttable->pre_scription->medicines){
		        		$requesttable->medicines = $requesttable->pre_scription->medicines;
		        		unset($requesttable->pre_scription->medicines);
		        	}
		        }elseif($requesttable->pre_scription && $requesttable->pre_scription->type=="manual"){
		        	$requesttable->images = ModelImage::where(['module_table'=>'pre_scriptions','module_table_id'=>$requesttable->pre_scription->id])->get();
		        }
		        view()->share('requesttable',$requesttable);
		        if($request->has('download')){
		        	// die('hehehe');
		        	// Set extra option
		        	\PDF::setOptions(['dpi' => 150, 'defaultFont' => 'sans-serif']);
		        	// pass view file
		            $pdf = \PDF::loadView('pdfview');
		            return $pdf->download('pdfview.pdf');
		        }
		        return view('pdfview');
	        }else{
    			abort(404);
	        }
    	}else{
    		abort(404);
    	}
    }

}
