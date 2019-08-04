<?php

namespace App\Http\Controllers;

use App\Http\Proxy\TokenProxy;
use App\Http\Requests\LoginRequest;
use App\Models\AdminConfig;
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

        return $this->responseSuccess([
            'task'         => 'https://taobao.com', //任务地址
            'time'         => $config['ad_frequency'], //第一次请求任务的间隔时间
            'announcement' => $config['announcement'] == 'null' ? null : $config['announcement'], //公告
            'banner'       => [ //轮播图
                [
                    'img' => 'http://guafen.oss-cn-beijing.aliyuncs.com/images/11.jpg',
                    'url' => 'https://www.baidu.com'
                ],
                [
                    'img' => 'http://guafen.oss-cn-beijing.aliyuncs.com/images/10.jpg',
                    'url' => null
                ],
            ]
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
            'Coupon'        => 'https://www.baidu.com', //优惠券
            'version'       => '1.1.1', //版本号
            'version_tips'  => '新版本上线啦！', //版本号
            'download'      => 'https://guafen.oss-cn-beijing.aliyuncs.com/guafen.apk', //新版本下载地址
            'agreement'     => 'https://www.baidu.com', //使用协议
            'strategy'      => 'https://www.baidu.com', //挣钱攻略
            'share'         => 'https://www.baidu.com', //分享
            'withdraw_info' => '1、单次提现金额为100元的整数倍，如100，200;2、提现申请后，T+1个工作日内提现到注册时提供的支付宝账户;3、100积分可以折算成1元，5000积分=50元，以此类推;4、什么乱七八糟的随便写了一些东西',
            'task_text'    => "每天早" . $config['ad_start_time'] . "到晚" . $config['ad_end_time'] . "限时开放", //任务内容
            'start_time'    => $config['ad_start_time'], //广告开始时间
            'end_time'    => $config['ad_end_time'], //广告结束时间
        ]);
    }
}
