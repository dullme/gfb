<?php

namespace App\Http\Controllers;

use App\Models\AdminConfig;
use App\Models\Advertisement;
use Cache;
use Storage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Predis\Client;

class ProfitController extends ResponseController
{

    protected $client;
    protected $redis;

    /**
     * ProfitController constructor.
     * @param $client
     */
    public function __construct()
    {
        $this->client = new Client(config('database.redis.local'));
        $this->redis = new Client(config('database.redis.default'));
    }

    public function getImage(Request $request)
    {
        $res = str_replace('Bearer ', '', $request->header('Authorization'));
        $token = json_decode($res, true);
        if ($token['id']) {
            $user = $this->getUserFromRedis($token['id']);
            if ($user && $user['token'] == $token['token']) {  //认证成功
                $ready = $this->ready($user, $request);
                if ($ready['status']) {
                    return $this->responseSuccess($ready);
                } else {
                    return $this->responseError($ready['message']);
                }
            } else {
                $auth_user = $this->authUser($token['id'], $token['token']);
                if ($auth_user) { //认证成功
                    $ready = $this->ready($auth_user, $request);
                    if ($ready['status']) {
                        return $this->responseSuccess($ready);
                    } else {
                        return $this->responseError($ready['message']);
                    }
                }
            }

        }

        return $this->responseError('请重新登录');
    }

    public function getUserFromRedis($user_id)
    {
        $user = $this->client->get($user_id);
        if ($user) {
            return json_decode($user, true);
        }

        return false;
    }

    public function authUser($user_id, $token)
    {
        $user = User::find($user_id);
        if ($user && $token == $user->remember_token) {
            $auth_user = [
                'id'                 => $user->id,
                'alipay_name'        => $user->alipay_name,
                'status'             => $user->status,
                'token'              => $user->remember_token,
                'amount'             => $user->amount,
                'history_amount'     => $user->history_amount,
                'history_read_count' => $user->history_read_count,
                'activation_at'      => $user->activation_at,
                'expiration_at'      => $user->expiration_at,
            ];
            $this->client->set($user->id, json_encode($auth_user));

            return $auth_user;
        }

        return false;
    }

    public function ready($user, $request)
    {
        if ($user['status'] == 1 || $user['status'] == 3) {
            return [
                'status'  => false,
                'message' => '请重新登陆'
            ];
        }
        $carbon_now = Carbon::now();
        if ($carbon_now >= $user['expiration_at']) {
            return [
                'status'  => false,
                'message' => '该卡已过期'
            ];
        }

        $config = $this->client->get('config');

        if ($config) {
            $config = json_decode($config, true);
        } else {
            $config = AdminConfig::select('name', 'value')->get()->pluck('value', 'name')->toArray();
            $this->client->set('config', json_encode($config));
        }


        if (isset($config['maintenance']) && $config['maintenance'] != 'null') {
            return [
                'status'  => false,
                'message' => $config['maintenance']
            ];
        }


        $ad_start_time = Carbon::createFromTimeString($config['ad_start_time']);
        $ad_end_time = Carbon::createFromTimeString($config['ad_end_time']);
        if ($carbon_now->gt($ad_end_time) || $carbon_now->lt($ad_start_time)) {
            if ($request->headers->get('user-agent') == 'okhttp/3.8.0') {
                if ($carbon_now->gt($ad_start_time)) {
                    $seconds = $carbon_now->diffInSeconds($ad_start_time->addDay());
                } else {
                    $seconds = $carbon_now->diffInSeconds($ad_start_time);
                }

                return [
                    'status'      => true,
                    'last_amount' => 0,
                    'time'        => $seconds,
                    'url'         => ''
                ];
            }

            return [
                'status'  => false,
                'message' => '广告开始结束时间为' . $config['ad_start_time'] . '-' . $config['ad_end_time']
            ];
        }

        $visit = $this->redis->get('v_' . $user['id'] . '_' . date('Ymd')) ?: 0;

        if ($visit >= $config['max_visits']) {
            if ($carbon_now->addMinutes(30)->toDateTimeString() <= $ad_end_time) {
                return [
                    'status'  => false,
                    'message' => '系统检测到您的请求异常，如继续使用非法手段刷新可能面临封号风险！'
                ];
            } else {
                return [
                    'status'  => false,
                    'message' => '今日访问已达上限'
                ];
            }
        }
        $my_amount = $this->getLast_amount($config, $user['expiration_at']);


        if (!$this->canSee($user['id'])) {
            return [
                'status'  => false,
                'message' => '请求频繁'
            ];
        }

        $advertisement = Cache::remember('advertisement', 60, function () {
            return Advertisement::where('status', 1)->select('img_uri', 'img')->get();
        });
        $res = $advertisement->random();

        $this->redis->incrby('v_' . $user['id'] . '_' . date('Ymd'), 1);
        $this->redis->incrby('a_' . $user['id'] . '_' . date('Ymd'), $my_amount);
        $this->redis->set('see_' . $user['id'], Carbon::now()->addSeconds($config['ad_frequency']));

        $my_amount = $my_amount / 100;

        return [
            'status'      => true,
            'url'         => $res->img_uri ?: 'http://taofubao.oss-cn-beijing.aliyuncs.com/' . $res->img,
            'type'        => 'image',
            'text'        => $config['second_task_text'],
            'last_amount' => "已增加{$my_amount}积分",
            'time'        => intval($config['ad_frequency']),
        ];

    }

    /**
     * 获取分润
     * @param $config
     * @param $expiration_at
     * @return int
     */
    public function getLast_amount($config, $expiration_at)
    {

        return (int) (round(intval($config['single_point']) / $config['redeem'], 4) * 10000);

//        $my_amount = round($config['daily_ad_revenue'] / $config['max_visits'], 4);

//        if($config['expiration_days'] != 'null'){
//            $expiration_at = Carbon::createFromFormat('Y-m-d H:i:s', $expiration_at)->subDays($config['expiration_days']);
//            if($expiration_at->lt(Carbon::now())){
//                $my_amount = round($config['daily_ad_revenue_by_expiration_days'] / $config['max_visits'], 4);
//            }
//        }

//        return (int) (round($my_amount + randFloat(0.0001, 0.003), 4) * 10000);
    }

    public function canSee($user_id)
    {
        $last_time_see_ad = $this->redis->get('see_' . $user_id);
        if (!is_null($last_time_see_ad)) {
            Carbon::now();
            $last_time_see_ad = Carbon::createFromFormat('Y-m-d H:i:s', $last_time_see_ad);

            return Carbon::now()->gt($last_time_see_ad) ?: false;
        }

        return true;
    }
}
