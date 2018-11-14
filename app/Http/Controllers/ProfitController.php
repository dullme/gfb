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
                if($ready['status']){
                    return $this->responseSuccess($ready);
                }else{
                    return $this->responseError($ready['message']);
                }
            } else {
                $auth_user = $this->authUser($token['id'], $token['token']);
                if ($auth_user) { //认证成功
                    $ready = $this->ready($auth_user, $request);
                    if($ready['status']){
                        return $this->responseSuccess($ready);
                    }else{
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
                'status' => false,
                'message' => '请重新登陆'
            ];
        }
        $carbon_now = Carbon::now();
        if ($carbon_now >= $user['expiration_at']) {
            return [
                'status' => false,
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

        $ad_start_time = Carbon::createFromTimeString($config['ad_start_time']);
        $ad_end_time = Carbon::createFromTimeString($config['ad_end_time']);
        if ($carbon_now->gt($ad_end_time) || $carbon_now->lt($ad_start_time)) {
            if($request->headers->get('user-agent') == 'okhttp/3.8.0'){
                return [
                    'status'      => true,
                    'last_amount' => 0,
                    'url'         => '',
                    'time'        => $config['ad_frequency'],
                    'text'        => $config['announcement'] != 'null' ? $config['announcement'] : null,
                ];
            }

            return [
                'status' => false,
                'message' => '广告开始结束时间为' . $config['ad_start_time'] . '-' . $config['ad_end_time']
            ];
        }

        $visit = $this->redis->get('v_' . $user['id'] . '_' . date('Ymd')) ?: 0;

        if ($visit >= $config['max_visits']) {
            return [
                'status' => false,
                'message' => '今日访问已达上限'
            ];
        }

        $my_amount = $this->getLast_amount($config);

        $this->redis->incrby('v_' . $user['id'] . '_' . date('Ymd'), 1);
        $this->redis->incrby('a_' . $user['id'] . '_' . date('Ymd'), $my_amount);

        $advertisement = Cache::rememberForever('advertisement', function () {
            return Advertisement::where('status', 1)->select('img_uri', 'img')->get();
        });

        $res = $advertisement->random();

        return [
            'status'      => true,
            'last_amount' => $my_amount / 10000,
            'url'         => $res->img_uri ?: 'http://guafen.oss-cn-beijing.aliyuncs.com/'.$res->img,
            'time'        => $config['ad_frequency'],
            'text'        => $config['announcement'] != 'null' ? $config['announcement'] : null,
        ];

    }

    /**
     * 获取分润
     * @param $config
     * @return int
     */
    public function getLast_amount($config)
    {
        $my_amount = round($config['daily_ad_revenue'] / $config['max_visits'], 4);

        return (int) (round($my_amount + randFloat(0.0001, 0.003), 4) * 10000);
    }
}
