<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\User;
use App\Model\RequestDetail;
use App\Model\RequestDate;
use App\Model\MasterPreference;
use App\Model\LastLocation;
use App\Model\CustomInfo;
use App\Model\FilterTypeOption;
use Carbon\Carbon;
use App\Helpers\Helper;
use App\Model\Image as ModelImage;
class EmergencyTimeSlot extends Model
{
    protected $guarded = []; 
}