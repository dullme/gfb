<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CapitalPool;
use App\Models\User;
use App\Models\Withdraw;
use Carbon\Carbon;
use Cache;
use Encore\Admin\Layout\Content;
use Predis\Client;

class HomeController extends Controller {

    protected $redis;

    /**
     * RedisController constructor.
     * @param $redis
     */
    public function __construct() {
        $this->redis = new Client(config('database.redis.default'));
    }

    public function index(Content $content) {
        $withdraw =Cache::remember('withdraw', 60, function (){
            $withdraw = Withdraw::where('status', 2)->get();
            return (int)$withdraw->sum('price') / 10000;
        });
        $today_amount = $this->redis->get('tta') / 10000;
        $admin_users = Cache::remember('admin_users', 60, function () {
            $users = User::all();
            return [
                $users->where('status', 0)->count(),
                $users->where('status', 1)->count(),
                $users->where('status', 2)->count(),
                $users->where('status', 2)->where('activation_at', '>=', Carbon::today())->count(),
                $users->sum('history_amount')
            ];
        });

        $data = [
            ['name' => '待售', 'value' => $admin_users[0]],
            ['name' => '待激活', 'value' => $admin_users[1]],
            ['name' => '已激活', 'value' => $admin_users[2]],
            ['name' => '今日激活数', 'value' => $admin_users[3]],
            ['name' => '今日访问数', 'value' => $this->redis->get('ttv')],
            ['name' => '今日分润总额', 'value' => $today_amount],
            ['name' => '分润总额', 'value' => ($admin_users[4] / 10000 + $today_amount)],
            ['name' => '提现总额', 'value' => $withdraw]
        ];

        return $content
            ->header('首页')
            ->description(' ')
            ->row(view('admin.home', compact('data')));
    }
}
