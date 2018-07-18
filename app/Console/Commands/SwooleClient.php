<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use swoole_client;
use App\Http\Controllers\BattleController;
use Log;
class SwooleClient extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swoole:client';

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
        $client = new swoole_client(SWOOLE_SOCK_UDP);
        $client->connect('127.0.0.1', 6380, 1);

        $test=swoole_timer_tick(300, function ($id) use ($client){
         $client->send("test\n");
           $message = $client->recv();
            echo "Get Message From Server:{$message}\n";

});

         swoole_timer_after(14000, function () use($client,$test){
        swoole_timer_clear($test);
         echo "client close";
         $client->close();
        });                                       

    }

}
