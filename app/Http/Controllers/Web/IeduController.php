<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Config,Auth;
use App\Model\StudyMaterial;
use App\Model\Category;
use App\Model\Topic;
use App\Model\SubjectTopic;
use App\Http\Traits\CategoriesTrait;
class IeduController extends Controller
{
	use CategoriesTrait;
    public function getStudyMaterial(){
        
    	$classes = $this->parentCategories();
        
    	foreach ($classes as $key => $class) {
            $subjects_ids = [];
            $subjects = Category::where('parent_id',$class->id)->where('enable','=','1')->get();
            foreach($subjects as $sub)
            {
                array_push($subjects_ids, $sub->id);
            }

            array_push($subjects_ids, $class->id);
            array_push($subjects_ids, $class->parent_id);
    		
            $direct_topics = SubjectTopic::with('topic')->whereHas('topic',function($q){
                                    return $q->where('status','activate');
    		})->whereIn('subject_id',$subjects_ids)->groupBy('topic_id')->get();

            $class->topics = $direct_topics;
    	}

        // return json_encode($classes);
        //return json_encode($classes);
    	// print_r($classes);die;
    	return view('vendor.'.Config::get("client_data")->domain_name.'.stdudy-material',compact('classes'));
    }

    public function getgradesubjetcs()
    {
        $classes = $this->parentCategories();
        foreach ($classes as $key => $class) {
            $subcategories = Category::where('parent_id',$class->id)->where('enable','=','1')->get();
            $class->subjects = $subcategories;
        }
       // return $classes;
        return view('vendor.'.Config::get("client_data")->domain_name.'.grade',compact('classes'));
        
    }

    public function getWebCoursesPage(){
    	$courses =\App\Model\Course::get();
        foreach ($courses as $key => $value) {
                $value->total =\App\Model\SpCourse::where('course_id',$value->id)->with('doctor_data')
            ->whereHas('doctor_data', function($query){
                    return $query->where('account_verified','!=',null);
            })
            ->whereHas('doctor_data.roles', function($query){
                    return $query->where('name','service_provider');
            })->count();
        }
        return view('vendor.'.Config::get("client_data")->domain_name.'.courses',compact('courses'));
    }

    public function getWebEmsatsPage(){
    	$emsats =\App\Model\Emsat::orderBy('id', 'desc')->get();
        return view('vendor.'.Config::get("client_data")->domain_name.'.emsats',compact('emsats'));
    }

    

    public function getStudyMaterialDetail($id,Request $request){
        $topic_detail = Topic::getTopicData($id);
        $topic_detail->subscribe = false;
        if(Auth::check()){
            $userpackage = \App\Model\SubscribeTopic::where([
                'topic_id'=>$topic_detail->id,
                'user_id'=>Auth::user()->id
            ])->first();
            if($userpackage){
                $topic_detail->subscribe = true;
            }
        }
        //return json_encode($topic_detail);
    	// print_r($topic_detail);die;
    	return view('vendor.'.Config::get("client_data")->domain_name.'.stdudy-material-detail',compact('topic_detail'));
    }

    public function getSubjectTopics($subject_id,$class_id,Request $request)
    {
        $subjects = Category::where('parent_id',$class_id)->where('id',$subject_id)->where('enable','=','1')->first();
        if($subjects)
        {
            $topics = SubjectTopic::with('topic')
            ->whereHas('topic',function($q) use($subjects){
                    $q->where('subject_id',$subjects->id)
                        ->where('status','activate');
            })
            
            ->get();

        }
       
        return view('vendor.'.Config::get("client_data")->domain_name.'.topics',compact('subjects','topics'));
       
    }
}
