<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class SpEmsat extends Model
{
    protected $fillable = [
        'emsat_id', 'price','sp_id'
    ];

    public function teacher_data(){
      return $this->belongsTo('App\User','sp_id');
    }
}
