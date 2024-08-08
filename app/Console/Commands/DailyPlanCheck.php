<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User,App\Model\SubscribePlan;
use DB,Config;
use Carbon\Carbon;
use App\Notification;
use App\Model\Plan;
class DailyPlanCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mypath:plancheck';

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
        $database_name = 'db_mp2r';
        $default = [
            'driver' => env('DB_CONNECTION','mysql'),
            'host' => env('DB_HOST'),
            'port' => env('DB_PORT'),
            'database' => $database_name,
            'username' => env('DB_USERNAME'),
            'password' => env('DB_PASSWORD'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => false,
            'engine' => null
        ];
        $five_start_date = Carbon::now()->addDays(5)->format('Y-m-d').' 00:00:00';
        $five_end_date = Carbon::now()->addDays(5)->format('Y-m-d').' 23:59:59';
        Config::set("database.connections.$database_name", $default);
        DB::setDefaultConnection($database_name);
        $plans = ["com.mp2r.premium", "com.mp2r.executive"];
        $fiveDaysNotifications = SubscribePlan::whereHas('plan', function ($query) use($plans){
                    $query->whereIn('plan_id', $plans);
        })->whereBetween('expired_on',[$five_start_date,$five_end_date])
        ->groupBy('user_id')->get();
        \Log::info("fiveDaysNotifications ".count($fiveDaysNotifications));
        $this->info("fiveDaysNotifications ".count($fiveDaysNotifications));
        foreach ($fiveDaysNotifications as $key => $fiveDaysNotification) {
            $notification = new Notification();
            $notification->sender_id = 1;
            $notification->receiver_id = $fiveDaysNotification->user_id;
            $notification->module_id = $fiveDaysNotification->plan_id;
            $notification->module ='plan';
            $notification->notification_type ='PLAN_EXPIRING';
            $notification->message =__('Your Plan is expiring in next 5 days. Please Recharge to Continue with plan');
            $notification->save();
            $notification->push_notification(array($fiveDaysNotification->user_id),array('pushType'=>'PLAN_EXPIRING','message'=>__('Your Plan is expiring in next 5 days. Please Recharge to Continue with plan')));
        }

        $today_start_date = Carbon::now()->addDays(-1)->format('Y-m-d').' 00:00:00';
        $today_end_date = Carbon::now()->addDays(-1)->format('Y-m-d').' 23:59:59';
        $expiredPlans = SubscribePlan::whereHas('plan', function ($query) use($plans){
                    $query->whereIn('plan_id', $plans);
        })->whereBetween('expired_on',[$today_start_date,$today_end_date])
        ->groupBy('user_id')->get();
         \Log::info("expiredPlans  ".count($expiredPlans));
        $this->info("expiredPlans  ".count($expiredPlans));
        $basic = Plan::where('plan_id','com.mp2r.basic')->first();
        $expired_on = \Carbon\Carbon::now()->addMonth(1)->format('Y-m-d H:i:s');
        foreach ($expiredPlans as $key => $expiredPlan) {
            $expiredPlan->plan_id =  $basic->id;
            $expiredPlan->expired_on =  $expired_on;
            $expiredPlan->save();
            $notification = new Notification();
            $notification->push_notification(array($expiredPlan->user_id),array('pushType'=>'PLAN_EXPIRED','message'=>__('Your plan is expired')));
        }

    }
}
