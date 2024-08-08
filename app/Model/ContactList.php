<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ContactList extends Model
{
    protected $fillable = [
        'name', 'parent_id', 'user_id','email','phone','country_code','type_label'
    ];
}
