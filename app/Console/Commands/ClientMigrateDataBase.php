<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\Artisan;
use Config;
use App\Model\Client;
use Exception;
class ClientMigrateDataBase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'client:migrate';

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

        // $chiper = 'AES-256-CBC';
        //         $options = 0;
        //         $encryption_iv = '1234567891011121';
        //         $encryption_key = "consultappclient";
        //         $password=openssl_decrypt('Fpf9YcdB3rGl3wh0Y6aPpA==',$chiper,$encryption_key,$options,$encryption_iv);

        //         print_r($password);die;
        \DB::connection('godpanel')->table('clients')->orderBy('id','ASC')->where(['client_status'=>'inprogress'])
        ->chunk(5, function($clients) {
            foreach ($clients as $key=>$client) {
                if($client->client_status!=='inprogress'){
                    continue;
                }

                \DB::connection('godpanel')->table('clients')
                ->where('id',$client->id)
                ->update(['client_status'=>'migrating']);
                
                $database_name = 'db_'.$client->domain_name;
                \DB::statement("create database $database_name;");
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
                Config::set("database.connections.$database_name", $default);
                Artisan::call('migrate', ['--database' => $database_name]);
                Artisan::call('passport:install');
                /* User Admin */
                /*End User Admin */
                Artisan::call('db:seed', ['--database' => $database_name]);
                Artisan::call('db:seed', ['--database' => $database_name,'--class' => 'ServiceTypeSeeder']);
                $chiper = 'AES-256-CBC';
                $options = 0;
                $encryption_iv = '1234567891011121';
                $encryption_key = "consultappclient";
                $password=openssl_decrypt($client->password,$chiper,$encryption_key,$options,$encryption_iv);
                $user = \App\User::firstOrCreate(['email'=>$client->email]);
                $user->password = bcrypt($password);
                $user->name = 'Admin';
                if($user->save()){
                    $role = \App\Model\Role::where('name','admin')->first();
                    if(!$user->hasRole('admin')){
                        $user->roles()->attach($role);
                    }
                }
                // \DB::disconnect($database_name);
                // \DB::reconnect('godpanel');
                \DB::connection('godpanel')->table('clients')
                ->where('id',$client->id)
                ->update([
                    'client_status'=>'completed',
                    'client_key'=>bin2hex(random_bytes(16)).$client->id,
                    'client_secret'=>bin2hex(random_bytes(32)).$client->id,
                ]);
            }
        });
    }
}
