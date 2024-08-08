<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\Artisan;
use Config;
use App\Model\Client;
use Exception;
class ClientMigrationUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clientmigration:update';

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
        $clients = \DB::connection('godpanel')->table('clients')->orderBy('id','ASC')->get();
        foreach ($clients as $key=>$client) {
            try{
                $this->info('db updating '.$client->name);
                $database_name = 'db_'.$client->domain_name;
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
                Artisan::call('db:seed', ['--database' => $database_name,'--class' => 'RoleTableSeeder']);
                Artisan::call('db:seed', ['--database' => $database_name,'--class' => 'ServiceTypeSeeder']);
            }catch(Exception $ex){
                $this->info($ex->getMessage());
                continue;
            }
        }
    }
}
