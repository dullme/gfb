<?php

namespace App\Console\Commands;

use App\Models\AdminConfig;
use Illuminate\Console\Command;
use Predis\Client;

class updateTodayAmount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'redis:update-today-amount';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '更新今日分润数据';

    protected $redis;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->redis = new Client(config('database.redis.default'));
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->redis->set('tta', $this->todayTotalAmount());
        $this->redis->set('ttv', $this->todayTotalVisit());
    }

    /**
     * 获取今日总分润
     * @return float|int
     */
    public function todayTotalAmount() {
        $a_amount = $this->redis->keys('a_*_' . date('Ymd'));

        return $a_amount ? collect($this->redis->mget($a_amount))->sum() : 0;
    }

    /**
     * 获取今日总访问量
     * @return int|mixed
     */
    public function todayTotalVisit() {
        $v_amount = $this->redis->keys('v_*_' . date('Ymd'));

        return $v_amount ? collect($this->redis->mget($v_amount))->sum() : 0;
    }
}
