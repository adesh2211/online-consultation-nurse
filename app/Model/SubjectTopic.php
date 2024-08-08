<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class SubjectTopic extends Model
{
    protected $fillable = ['subject_id','topic_id'];

    public function topic(){
        return $this->belongsTo('App\Model\Topic','topic_id');
    }
}
