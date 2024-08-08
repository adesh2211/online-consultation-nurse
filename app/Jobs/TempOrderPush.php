<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Model\BookingOrder;
use App\Model\Transaction;
use App\Notification,App\User;
class TempOrderPush implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $order = BookingOrder::where([
            'id'=>$this->data["order_id"],
            'main_status'=>'pending',
        ])->first();
        if($order){
            $order->main_status = 'failed';
            $order->save();
            $transaction = Transaction::where([
                'module_table'=>'booking_orders',
                'module_id'=>$order->id,
            ])->first();
            if($transaction){
                $admin = User::whereHas('roles',function($query){
                    return $query->where('name','admin');
                })->first();  
                $transaction->walletdata->increment('balance',$transaction->amount);
                $transaction->status = 'success';
                $transaction->transaction_type = 'refund';
                $transaction->save();
                $transaction->closing_balance = $transaction->walletdata->balance;
                $transaction->save();
                $payment = \App\Model\Payment::create(array(
                    'from'=>$admin->id,
                    'to'=>$transaction->walletdata->user_id,
                    'transaction_id'=>$transaction->id
                ));
                $received_from = User::find($admin->id);
                $notification = new Notification();
                $notification->sender_id = $admin->id;
                $notification->receiver_id = $transaction->walletdata->user_id;
                $notification->module_id = $payment->id;
                $notification->module ='payment';
                $notification->notification_type ='AMOUNT_RECEIVED';
                $notification->message =__(
                    'notification.booking_amount_refund_text',[
                        'amount' => $transaction->amount,
                        'user_name'=>$received_from->name
                    ]);
                $notification->save();
                $notification->push_notification(array($transaction->walletdata->user_id),array('pushType'=>'AMOUNT_RECEIVED','message'=>__('notification.booking_amount_refund_text', ['amount' => $transaction->amount,'user_name'=>$received_from->name])));
            
            }
        }
    }
}
