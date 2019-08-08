<?php

namespace App\Http\Controllers;

use App\Http\Proxy\TokenProxy;
use App\Http\Requests\LoginRequest;
use App\Models\AdminConfig;
use App\Models\Advertisement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Predis\Client;

class LoginController extends ResponseController
{

    protected $client;
    protected $redis;
    protected $proxy;

    /**
     * LoginController constructor.
     * @param $proxy
     */
    public function __construct(TokenProxy $proxy)
    {
        $this->proxy = $proxy;
        $this->client = new Client(config('database.redis.local'));
        $this->redis = new Client(config('database.redis.default'));
    }

    /**
     * 用户登录
     * @param LoginRequest $request
     * @return $this|\Illuminate\Http\JsonResponse
     */
    public function userLogin(LoginRequest $request)
    {
        $user = User::find($request->get('username'));

        if ($user) {
            if ($user->status == 0 || $user->status == 3) {
                return $this->responseError('该卡异常，请联系客服！');
            }

            if ($user->wrong_password >= 5 && Carbon::now()->lt($user->updated_at->addMinutes($user->wrong_password))) {
                return $this->responseError('连续输入错误过多，请' . $user->wrong_password . '分钟后重试');
            }

            if ($user->password == md5($request->get('password'))) {
                $token = makeInvitationCode(6);
                $user->update([
                    'remember_token' => $token,
                    'wrong_password' => 0
                ]);
                $redis = new Client(config('database.redis.local'));
                $redis->set($user->id, json_encode([
                    'id'                 => $user->id,
                    'alipay_name'        => $user->alipay_name,
                    'status'             => $user->status,
                    'token'              => $user->remember_token,
                    'amount'             => $user->amount,
                    'history_amount'     => $user->history_amount,
                    'history_read_count' => $user->history_read_count,
                    'activation_at'      => $user->activation_at,
                    'expiration_at'      => $user->expiration_at,
                ]));

                if ($user->status == 2 || $user->status == 3) {
                    $activated = true;
                } else {
                    $activated = false;
                }

                return $this->responseSuccess([
                    'activated' => $activated,
                    'token'     => json_encode([
                        'id'    => $user->id,
                        'token' => $token,
                    ])
                ]);
            } else {
                $user->increment('wrong_password');

                return $this->responseError('密码有误！');
            }
        } else {

            return $this->responseError('卡号有误！');
        }
    }

    /**
     * 用户注销
     * @param Request $request
     * @return int
     */
    public function userLogout(Request $request)
    {
        $res = str_replace('Bearer ', '', $request->header('Authorization'));
        $token = json_decode($res, true);
        $redis = new Client(config('database.redis.local'));
        $res = $redis->del($token['id']);

        if ($res) {
            User::where('id', $token['id'])->update(['remember_token' => null]);
        }

        return $this->responseSuccess(true);
    }

    /**
     * 重置 Token
     * @return mixed
     */
    public function refresh()
    {

        return $this->proxy->refresh();
    }

    /**
     * 淘宝任务
     */
    public function task()
    {
        $config = $this->client->get('config');

        if ($config) {
            $config = json_decode($config, true);
        } else {
            $config = AdminConfig::select('name', 'value')->get()->pluck('value', 'name')->toArray();
            $this->client->set('config', json_encode($config));
        }

        $advertisement = Cache::remember('advertisement', 60, function () {
            return Advertisement::where('status', 1)->select('img_uri', 'img')->get();
        });
        $res = $advertisement->random();

        $banner = explode(';', $config['banner']);

        $banner = collect($banner)->map(function ($item) {
            return [
                'img' => $item,
                'url' => null,
            ];
        });

        return $this->responseSuccess([
            'task'         => $config['task'], //任务地址
            'time'         => intval($config['ad_frequency']), //第一次请求任务的间隔时间
            'announcement' => $config['announcement'] == 'null' ? null : $config['announcement'], //公告
            'banner'       => $banner,
            'url'          => $res->img_uri ?: 'http://taofubao.oss-cn-beijing.aliyuncs.com/' . $res->img,
            'text'         => $config['first_task_text'],
        ]);
    }

    /**
     * 初始化接口
     * @return \Illuminate\Http\JsonResponse
     */
    public function systemInfo()
    {
        $config = $this->client->get('config');

        if ($config) {
            $config = json_decode($config, true);
        } else {
            $config = AdminConfig::select('name', 'value')->get()->pluck('value', 'name')->toArray();
            $this->client->set('config', json_encode($config));
        }

        return $this->responseSuccess([
            'coupon'        => $config['coupon'], //优惠券
            'version'       => $config['version'], //版本号
            'version_tips'  => $config['version_tips'], //版本提示
            'download'      => $config['app_download'], //新版本下载地址
            'agreement'     => $config['agreement'], //使用协议
            'strategy'      => $config['strategy'], //挣钱攻略
            'share'         => $config['share'], //分享
            'withdraw_info' => $config['withdraw_info'],
            'task_text'     => "每天早" . $config['ad_start_time'] . "到晚" . $config['ad_end_time'] . "限时开放", //任务内容
            'start_time'    => $config['ad_start_time'], //广告开始时间
            'end_time'      => $config['ad_end_time'], //广告结束时间
        ]);
    }
}
