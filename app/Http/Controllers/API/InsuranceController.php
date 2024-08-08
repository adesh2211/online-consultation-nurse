<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
class InsuranceController extends Controller
{
	/**
     * @SWG\Post(
     *     path="/claimmd/v1/eligibility",
     *     description="insurance eligibility",
     * tags={"Insurance"},
     *     security={
     *     {"Bearer": {}},
     *   },
     *  @SWG\Parameter(
     *         name="UserId",
     *         in="query",
     *         type="string",
     *         description="[optional] Account UserID to associate this request with.",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="File",
     *         in="query",
     *         type="file",
     *         description="270 file data",
     *         required=true,
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="",
     *     ),
     *     @SWG\Response(
     *         response=400,
     *         description="Please provide all data"
     *     )
     * )
     */
    public function verifyEligibility(Request $request){
        try{
        	$user  = Auth::user();
            $input = $request->all();
            $rules = ['File'=>'required'];
            $validation = \Validator::make($input,$rules);            
            if($validation->fails()){
                return response(array('status' => 'error', 'statuscode' => 400, 'message' =>
                    $validation->getMessageBag()->first()), 400);
      		}
           	$insurance_query = [];
            $input['AccountKey'] = "10115oVMQSJTDdaPkjErokfTgxQcX";
            // print_r($input['File']);die;
            // $resume_file = curl_file_create(realpath($input['File']->tmp_name),$input['File']->type,$input['File']->name);
            // print_r($fields);die;
            $url = "https://www.claim.md/services/elig/";
            $curl = curl_init();
            curl_setopt_array($curl, array(
              CURLOPT_URL => $url,
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => "",
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 30,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => "POST",
              CURLOPT_POSTFIELDS =>$input,
              CURLOPT_HTTPHEADER => array(
              	"cache-control: no-cache",
              	"content-type: multipart/form-data",
                "accept: application/json"
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
            	print_r($response);die;
                $result = json_decode($response);
                if(isset($result->Loop_2000A->Loop_2100A) && isset($result->Loop_2000A->Loop_2100A->PER_InformationSourceContactInformation_2100A)){
                    // $datenow = new DateTime("now", new DateTimeZone('UTC'));
                    // $datenowone = $datenow->format('Y-m-d H:i:s');
                    // $user = Auth::user();
                    // $user->insurance_verified = $datenowone;
                    // $user->save();
                //     return response(array(
                //     'status' => 'success',
                //     'statuscode' => 200,
                //     'data'=>[
                //         'insurance'=>$result->Loop_2000A->Loop_2100A->PER_InformationSourceContactInformation_2100A
                //         ],
                //     'message' =>'Insurance Verified')
                // ,200);
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

    /**
     * @SWG\Post(
     *     path="/claimmd/v2/eligibility",
     *     description="insurance eligibility",
     * tags={"Insurance"},
     *     security={
     *     {"Bearer": {}},
     *   },
     *  @SWG\Parameter(
     *         name="UserId",
     *         in="query",
     *         type="string",
     *         description="[optional] Account UserID to associate this request with.",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="ins_name_l",
     *         in="query",
     *         type="string",
     *         description="ins_name_l Insured/Patient last name",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="ins_name_m",
     *         in="query",
     *         type="string",
     *         description="ins_name_m Insured/Patient middle name",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="ins_name_f",
     *         in="query",
     *         type="string",
     *         description="ins_name_f Insured/Patient First name",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="payerid",
     *         in="query",
     *         type="string",
     *         description="Payer ID to identify which payer to query",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="pat_rel",
     *         in="query",
     *         type="string",
     *         description="Payer ID to identify which payer to query.",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="service_code",
     *         in="query",
     *         type="string",
     *         description="Benefit type code",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="fdos",
     *         in="query",
     *         type="string",
     *         description="Service date",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="prov_npi",
     *         in="query",
     *         type="string",
     *         description="Provider NPI",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="prov_name_l",
     *         in="query",
     *         type="string",
     *         description="Provider prov_name_l",
     *         required=true,
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="",
     *     ),
     *     @SWG\Response(
     *         response=400,
     *         description="Please provide all data"
     *     )
     * )
     */
    public function verifyEligibilityV2(Request $request){
        try{
        	$user  = Auth::user();
            $input = $request->all();
            $rules = [
            	'ins_name_l'=>'required',
            	// 'ins_name_m'=>'required',
            	'ins_name_f'=>'required',
            	'payerid'=>'required',
            	'pat_rel'=>'required',
            	'service_code'=>'required',
            	'fdos'=>'required',
            	'prov_npi'=>'required',
            ];
            $validation = \Validator::make($input,$rules);            
            if($validation->fails()){
                return response(array('status' => 'error', 'statuscode' => 400, 'message' =>
                    $validation->getMessageBag()->first()), 400);
      		}
           	$insurance_query = [];
            $input['AccountKey'] = "10115oVMQSJTDdaPkjErokfTgxQcX";
            // print_r($input['File']);die;
            // $resume_file = curl_file_create(realpath($input['File']->tmp_name),$input['File']->type,$input['File']->name);
            // print_r($fields);die;
            $url = "https://www.claim.md/services/eligxml/";
            $curl = curl_init();
            curl_setopt_array($curl, array(
              CURLOPT_URL => $url,
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => "",
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 30,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => "POST",
              CURLOPT_POSTFIELDS =>$input,
              CURLOPT_HTTPHEADER => array(
              	"cache-control: no-cache",
              	"content-type: multipart/form-data",
                "accept: application/json"
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
                if(isset($result->error) && isset($result->error->error_mesg)){
	                    return response(array(
	                    'status' => 'error',
	                    'statuscode' => 400,
	                    'message' =>$result->error->error_mesg)
	                ,400);
                }else{
	                    return response(array(
	                    'status' => 'success',
	                    'statuscode' => 200,
	                    'data'=>['result'=>$result],
	                    'message' =>'result...')
	                ,200);
                }
            }
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage()], 500);
        } 
    }
    
}
