<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Course;
use App\Model\SpCourse,App\Model\Topic;
use App\Model\SubjectTopic,App\Model\StudyMaterial;
use App\User;
use App\Model\Category;
use App\Model\ConsultClass;
use App\Model\Package,App\Model\UserPackage,App\Model\Transaction,App\Model\Payment;
use App\Model\AdditionalDetail;
use App\Model\SpAdditionalDetail;
use App\Notification;
use Illuminate\Support\Facades\Auth;
use Validator,Hash,Mail,DB;
use DateTime,DateTimeZone;
use Redirect,Response,File,Image;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;
use Config;
use App\Model\EnableService;
use App\Model\CategoryServiceProvider;
use App\Helpers\Helper;

class CourseController extends Controller
{
    public function __construct() {
        $this->middleware('auth')->except(['getcourses','getTopicList','getTopicDetail','getEmsatList','getTeacherList']);
    }
    /**
     * @SWG\Get(
     *     path="/emsats",
     *     description="Get Emsats List",
     * tags={"Category"},
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

     public function getEmsatList(Request $request) {
        try{
            $per_page = (isset($request->per_page)?$request->per_page:10);
            $Emsats = \App\Model\Emsat::select('id','title','icon','question','marks')->orderBy('id', 'desc')->cursorPaginate($per_page);
            if(Auth::guard('api')->check()){
                foreach ($Emsats as $key => $Emsat) {
                    $sp_em = \App\Model\SpEmsat::where([
                        'emsat_id'=>$Emsat->id,
                        'sp_id'=>Auth::guard('api')->user()->id])->first();
                    if($sp_em){
                        $Emsat->isSelected = true;
                        $Emsat->price = $sp_em->price;
                    }
                }
            }
            $after = null;
            if($Emsats->meta['next']){
                $after = $Emsats->meta['next']->target;
            }
            $before = null;
            if($Emsats->meta['previous']){
                $before = $Emsats->meta['previous']->target;
            }
            $per_page = $Emsats->perPage();
            return response(['status' => "success", 'statuscode' => 200,
                                'message' => __('course'), 'data' =>[
                                    'emsats'=>$Emsats->items(),
                                    'after'=>$after,
                                    'before'=>$before,
                                    'per_page'=>$per_page
                                ]], 200);
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage()], 500);
        }
    }

    /**
     * @SWG\Get(
     *     path="/emsat-teachers",
     *     description="Get Emsat Teacher",
     * tags={"Category"},
     *  @SWG\Parameter(
     *         name="id",
     *         in="query",
     *         type="integer",
     *         description="Course ID",
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

     public function getTeacherList(Request $request) {
        try{
            $rules = [
                    'id'=>'required',    
            ];
            $validator = Validator::make($request->all(),$rules);
            if ($validator->fails()) {
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                    $validator->getMessageBag()->first()), 400);
            }
            $per_page = (isset($request->per_page)?$request->per_page:10);

            $Emsats = \App\Model\SpEmsat::with('teacher_data')->whereHas('teacher_data.roles', function ($query) {
                           $query->whereIn('name',['service_provider','customer']);
                        })->groupBy('sp_id')->where('emsat_id',$request['id'])->orderBy('id','asc')->cursorPaginate($per_page);
            // if(Auth::guard('api')->check()){
                foreach ($Emsats as $key => $Emsat) {
                    $Emsat->teacher_data = Helper::getMoreData($Emsat->teacher_data);
                }
            // }
            $after = null;
            if($Emsats->meta['next']){
                $after = $Emsats->meta['next']->target;
            }
            $before = null;
            if($Emsats->meta['previous']){
                $before = $Emsats->meta['previous']->target;
            }
            $per_page = $Emsats->perPage();
            return response(['status' => "success", 'statuscode' => 200,
                                'message' => __('Teachers'), 'data' =>[
                                    'teachers'=>$Emsats->items(),
                                    'after'=>$after,
                                    'before'=>$before,
                                    'per_page'=>$per_page
                                ]], 200);
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage()], 500);
        }
    }


    /**
     * @SWG\Get(
     *     path="/courses",
     *     description="Get Class Courses",
     * tags={"Category"},
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

     public static function getcourses(Request $request) {
        try{
            $per_page = (isset($request->per_page)?$request->per_page:10);
            $Course = Course::orderBy('id', 'desc')->cursorPaginate($per_page);
            
            $after = null;
            if($Course->meta['next']){
                $after = $Course->meta['next']->target;
            }
            $before = null;
            if($Course->meta['previous']){
                $before = $Course->meta['previous']->target;
            }
            $per_page = $Course->perPage();
            return response(['status' => "success", 'statuscode' => 200,
                                'message' => __('course'), 'data' =>['spCourses'=>$Course->items(),'after'=>$after,'before'=>$before,'per_page'=>$per_page]], 200);
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage()], 500);
        }
    }


    /**
     * @SWG\POST(
     *     path="/sp-course",
     *     description="Course For Service Provider",
     * tags={"Service Provider Course"},
     *     security={
     *     {"Bearer": {}},
     *   },
     *  @SWG\Parameter(
     *         name="course_id",
     *         in="query",
     *         type="integer",
     *         description="Course ID",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="emsats",
     *         in="query",
     *         type="integer",
     *         description="emsats [{'id':'emsat id','price':100}]",
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

    public static function postspcourses(Request $request) {
        try{
            $rules = [
                     'course_id'=>'required',    
            ];
            $validator = Validator::make($request->all(),$rules);
            if ($validator->fails()) {
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                    $validator->getMessageBag()->first()), 400);
            }
            $input = $request->all();
            $user=Auth::user();
            if(isset($input['course_id'])){
                $coursearray=explode(',',$input['course_id']);
                $data=[];
                SpCourse::where(['sp_id'=>$user->id])->delete();
                foreach ($coursearray as $courses) {
                    $spcourse = SpCourse::firstOrCreate([
                        'sp_id'=>$user->id,
                        'course_id'=>$courses,
                    ]);

                    $data[]=$spcourse;
                }
            }
            if(isset($input['emsats'])){
                if(!is_array($input['emsats'])){
                    $input['emsats'] =  json_decode($input['emsats'],true);
                }
                if(is_array($input['emsats'])){
                    $not_delete = [];
                    foreach ($input['emsats'] as $key => $emsat) {
                        $not_delete[] = $emsat['id'];
                        $sp_em = \App\Model\SpEmsat::firstOrCreate([
                            'emsat_id'=>$emsat['id'],
                            'sp_id'=>$user->id]
                        );
                        $sp_em->price = $emsat['price'];
                        $sp_em->save();
                    }
                    \App\Model\SpEmsat::where('sp_id',$user->id)->whereNotIn('emsat_id',$not_delete)->delete();
                } 
            }
            return response(['status' => "success", 'statuscode' => 200,'message' => __('SPcourse'), 'data' =>['spCourses'=>$user->getcourseSP($user->id)]], 200);
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage()], 500);
        }
    }
    /**
     * @SWG\POST(
     *     path="/topic",
     *     description="Add Topic",
     * tags={"Subjects"},
     *     security={
     *     {"Bearer": {}},
     *   },
     *  @SWG\Parameter(
     *         name="subject_ids",
     *         in="query",
     *         type="string",
     *         description="Subject Ids comma seprated",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="title",
     *         in="query",
     *         type="string",
     *         description="Topic Title",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="description",
     *         in="query",
     *         type="string",
     *         description="Topic Description",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="image_icon",
     *         in="query",
     *         type="string",
     *         description="Image Name",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="price",
     *         in="query",
     *         type="integer",
     *         description="Price",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="study_materials",
     *         in="query",
     *         type="string",
     *         description="[{'title':'title required true','description':'required false','type':'image,pdf,video,audio etc','file_name':'file_name required false'}]",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="id",
     *         in="query",
     *         type="string",
     *         description="Topic id when Edit",
     *         required=false,
     *     ),
     *  @SWG\Parameter(
     *         name="status",
     *         in="query",
     *         type="string",
     *         description="Status when Edit activate,deactivate,delete",
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
    public function addTopic(Request $request){
        try{
            $input = $request->all();
            if(isset($input['status']) && ($input['status']=='delete' || $input['status']=='activate' || $input['status']=='deactivate')){
                $rules = [];
            }else{
                $rules = [
                        'title'=>'required',    
                        'price'=>'required',   
                ];
            }
            if(isset($input['id'])){
                $rules['id'] = 'required|integer|exists:topics,id';
                $rules['status'] = 'required';
            }else{
                $rules['subject_ids'] = 'required';
                $rules['study_materials'] = 'required';
            }
            $validator = Validator::make($request->all(),$rules);
            if ($validator->fails()) {
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                    $validator->getMessageBag()->first()), 400);
            }
            $user=Auth::user();
            if(isset($input['id'])){
                $topic = Topic::where('id',$input['id'])->first();
                if($input['status']=='delete'){
                    $topic->delete();
                    return response(['status' => "success", 'statuscode' => 200,'message' => __('Topic Deleted')], 200);
                }else{
                    $topic->status = $input['status'];
                }
            }else{
                $topic = new Topic();
                $topic->created_by = $user->id;
            }
            $topic->title = isset($input['title'])?$input['title']:$topic->title;
            $topic->description = isset($input['description'])?$input['description']:$topic->description;
            $topic->image_icon = isset($input['image_icon'])?$input['image_icon']:$topic->image_icon;
            $topic->price = isset($input['price'])?$input['price']:$topic->price;
            if($topic->save()){
                if(isset($input['subject_ids'])){
                    $subject_ids=explode(',',$input['subject_ids']);
                    foreach ($subject_ids as $subject_id) {
                        SubjectTopic::firstOrCreate([
                            'topic_id'=>$topic->id,
                            'subject_id'=>$subject_id,
                        ]);
                    }
                }
            }
            if(isset($input['study_materials']) && $input['study_materials'] && is_array($input['study_materials'])){
                StudyMaterial::where('topic_id',$topic->id)->delete();
                foreach ($input['study_materials'] as $key => $study_mate) {
                   $study_material =  new StudyMaterial();
                   $study_material->topic_id = $topic->id;
                   $study_material->title = isset($study_mate['title'])?$study_mate['title']:null;
                   $study_material->description = isset($study_mate['description'])?$study_mate['description']:null;
                   $study_material->type = isset($study_mate['type'])?$study_mate['type']:'image';
                   $study_material->file_name = isset($study_mate['file_name'])?$study_mate['file_name']:null;
                   $study_material->added_by = $user->id;
                   $study_material->save();
                }
            }
            return response(['status' => "success", 'statuscode' => 200,'message' => __('Added or Updated')], 200);
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage()], 500);
        }
    }

    /**
     * @SWG\Get(
     *     path="/topic-list",
     *     description="Get Topic with Pagination",
     * tags={"Subjects"},
     *  @SWG\Parameter(
     *         name="subject_id",
     *         in="query",
     *         type="string",
     *         description="Subject Id",
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

    public function getTopicList($subject_id,Request $request){
       
        $input = $request->all();
        $per_page = (isset($request->per_page)?$request->per_page:10);
        $topics = Topic::query();
        $topics = $topics->whereHas('subjects', function ($query) use($subject_id) {
           $query->where('subject_id',$subject_id);
        });
        if(Auth::check() && Auth::user()->hasrole('service_provider')){
            $topics = $topics->where('created_by',Auth::user()->id);
        }
        if(Auth::check() && Auth::user()->hasrole('customer')){
            $topics = $topics->where('status','activate');
        }
        $topics = $topics->orderBy('id', 'desc')->cursorPaginate($per_page);
        $after = null;
        if($topics->meta['next']){
            $after = $topics->meta['next']->target;
        }
        $before = null;
        if($topics->meta['previous']){
            $before = $topics->meta['previous']->target;
        }
        $per_page = $topics->perPage();
        //return $topics;
        return view('vendor.'.Config::get("client_data")->domain_name.'.topics')->with(['topics'=>$topics->items(),'after'=>$after,'before'=>$before,'per_page'=>$per_page]);
        // return response(['status' => "success", 'statuscode' => 200,
        //                     'message' => __('course'), 'data' =>['topics'=>$topics->items(),'after'=>$after,'before'=>$before,'per_page'=>$per_page]], 200);
    }

    /**
     * @SWG\Get(
     *     path="/topic-detail",
     *     description="Get Topic Detail",
     * tags={"Subjects"},
     *  @SWG\Parameter(
     *         name="topic_id",
     *         in="query",
     *         type="string",
     *         description="Topic id",
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

    public function getTopicDetail(Request $request){
        $rules = ['topic_id'=>'required|integer|exists:topics,id'];
        $validator = Validator::make($request->all(),$rules);
        if ($validator->fails()) {
            return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                $validator->getMessageBag()->first()), 400);
        }
        $input = $request->all();
        $topic_detail = Topic::getTopicData($input['topic_id']);
        $topic_detail->subscribe = false;
        if(Auth::guard('api')->check()){
            $userpackage = \App\Model\SubscribeTopic::where([
                'topic_id'=>$topic_detail->id,
                'user_id'=>Auth::guard('api')->user()->id
            ])->first();
            if($userpackage){
                $topic_detail->subscribe = true;
            }
        }
        return response(['status' => "success", 'statuscode' => 200,
                            'message' => __('course'), 'data' =>['topic_detail'=>$topic_detail]], 200);
    }

    
}
