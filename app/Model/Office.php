<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Office extends Model
{
	use SoftDeletes;

	protected $dates = ['deleted_at'];

	protected $appends = ['documents'];

	public function getDocumentsAttribute(){
        return \App\Model\Image::select('id','image_name as image','type')->where(['module_table'=>'clinics','module_table_id'=>$this->id])->get();
    }

    public static function getClinics($sp_id){
    	return self::where(['sp_id'=>$sp_id])->get();
    }
}
