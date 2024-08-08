<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\Model\StudyMaterial;
use Illuminate\Database\Eloquent\SoftDeletes;
class Topic extends Model
{
	use SoftDeletes;
	/**
    * The attributes that should be mutated to dates.
    *
    * @var array
    */
    protected $dates = ['deleted_at'];
	public static function getTopicData($topic_id){
		$tp = Self::where('id',$topic_id)->first();
		if($tp){
			$tp->study_materials = StudyMaterial::where(['topic_id'=>$tp->id])->get();
		}
		return $tp;
	}

	public function subjects(){
      return $this->HasMany('App\Model\SubjectTopic','topic_id','id');
    }

    public function sp_data(){
      return $this->hasOne('App\User','id','created_by');
    }
}
