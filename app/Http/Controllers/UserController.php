<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use App\Models\Complex;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Predis\Client;

class UserController extends ResponseController {

    public function updateUserInfo(Request $request) {
        $request->validate([
            'mobile'         => 'required|mobile',
            'alipay_account' => 'required',
            'alipay_name'    => 'required',
        ]);

        $user = User::find(Auth()->user()->id);

        if ($user->status == 2) {
            return $this->responseError('该卡已激活无需重新激活！');
        }

        $user->mobile = $request->get('mobile');
        $user->alipay_account = $request->get('alipay_account');
        $user->alipay_name = $request->get('alipay_name');
        $user->status = 2;
        $user->activation_at = Carbon::now();
        $user->save();
        if (!$user->save()) {

            return $this->responseError('激活失败');
        }

        return $this->responseSuccess(true);
    }

    public function userInfo() {
        $user = User::find(Auth()->user()->id);

        if (!$user) {
            return $this->responseError('用户信息获取失败');
        }

        if ($user->status != 2) {
            return $this->responseError('该卡尚未激活！');
        }

        return $this->responseSuccess([
            'username'       => $user->id,
            'mobile'         => $user->mobile,
            'alipay_account' => $user->alipay_account,
            'alipay_name'    => $user->alipay_name,
            'activation_at'  => $user->activation_at,
        ]);
    }

    /**
     * 分润记录
     */
    public function complex() {
        $complex = Complex::where('user_id', auth()->user()->id)->get();
        $redis = new Client(config('database.redis.default'));
        $today_amount = $redis->get('a_' . auth()->user()->id . '_' . date('Ymd'));
        $visits = $redis->get('v_' . auth()->user()->id . '_' . date('Ymd'));

        return $this->responseSuccess([
            'today_amount'       => $today_amount ?: 0,
            'visits'             => $visits ?: 0,
            'history_read_count' => $complex->sum('history_read_count'),
            'history_amount'     => $complex->sum('history_amount'),
        ]);
    }

    public function getImage() {
        $advertisement = Advertisement::where('status', 1)->get();

        $res = $advertisement->random();

        return $this->responseSuccess([
            'last_amount' => 0.122,
            'url' => $res->img_uri ? : url('storage/'.$res->img),
            'time' => config('ad_frequency')
        ]);
    }
}
