<?php

namespace App\Console\Commands;

use App\Models\Complex;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Predis\Client;

class StoreRedis extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'redis:store';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '缓存除今天外的数据';

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
        $data = $this->getRedisData();  //获取Redis数据
        if($data){
            if($this->needStore()){ //是否需要保存到数据库
                $this->storeComplex($data['create']);   //保存数据到数据库
            }
            $this->clearRedis(array_merge($data['amount'], $data['visit'])); //清除历史Redis
        }

    }

    /**
     * 删除 redis 数据
     * @param array $keys
     */
    public function clearRedis(array $keys) {
        $this->redis->del($keys);
    }

    /**
     * 保存到数据库
     * @param array $data
     */
    public function storeComplex(array $data) {
        Complex::insert($data);
    }

    /**
     * 是否需要保存
     * @return bool
     */
    public function needStore() {
        $last_complex = Complex::orderBy('id', 'desc')->first();

        if(!is_null($last_complex) && $last_complex->created_at->isYesterday()){
            return false;
        }

        return true;
    }

    /**
     * 获取 redis 数据
     * @return array
     */
    public function getRedisData() {
        $keys = $this->redis->keys('a_*');
        $data = [];
        foreach ($keys as $key){
            if(!!!strpos($key, date('Ymd'))){
                $data['amount'][] =$key;
            }
        }

        if($data){
            $data['visit'] = str_replace('a', 'v', $data['amount']);
            $data['amount_value'] = $this->redis->mget($data['amount']);
            $data['visit_value'] = $this->redis->mget($data['visit']);
            foreach ($data['amount'] as $key=>$item){
                $date = Carbon::createFromFormat('YmdH:i:s', substr($item, strripos($item,'_') + 1).'00:00:00');
                $data['create'][] = [
                    'user_id' => cut('_', '_', $item),
                    'history_read_count' => $data['visit_value'][$key],
                    'history_amount' => $data['amount_value'][$key],
                    'created_at' => $date,
                    'updated_at' => Carbon::now(),
                ];
            }
        }

        return $data;
    }
}
