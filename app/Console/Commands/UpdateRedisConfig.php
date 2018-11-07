<?php

namespace App\Console\Commands;

use App\Models\AdminConfig;
use Illuminate\Console\Command;
use Predis\Client;

class UpdateRedisConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'redis:config-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '更新本机Redis中的Config数据';

    protected $client;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->client = new Client(config('database.redis.local'));
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $config = AdminConfig::select('name', 'value')->get()->pluck('value', 'name')->toArray();
        $this->client->set('config', json_encode($config));
    }
}
