<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Exception;
use App\Model\Transaction;
use App\Model\UserPackage;
use App\Model\Package;
use App\Notification;
use Illuminate\Support\Str;
use Config;
use App\Helpers\Helper;
class WebHookController extends Controller
{
	protected $razorpay;
    protected $razorpay_key;
    protected $razorpay_secret;

    /**
     * Instantiate a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->razorpay_key = "";
        $this->razorpay_secret = "";
        // $this->razorpay = new Api($this->razorpay_key,$this->razorpay_secret);
    }

    private function validateSignature(Request $request)
    {
        $webhookSecret = $this->razorpay_secret;
        $webhookSignature = $request->header('X-Razorpay-Signature');
        $payload = $request->getContent();
        $this->razorpay->utility->verifyWebhookSignature($payload, $webhookSignature, $webhookSecret);
    }

    public function getHandlePayStackWebhook(Request $request){
      $input = $request->all();
      $client_data = null;
      if(Config::get("client_connected") && Config::get("client_data")){
           $client_data = Config::get("client_data");
           $payment_gateway = $client_data->payment_type;
      }
      $paystack = new \Yabacon\Paystack($client_data->gateway_key);
      try
      {
        // verify using the library
        $tranx = $paystack->transaction->verify([
          'reference'=>$input['reference'], // unique to transactions
        ]);
      } catch(\Yabacon\Paystack\Exception\ApiException $e){
        echo "Payment Error $e->getMessage()";
        http_response_code(400);
        exit();
      }

      if ('success' === $tranx->data->status) {
        $transaction = Transaction::where([
              'transaction_id'=>$input['reference'],
              'payment_gateway'=>'paystack']
          )->first();
        if($transaction->status=="pending"  && $transaction->module_table=='add_money'){
              $transaction->walletdata->increment('balance',$transaction->amount);
              $transaction->status = 'success';
              $transaction->save();
              $notification = new Notification();
              $notification->push_notification(array($transaction->walletdata->user_id),array(
              'pushType'=>'BALANCE_ADDED',
              'transaction_id'=>$transaction->transaction_id,
              'message'=>__($transaction->amount." amount added into your wallet")));

              echo "Payment Success";
              http_response_code(200);
              exit();
          }else{
            echo "Already added balance";
            http_response_code(400);
            exit();
          }
      }else{
        
      }
    }

	public function getHandleRazorPayWebhook(Request $request){
		try{
            return;
            if(Config::get("client_connected") && Config::get("client_data")){
                 $client_data = Config::get("client_data");
                 $domain_name = $client_data->domain_name;
                 if ($client_data->domain_name=='physiotherapist' || $client_data->domain_name=='food') {
                     $this->razorpay_key = $client_data->gateway_key;
                     $this->razorpay_secret = $client_data->gateway_secret;
                 }
            }
            $this->razorpay = new Api($this->razorpay_key,$this->razorpay_secret);
			$this->validateSignature($request);
			$payload = $request->all();
			if ((!isset($payload['entity'])) || $payload['entity'] != 'event') {
            	$log = array(
	                'message'   => 'Error entity',
	                'data'      => 'Razor Pay',
	            );
	            \Log::info('Razor Pay '.json_encode($log));
        	}
        	$method = 'handle'.Str::studly(str_replace('.', '_', $payload['event']));
	        if (method_exists($this, $method)) {
	            return $this->{$method}($payload);
	        } else {
	            return $this->missingMethod();
	        }
		}catch(Exception $ex){
			$log = array(
                'message'   => $ex->getMessage().' '.$ex->getLine(),
                'data'      => 'Razor Pay',
            );
            \Log::info('Razor Pay '.json_encode($log));
		}
	}

    public function getHandleStripeWebhook(Request $request){
        $domain_name = 'default';
        $endpoint_secret = 'whsec_O0XwHZ1IcynEJCGHhdCqNgu54oCn1wwl';
        if(Config::get("client_connected") && Config::get("client_data")){
            $client_data = Config::get("client_data");
            $domain_name = $client_data->domain_name;
            if($client_data->domain_name=='curenik'){
                 $endpoint_secret = 'whsec_ijWER3VwWo043IQDyizIxvyQbM7TCDhM';
             }elseif ($client_data->domain_name=='mp2r') {
                 $endpoint_secret = 'whsec_Pj2aI9E65eprX0dsn7l33DQm8VKGwcP8';
             }elseif ($client_data->domain_name=='physiotherapist') {
                 $endpoint_secret = 'whsec_HJk1bGM8iFVhA1wgxWNDTVR48ci7AX0M';
             }elseif ($client_data->domain_name=='healthcare') {
                 $endpoint_secret = 'whsec_3uJMNSgeoxAqp1pAWRnso6rEPIJOAfzw';
             }elseif ($client_data->domain_name=='healtcaremydoctor') {
                 $endpoint_secret = 'whsec_vbLXJLy3LCozBrFlGzE21V0UllOS37hK';
             }elseif ($client_data->domain_name=='marketplace') {
                 $endpoint_secret = 'whsec_KHiPgoMR7bwdLP5OwtAiOv0NptGDXNvm';
             }elseif ($client_data->domain_name=='education') {
                 $endpoint_secret = 'whsec_dvkegoa1KMGWxUFy2xOdbbafPiAYGjNI';
             }elseif ($client_data->domain_name=='heal') {
                 $endpoint_secret = 'whsec_TL7Lw4FyvI4EiWFglaOyLtgqqQxAjO9j';
             }elseif ($client_data->domain_name=='intely') {
                 $endpoint_secret = 'whsec_6nk2aKGrbjqiIKRDWVFqukFHbCzKY3bg';
                 $keys = Helper::getClientFeatureKeys('Payment Gateway','Stripe');
                 if(isset($keys['STRIPE_MODE']) && $keys['STRIPE_MODE']=='test'){
                    $endpoint_secret = $keys['STRIPE_TEST_SIGNING_SECRET'];
                 }elseif (isset($keys['STRIPE_MODE']) && $keys['STRIPE_MODE']=='live') {
                    $endpoint_secret = $keys['STRIPE_LIVE_SIGNING_SECRET'];
                 }
             }elseif ($client_data->domain_name=='care_connect_live') {
                 $endpoint_secret = 'whsec_6nk2aKGrbjqiIKRDWVFqukFHbCzKY3bg';
             }elseif ($client_data->domain_name=='food') {
                 $endpoint_secret = 'whsec_Xxa4WTKXU54EjIYpT6y7Wd7hfWcb5tQB';
             }elseif ($client_data->domain_name=='homedoctor') {
                 $endpoint_secret = 'whsec_PR8x0NgywvGnI5tm5AH8lRMdoIIhLWdM';
             }elseif ($client_data->domain_name=='iedu') {
                 $endpoint_secret = 'whsec_CroTIQgj8iNMXhuvU4Qe1Roxsofh2Rl8';
             }elseif ($client_data->domain_name=='meetmd') {
                 $endpoint_secret = 'whsec_CsuLCx6R9QVlqNFJSPAimyO7kjCrXlxw';
                 // $keys = Helper::getClientFeatureKeys('Payment Gateway','Stripe');
                 // if(isset($keys['STRIPE_MODE']) && $keys['STRIPE_MODE']=='test'){
                 //    $endpoint_secret = $keys['STRIPE_TEST_SIGNING_SECRET'];
                 // }elseif (isset($keys['STRIPE_MODE']) && $keys['STRIPE_MODE']=='live') {
                 //    $endpoint_secret = $keys['STRIPE_LIVE_SIGNING_SECRET'];
                 // }
             }elseif ($client_data->domain_name=='airdoc') {
                 $endpoint_secret = 'whsec_8lk2Wm5XtRbShU5r3NcnT0WDnym8gxQW';
             }elseif ($client_data->domain_name=='taradoc') {
                 $endpoint_secret = 'whsec_fAIVR8SyJxVox5Dg8plVeuVbzaRW3RYk';
             }elseif ($client_data->domain_name=='clouddoc') {
                 $endpoint_secret = 'whsec_jj5mLuJAMycRquX5d68LoH9w0K27fi7E';
             }
        }
        // $transaction = Transaction::where([
        //         'transaction_id'=>'pi_1HdxXZG8MYa9zE4BWOdA7bhc',
        //         'payment_gateway'=>'stripe']
        //     )->first();
        // if($transaction->module_table=='direct'){
        //     $raw_details = json_decode($transaction->raw_details);
        //     $response = Helper::createRequest($raw_details);
        //     print_r($response);die;
        // }
        if(!isset($_SERVER['HTTP_STRIPE_SIGNATURE'])){
            http_response_code(400);
            exit();
        }

        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $event = null;
        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );
            // print_r($event);die;
        } catch(\UnexpectedValueException $e) {
            // Invalid payload
            http_response_code(400);
            exit();
        } catch(\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            http_response_code(400);
            exit();
        }

        if ($event->type == "payment_intent.succeeded") {
            $intent = $event->data->object;
            \Log::info("payment_intent.succeeded",["intent"=>$intent]);
            $transaction = Transaction::where([
                'transaction_id'=>$intent->id,
                'payment_gateway'=>'stripe']
            )->first();
            // print_r($transaction);die;
            if($transaction){
                if($transaction->status=="pending"  && $transaction->module_table=='packages'){
                    $userpackage  = UserPackage::firstOrCreate([
                        'user_id'=>$transaction->walletdata->user_id,
                        'package_id'=>$transaction->module_id
                    ]);
                    if($userpackage){
                        $package = Package::where('id',$transaction->module_id)->first();
                        $userpackage->increment('available_requests',$package->total_requests);
                        $transaction->status = 'success';
                        $transaction->save();
                    }
                    http_response_code(200);
                    exit();
                }

                if($transaction->status=="pending"  && $transaction->module_table=='request_creation'){
                    $response =  Helper::createRequest($transaction->raw_details,$transaction->id);
                    \Log::info("------------------payment_intent.request_creation-----------",["response"=>$response]);
                    http_response_code(200);
                    exit();
                }
                if($transaction->status=="success"){
                    http_response_code(200);
                    exit();
                }
                $transaction->walletdata->increment('balance',$transaction->amount);
                // if($transaction->module_table=='direct'){

                // }else{
                // }
                $transaction->status = 'success';
                $transaction->save();

                $notification = new Notification();
                $notification->push_notification(array($transaction->walletdata->user_id),array(
                    'pushType'=>'BALANCE_ADDED',
                    'transaction_id'=>$transaction->transaction_id,
                    'message'=>__($transaction->amount." amount added into your wallet")));
                \Log::info("payment_intent.succeeded.notification",["notification"=>$notification]);
            }
            http_response_code(200);
            exit();
        } elseif ($event->type == "payment_intent.payment_failed") {
            $intent = $event->data->object;
            \Log::info("payment_intent.payment_failed",["intent"=>$intent]);
            $transaction = Transaction::where([
                'transaction_id'=>$intent->id,
                'payment_gateway'=>'stripe']
            )->first();
            if($transaction){
                $transaction->status = 'failed';
                $transaction->save();

                $notification = new Notification();
                $notification->push_notification(array($transaction->walletdata->user_id),array(
                    'pushType'=>'BALANCE_FAILED',
                    'transaction_id'=>$transaction->transaction_id,
                    'message'=>__("Transaction Failed")));
            }
            http_response_code(200);
            exit();
        }
    }

    public function getHandleAlRajhiWebhook(Request $request){
            $keys = Helper::getClientFeatureKeys('Payment Gateway','Al Rajhi Bank');
            $input = $request->all();
            if(!$input){
                echo "transaction invalid";
                http_response_code(200);
                exit();
            }
            $tarndata = $input['trandata'];
            $data = [
                "textToDecrypt"=>$tarndata,
                "secretKey"=>$keys["secret_key"],
                "mode"=>$keys["mode"],
                "keySize"=>$keys["key_size"],
                "dataFormat"=>$keys["data_format"],
                "iv"=>$keys["iv"]
            ];
            $post_data = json_encode($data);
            $headers = array(
            'Content-type: application/json'
            ); 
            $curl = curl_init();
            curl_setopt_array($curl, array(
              CURLOPT_URL => "https://www.devglan.com/online-tools/aes-decryption",
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => "",
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 30,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => "POST",
              CURLOPT_POSTFIELDS => $post_data,
              CURLOPT_HTTPHEADER =>$headers,
            ));
            $response = curl_exec($curl);
            // print_r($response);die;
            $err = curl_error($curl);
            curl_close($curl);
            if ($err) {
                return [
                    'status'=>"error",
                    'statuscode' =>500,
                    'message' => $err,
                ];
            }
            $data = json_decode($response);
            $output = json_decode(urldecode(base64_decode($data->output)));
            if(isset($output[0]) && ($output[0]->authRespCode=='D' || $output[0]->result=='CAPTURED')){
                $transaction = Transaction::where([
                'transaction_id'=>$output[0]->paymentId,
                'payment_gateway'=>'al_rajhi_bank'])->first();
                if($transaction->status=="pending"  && $transaction->module_table=='request_creation'){
                    $response =  Helper::createRequest($transaction->raw_details,$transaction->id);
                    echo "Payment Success";
                    http_response_code(200);
                    exit();
                }
                if($transaction->status=="pending"  && $transaction->module_table=='extra_payment'){
                    $response =  Helper::extraPayment($transaction);
                    echo "Payment Success";
                    http_response_code(200);
                    exit();
                }
                if($transaction->status=="pending"  && $transaction->module_table=='add_money'){
                    $transaction->walletdata->increment('balance',$transaction->amount);
                    $transaction->status = 'success';
                    $transaction->save();

                    $notification = new Notification();
                    $notification->push_notification(array($transaction->walletdata->user_id),array(
                    'pushType'=>'BALANCE_ADDED',
                    'transaction_id'=>$transaction->transaction_id,
                    'message'=>__($transaction->amount." amount added into your wallet")));

                    echo "Payment Success";
                    http_response_code(200);
                    exit();
                }
            }
            echo "transaction invalid";
            http_response_code(200);
            exit();
        // print_r($request->all());die;
    }

    public function curlForHyper(){
        $client_data = Config::get("client_data");
        $url = "https://test.oppwa.com/".$request->resourcePath;
        $url .= "?entityId=$client_data->entity_id_visa_master";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                         'Authorization:Bearer '.$client_data->payment_access_token));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// this should be set to true in production
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $responseData = curl_exec($ch);
        curl_close($ch);
        $res = json_decode($responseData,true);
        return $res;
    }

    public function getHyperWebhook(Request $request){
      \Log::channel('custom')->info('sendNotification==========', ['getHyperWebhook' =>$request->all()]);
      $client_data = Config::get("client_data");
      $transaction_id = str_replace("/v1/checkouts/","",$request->resourcePath);
      $transaction_id = str_replace("/payment","",$transaction_id);
      $transaction = Transaction::where([
                'transaction_id'=>$transaction_id,
                'payment_gateway'=>'hyperpay'
      ])->first();
      $entity_id = $client_data->entity_id_visa_master;
      if($transaction && $transaction->status=="pending"  && $transaction->module_table=='add_money'){
          $raw_details = json_decode($transaction->raw_details);
          if(strtolower($raw_details->payment_method)=="mada"){
              $entity_id = $client_data->entity_id_mada;
          }
          $url = "$client_data->payment_bank_url".$request->resourcePath;
          $url .= "?entityId=$entity_id";
          $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, $url);
          curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                           'Authorization:Bearer '.$client_data->payment_access_token));
          curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
          curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// this should be set to true in production
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          $responseData = curl_exec($ch);
          if(curl_errno($ch)) {
            return curl_error($ch);
          }
          curl_close($ch);
          $res = json_decode($responseData,true);
          \Log::channel('custom')->info('responseData==========', ['responseData' =>$responseData]);
          if(($res['result']['code']=="000.100.110" || $res['result']['code']=="000.100.111" || $res['result']['code']=="000.100.112") && isset($res['ndc'])){
              if($transaction->status=="pending"  && $transaction->module_table=='add_money'){
                  $transaction->walletdata->increment('balance',$transaction->amount);
                  $transaction->status = 'success';
                  $transaction->save();

                  $notification = new Notification();
                  $notification->push_notification(array($transaction->walletdata->user_id),array(
                  'pushType'=>'BALANCE_ADDED',
                  'transaction_id'=>$transaction->transaction_id,
                  'message'=>__($transaction->amount." amount added into your wallet")));
                  return response([
                      'status' => "success",
                      'statuscode' => 200,
                      'message' =>__($transaction->amount." amount added into your wallet"),
                      'data'=>['transactionCompleted'=>true]
                  ], 200);
              }
          }elseif($res['result']['code']=="000.200.000"){
            return response([
                      'status' => "success",
                      'statuscode' => 400,
                      'message' =>$res['result']['description'],
                      'data'=>['transactionCompleted'=>false]
                  ], 400);
          }

      }

      return response([
          'status' => "success",
          'statuscode' => 400,
          'message' => __('Payment Processing Not Done'),
          'data'=>['transactionCompleted'=>false]
      ], 400);
    }

	/**
     * Handle a handlePayment Authorized.
     *
     * @param array $payload
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handlePaymentAuthorized(array $payload)
    {

        if(isset($payload['payload']['payment']['entity'])){
        	$razorpayPaymentId = $payload['payload']['payment']['entity']['id'];
        	$payment = $this->getPaymentEntity($razorpayPaymentId, $payload);
        	$success = false;
	        $errorMessage = 'The payment has failed.';
	        if ($payment['status'] === 'captured')
	        {
	            $success = true;
	        }else if ($payment['status'] === 'authorized'){
	        	$amount = $payment['amount']/100;
	        	$payment->capture(array('amount'=>$payment['amount']));
	        	$success = true;
	        	$transaction = Transaction::where('order_id',$payload['payload']['payment']['entity']['order_id'])->first();
	        	if($transaction && $transaction->status=="pending"){
		        	$transaction->amount = $amount;
		        	$transaction->walletdata->increment('balance',$amount);
		        	$transaction->status = 'success';
		        	$transaction->transaction_id = $razorpayPaymentId;
			        $transaction->closing_balance = $transaction->walletdata->balance;
			        $transaction->save();

                    $notification = new Notification();
                    $notification->push_notification(array($transaction->walletdata->user_id),array(
                    'pushType'=>'BALANCE_ADDED',
                    'transaction_id'=>$transaction->transaction_id,
                    'message'=>__($transaction->amount." amount added into your wallet")));
	        	}
	        }
	        return response(array('status' => "success", 'statuscode' => 200, 'message' =>__('handlePaymentAuthorized'),'data'=>['razorpayPaymentId'=>$razorpayPaymentId]), 200);
        }
    }

    /**
     * Handle a handlePayment Failed.
     *
     * @param array $payload
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handlePaymentFailed(array $payload)
    {

        if(isset($payload['payload']['payment']['entity'])){
        	$razorpayPaymentId = $payload['payload']['payment']['entity']['id'];
        	$payment = $this->getPaymentEntity($razorpayPaymentId, $payload);
        	$success = false;
	        $errorMessage = 'The payment has failed.';
	        if ($payment['status'] === 'captured')
	        {
	            $success = true;
	        }else if ($payment['status'] === 'failed'){
	        	$transaction = Transaction::where('order_id',$payload['payload']['payment']['entity']['order_id'])->first();
	        	if($transaction && $transaction->status=="pending"){
		        	$transaction->status = 'failed';
		        	$transaction->transaction_id = $razorpayPaymentId;
			        $transaction->save();

                    $notification = new Notification();
                    $notification->push_notification(array($transaction->walletdata->user_id),array(
                    'pushType'=>'BALANCE_FAILED',
                    'transaction_id'=>$transaction->transaction_id,
                    'message'=>__("Transaction Failed")));
	        	}
	        }
	        return response(array('status' => "success", 'statuscode' => 200, 'message' =>__('handlePaymentFailed'),'data'=>['razorpayPaymentId'=>$razorpayPaymentId]), 200);
        }
    }

    protected function getPaymentEntity($razorpayPaymentId, $data)
    {
       $payment = $this->razorpay->payment->fetch($razorpayPaymentId);
       return $payment;
    }

    /**
     * Returns the order amount, rounded as integer
     * @param WC_Order $order WooCommerce Order instance
     * @return int Order Amount
     */
    public function getOrderAmountAsInteger($order)
    {
        
        return (int) round($order->order_total * 100);
    }

	/**
     * Handle calls to missing methods on the controller.
     *
     * @param array $parameters
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function missingMethod($parameters = [])
    {
    	$log = array(
                'message'   => 'missingMethod',
                'data'      => 'Razor Pay',
            );
         \Log::info('Razor Pay '.json_encode($log));
        return response();
    }
}
