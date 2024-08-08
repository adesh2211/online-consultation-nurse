<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class BookingOrder extends Model
{
    protected $fillable = ['user_id', 'service_id','category_id','main_status','module_type','module_table_id','order_data'];
}
