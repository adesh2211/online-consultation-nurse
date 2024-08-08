<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Config;
use App\Model\ServiceProviderSlot;
use Auth;
use Session;
use  App\User;
use App\Model\Payment;
use App\Model\Wallet;
use DB;
use App\Helpers\Helper;
use App\Model\Feedback;
use DateTime;
use App\Notification;
use App\Model\CategoryServiceType;
use Illuminate\Support\Facades\Validator;
use Google\Model;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Hash,Mail;
use Redirect,Response,File,Image;
use Illuminate\Support\Facades\URL;
use App\Model\Role;
use App\Model\Package;
use Carbon\Carbon;
use App\Model\Profile;
use App\Model\UserPackage;
use App\Model\Transaction;
use App\Model\Card;
use App\Model\SocialAccount;
use App\Model\BankAccount;

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;
class PaymentController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        //$this->middleware('auth'); // later enable it when needed user login while payment
    }

    // start page form after start
    public function pay() {
        return view('authrise.pay');
    }

    public function handleonlinepay(Request $request) {
        $input = $request->input();
        
        /* Create a merchantAuthenticationType object with authentication details
          retrieved from the constants file */
        $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
        $merchantAuthentication->setName(env('MERCHANT_LOGIN_ID'));
        $merchantAuthentication->setTransactionKey(env('MERCHANT_TRANSACTION_KEY'));

        // Set the transaction's refId
        $refId = 'ref' . time();
        $cardNumber = preg_replace('/\s+/', '', $input['cardNumber']);
        
        // Create the payment data for a credit card
        $creditCard = new AnetAPI\CreditCardType();
        $creditCard->setCardNumber($cardNumber);
        $creditCard->setExpirationDate($input['expiration-year'] . "-" .$input['expiration-month']);
        $creditCard->setCardCode($input['cvv']);

        // Add the payment data to a paymentType object
        $paymentOne = new AnetAPI\PaymentType();
        $paymentOne->setCreditCard($creditCard);

        // Create a TransactionRequestType object and add the previous objects to it
        $transactionRequestType = new AnetAPI\TransactionRequestType();
        $transactionRequestType->setTransactionType("authCaptureTransaction");
        $transactionRequestType->setAmount($input['amount']);
        $transactionRequestType->setPayment($paymentOne);

        // Assemble the complete transaction request
        $requests = new AnetAPI\CreateTransactionRequest();
        $requests->setMerchantAuthentication($merchantAuthentication);
        $requests->setRefId($refId);
        $requests->setTransactionRequest($transactionRequestType);

        // Create the controller and get the response
        $controller = new AnetController\CreateTransactionController($requests);
        $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);

        if ($response != null) {
            // Check to see if the API request was successfully received and acted upon
            if ($response->getMessages()->getResultCode() == "Ok") {
                // Since the API request was successful, look for a transaction response
                // and parse it to display the results of authorizing the card
                $tresponse = $response->getTransactionResponse();

                if ($tresponse != null && $tresponse->getMessages() != null) {
//                    echo " Successfully created transaction with Transaction ID: " . $tresponse->getTransId() . "\n";
//                    echo " Transaction Response Code: " . $tresponse->getResponseCode() . "\n";
//                    echo " Message Code: " . $tresponse->getMessages()[0]->getCode() . "\n";
//                    echo " Auth Code: " . $tresponse->getAuthCode() . "\n";
//                    echo " Description: " . $tresponse->getMessages()[0]->getDescription() . "\n";
                    $message_text = $tresponse->getMessages()[0]->getDescription().", Transaction ID: " . $tresponse->getTransId();
                    $msg_type = "success_msg";    
                    
                    // \App\PaymentLogs::create([                                         
                    //     'amount' => $input['amount'],
                    //     'response_code' => $tresponse->getResponseCode(),
                    //     'transaction_id' => $tresponse->getTransId(),
                    //     'auth_id' => $tresponse->getAuthCode(),
                    //     'message_code' => $tresponse->getMessages()[0]->getCode(),
                    //     'name_on_card' => trim($input['owner']),
                    //     'quantity'=>1
                    // ]);
                } else {
                    $message_text = 'There were some issue with the payment. Please try again later.';
                    $msg_type = "error_msg";                                    

                    if ($tresponse->getErrors() != null) {
                        $message_text = $tresponse->getErrors()[0]->getErrorText();
                        $msg_type = "error_msg";                                    
                    }
                }
                // Or, print errors if the API request wasn't successful
            } else {
                $message_text = 'There were some issue with the payment. Please try again later.';
                $msg_type = "error_msg";                                    

                $tresponse = $response->getTransactionResponse();

                if ($tresponse != null && $tresponse->getErrors() != null) {
                    $message_text = $tresponse->getErrors()[0]->getErrorText();
                    $msg_type = "error_msg";                    
                } else {
                    $message_text = $response->getMessages()->getMessage()[0]->getText();
                    $msg_type = "error_msg";
                }                
            }
        } else {
            $message_text = "No response returned";
            $msg_type = "error_msg";
        }
        return back()->with($msg_type, $message_text);
    }

    public static function payoutWalletToBankAccount(Request $request) {
        try{
          
            $user = Auth::user();
            $rules = ['bank_id' => 'required|exists:bank_accounts,id',
                     'amount'=>'required|numeric|min:500',
            ];
            $validator = Validator::make($request->all(),$rules);
            if ($validator->fails()) {
                return response(array('status' => 'error', 'statuscode' => 400, 'message' =>
                    $validator->getMessageBag()->first()), 400);
            }
        return    $input = $request->all();
            if($user->wallet->balance < $input['amount']){
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>__("Keep a minimum amount ".$input['amount'])), 400);
            }
            $deposit_to = array(
                'balance'=>$input['amount'],
                'user'=>$user,
                'from_id'=>1,
            );
            $transaction = Transaction::createPayoutRequest($deposit_to); 
            $payoutrequest  = new PayoutRequest();  
            $payoutrequest->transaction_id = $transaction->id;
            $payoutrequest->account_id = $input['bank_id'];
            $payoutrequest->vendor_id = $user->id;
            $payoutrequest->status = 'pending';
            if($payoutrequest->save()){
                return response(['status' => "success", 'statuscode' => 200,
                                'message' => __('Payout Request Created'), 'data' =>(Object)[]], 200);
            }
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage()], 500);
        }
    }

    public static function postAddBankAccount(Request $request) {
        try{
            //return $request->all();
            $user = Auth::user();
            $rules = ['country' => 'required',
                     'currency'=>'required',
                     'account_holder_name'=>'required',
                     'account_holder_type'=>'required',
                     'ifc_code'=>'required',
                     'account_number'=>'required',
            ];
            $validator = Validator::make($request->all(),$rules);
            if ($validator->fails()) { 
                return redirect('/service_provider/bank-accounts/')->withErrors($validator)->withInput();
              
            }
           $message = 'something went wrong';
            $input = $request->all();
            if(isset($input['bank_id'])){ 
                $bankaccount = \App\Model\BankAccount::where(['user_id'=>$user->id,'id'=>$input['bank_id']])->first();
            }else{
                $bankaccount = \App\Model\BankAccount::where(['user_id'=>$user->id,'account_number'=>$request->account_number])->first();
                if($bankaccount){
                   return redirect('/service_provider/bank-accounts/')->with('status.error', 'Account already exist');
                }
                $bankaccount = new \App\Model\BankAccount();
            }
          
            $bankaccount->holder_name =  $request->account_holder_name;
            $bankaccount->account_number = $request->account_number;
            $bankaccount->ifc_code = $request->ifc_code;
            $bankaccount->account_type = $request->account_holder_type;
            $bankaccount->country = $request->country;
            $bankaccount->currency = $request->currency;
            $bankaccount->user_id = $user->id;
            if(isset($input['institution_number'])){
                $bankaccount->institution_number = $input['institution_number'];
            }
            if(isset($input['transit_number'])){
                $bankaccount->transit_number = $input['transit_number'];
            }

            if(isset($input['bank_name'])){
                $bankaccount->bank_name = $input['bank_name'];
            }
            // $response = \Stripe\Account::createExternalAccount(
            //  $user->stripe_account_id,
            //  ['external_account' => array(
            //     'object'=>'bank_account',
            //     'country'=>$request->country,
            //     'currency'=>$request->currency,
            //     'account_holder_name'=>$request->account_holder_name,
            //     'account_holder_type'=>$request->account_holder_type,
            //     'routing_number'=>$request->ifc_code,
            //     'account_number'=>$request->account_number,
            //     )]
            // );
            if($bankaccount->save()){
                return redirect('/service_provider/bank-accounts/')->with('status.success', 'Bank Added');
                // return response(['status' => "success", 'statuscode' => 200,
                //                 'message' => __('Bank Added'), 'data' =>['bank_accounts'=>$user->getAttachedBanks($user)]], 200);
            }
            return response(array('status' => "error", 'statuscode' => 400, 'message' =>__($message)), 400);
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage()], 500);
        }
    }

    public static function postCompleteChat(Request $request) {
        try{
            $user = Auth::user();
           $rules = ['request_id' => 'required'];
            $validator = Validator::make($request->all(),$rules);
            if ($validator->fails()) {
                return response(array('status' => 'error', 'statuscode' => 400, 'message' =>
                    $validator->getMessageBag()->first()), 400);
            }
            $message = null;
            $service_id = \App\Model\Service::getServiceId('chat');
          
            $request_data = \App\Model\Request::where([
                'id'=>$request->request_id,
                'service_id'=>$service_id])
            ->first();
           
            if($request_data && $request_data->requesthistory->status=='in-progress'){
                $last_message = \App\Model\Message::getLastMessage($request_data);
             
                if($last_message){
                    $new_time = $last_message->created_at;
                    $old_time = $request_data->requesthistory->updated_at;
                    $diff_in_duration = $new_time->diffInSeconds($old_time);
                }else{
                    $new_time = Carbon::now();
                    $diff_in_duration = $new_time->diffInSeconds($request_data->requesthistory->updated_at);
                }
                if(\Config::get('client_connected') && \Config::get('client_data')->domain_name=='care_connect_live'){
                    //reorder token number
                 
                   $get_data = \App\Model\Request::where('to_user',Auth::user()->id)
                                  // ->where('id','!=',$request->request_id)
                                   ->where('booking_date',$request_data->booking_date)
                                   ->whereHas('requesthistory', function ($query) {
                                       $query->whereNotIn('status',['failed','completed']);
                                   })->orderby('id','asc')
                                   ->where('token_number', '!=', NULL)->get();

                   $get_selected_data = \App\Model\Request::where('to_user',Auth::user()->id)
                       ->where('id','=',$request->request_id)
                       ->where('booking_date',$request_data->booking_date)
                       ->whereHas('requesthistory', function ($query) {
                           $query->whereNotIn('status',['failed','completed']);
                       })
                       ->where('token_number', '!=', NULL)->first();
                  // $i = 1;

                   $current_token_selected = $get_selected_data->token_number;

                   foreach($get_data as $record)
                   {
                      
                       if($record->id == $request->request_id)
                       {
                           
                               $update_token_number = Null;
                         
                       }
                       else
                       {
       

                           if($current_token_selected != null)
                           {
                               $update_token_number = $record->token_number;

                               if($record->token_number > $current_token_selected)
                               {
                                   $update_token_number = $record->token_number - 1;
                               }
                           }


                           
                       }
                      
                       $update_token = \App\Model\Request::where('to_user', Auth::user()->id)
                                       ->where('booking_date',$request_data->booking_date)
                                       ->where('id',$record->id)
                                       ->whereHas('requesthistory', function ($query) {
                                           $query->whereNotIn('status',['failed','completed']);
                                       })
                                       ->update([
                                           'token_number'  => $update_token_number
                                           ]);
                       if($record->id != $request->request_id && $record->token_number > $current_token_selected)
                       {
                           $status = ucwords(strtolower(str_replace('_', ' ', 'token updated')));
                           $notification = new Notification();
                        
                           $notification->sender_id = $record->from_user;
                           $notification->receiver_id = $record->to_user;
                           
                           $notification->module_id = $record->id;
                           $notification->module ='request';
                           $notification->notification_type = strtoupper($status);
                           $notification->message =__('notification.token_update_req', ['token_number' => $update_token_number]);
                           $notification->save();
                           $notification->push_notification(
                               array($notification->receiver_id),
                               array('pushType'=>strtoupper($status),
                                   'message'=>__('notification.token_update_req', ['token_number' => $update_token_number]),
                                   'request_time'=>$record->booking_date,
                                   'service_type'=>$record->servicetype->type,
                                   'sender_name'=>$user->name,
                                   'sender_image'=>$user->profile_image,
                                   'request_id'=>$record->id,
                                   'call_id'=>'',
                                   'token_number' => $update_token_number
                               ));
                       }
                  //$i--;   
                   }

                 

               }
              
                $request_data->requesthistory->status = 'completed';
                $request_data->requesthistory->increment('duration',$diff_in_duration);
               $request_data->requesthistory->save();
              
                $deposit_to = array(
                        'user'=>$request_data->sr_info,
                        'from_id'=>$request_data->cus_info->id,
                        'request_id'=>$request_data->id,
                        'status'=>'succeeded'
                    );
                    
               
                $data = Transaction::updateDeposit($deposit_to);
              
                $notification = new Notification();
                $notification->sender_id = $request_data->to_user;
                $notification->receiver_id = $request_data->from_user;
                $notification->module_id = $request_data->id;
                $notification->module ='request';
                $notification->notification_type ='REQUEST_COMPLETED';
                $notification->message ="Your Request has been completed";
                $notification->save();

                $notification = new Notification();
                $notification->sender_id = $request_data->from_user;
                $notification->receiver_id = $request_data->to_user;
                $notification->module_id = $request_data->id;
                $notification->module ='request';
                $notification->notification_type ='REQUEST_COMPLETED';
                $notification->message ="Your Request has been completed";
                $notification->save();

                $notification->push_notification(array($request_data->to_user,$request_data->from_user),array('pushType'=>'REQUEST_COMPLETED',
                    'request_id'=>$request_data->id,
                    'message'=>__('Your Request has been completed')));
                return response(array('status' => "success", 'statuscode' => 200, 'message' =>__('Request Completed')), 200);
            }else{
                $message = 'In-Progress Chat Request Not Found';
            }
            return response(array('status' => "error", 'statuscode' => 400, 'message' =>__($message)), 400);
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage()], 500);
        }
    }


    public  function postSubscriptionTopic(Request $request) {
        try{
            $user = Auth::user();
            $rules = ['topic_id' => 'required|exists:topics,id'];
            $validator = Validator::make($request->all(),$rules);
            if ($validator->fails()) {
                return response(array('status' => "error", 'statuscode' => 400, 'message' =>
                    $validator->getMessageBag()->first()), 400);
            }
            $package = \App\Model\Topic::where('id',$request->topic_id)->first();
            if($user->wallet->balance < $package->price){
                return response(['status' => "success", 'statuscode' => 500,'message' => __('insufficient balance'),'data'=>['amountNotSufficient'=>true]], 200);
            }
            $userpackage  = new \App\Model\SubscribeTopic();
            $userpackage->user_id = $user->id;
            $userpackage->topic_id = $package->id;
            $userpackage->expired_on = \Carbon\Carbon::now()->addMonth(1)->format('Y-m-d H:i:s');
            if($userpackage->save()){
                $user->wallet->decrement('balance',$package->price);
                $transaction = Transaction::create(array(
                        'amount'=>$package->price,
                        'transaction_type'=>'subscribe_topics',
                        'status'=>'success',
                        'wallet_id'=>$user->wallet->id,
                        'closing_balance'=>$user->wallet->balance,
                ));
                if($transaction){
                    $transaction->module_table = 'subscribe_topics';
                    $transaction->module_id = $package->id;
                    $transaction->save();
                    $payment = Payment::create(array('from'=>$package->created_by,'to'=>$user->id,'transaction_id'=>$transaction->id));
                }

                /* For Service Provider */
                $create_by = User::where('id',$package->created_by)->first();
                $create_by->wallet->increment('balance',$package->price);
                $transaction = Transaction::create(array(
                        'amount'=>$package->price,
                        'transaction_type'=>'deposit',
                        'status'=>'success',
                        'wallet_id'=>$create_by->wallet->id,
                        'closing_balance'=>$create_by->wallet->balance,
                ));
                if($transaction){
                    $transaction->module_table = 'subscribe_topics';
                    $transaction->module_id = $package->id;
                    $transaction->save();
                    $payment = Payment::create(array('from'=>$user->id,'to'=>$package->created_by,'transaction_id'=>$transaction->id));
                }
            }

            return response(array(
                'status' => "success",
                'statuscode' => 200,
                'data'=>(Object)[],
                'message' =>__('Subscribe Successfully')), 200);
        }catch(Exception $ex){
            return response(['status' => "error", 'statuscode' => 500, 'message' => $ex->getMessage()], 500);
        }
    }
}
