<?php

namespace App\Console\Commands;
use DB;
use App\Notification;
use App\Helpers\Helper;
use Carbon\Carbon,Config;
use App\Model\DailyWaterDate;
use Illuminate\Console\Command;

class WaterIntakeReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:WaterIntakeReminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Helper::connectByDomain('heal');
        $daily_water_records = DailyWaterDate::with('user')->with(['dailyglass' => function($query) { 
            $query->whereDate('created_at', Carbon::today()); 
        }])->whereDate('created_at', Carbon::today())->get();
        foreach ($daily_water_records as $daily_water_record) {
            if(!$daily_water_record->dailyglass){
                $notification = new Notification();
                $response = $notification->push_notification(array($daily_water_record->user->id),array('pushType'=>'WATER_INTAKE','message'=> "It's time to take water."));
            }else{
                $to = Carbon::createFromFormat('Y-m-d H:i:s', $daily_water_record->dailyglass->created_at);
                $from = Carbon::now();
                $diff_in_hour = $to->diffInHours($from);
                if($diff_in_hour >= 2 && $daily_water_record->total_usage < $daily_water_record->daily_limit){
                    $notification = new Notification();
                    $response = $notification->push_notification(array($daily_water_record->user->id),array('pushType'=>'WATER_INTAKE','message'=>"It's time to take water."));
                }
            }
        }
    }
}
