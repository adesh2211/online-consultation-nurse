<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\User;
use db;
use App\Model\Request as RequestData; 
use App\Model\RequestHistory; 
use App\Model\Service;

class RequestSmsEmail implements ShouldQueue
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
     
        $get_email_template = DB::table('templates')->where('template_name', $this->data['template_name'])->first();

        $get_user_details = DB::table('users')->where('id', $this->data['user_id'])->first();

        $get_consultant_details = DB::table('users')->where('id', $this->data['consultant_id'])->first();

        $get_booking_details = DB::table('requests')->where('id', $this->data['request_id'])->first();

        $get_service_details = DB::table('services')->where('id', $this->data['service_id'])->first();

        $template_text = $get_email_template->message;

        if($get_user_details)
        {
            $template_text = str_replace("%booking_date", $get_booking_details->booking_date, $template_text);
            $template_text = str_replace("%type", $get_service_details->type, $template_text);
            $template_text = str_replace("%doctor_name", $get_consultant_details->user_name, $template_text);
            $template_text = str_replace("%doctor_email", $get_consultant_details->email, $template_text);
            $template_text = str_replace("%user_name", $get_user_details->user_name, $template_text);
            $template_text = str_replace("%email", $get_user_details->email, $template_text);
        }
       
        if($get_email_template->type == 'Email')
        {
            $data = [
                'msg_text'	=>	$template_text
            ];

            $emails_to = array(
                'email' =>  $get_user_details->email,
                'name'  =>  $get_user_details->user_name,
                'subject'	=>	$get_email_template->template_name
            );
            
            \Mail::send('emails.generic', $data, function($message) use ($emails_to)
            {
                $message->to($emails_to['email'], $emails_to['name'])->subject($emails_to['subject']);
            });
        }
    //  }
    }
}
