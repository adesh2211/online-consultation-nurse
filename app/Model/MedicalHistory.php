<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class MedicalHistory extends Model
{
    //
    protected $table = 'medical_historys';

    protected $fillable = [
         'comment','request_id'
    ];

    /**
     * Get the Request History From RequestHistory Model.
     */
    public function request()
    {
        return $this->hasOne('App\Model\Request','id','request_id');
    }
}
