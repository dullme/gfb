<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class UserController extends ResponseController
{

    public function updateUserInfo(Request $request) {
        $request->validate([
            'mobile' => 'required|mobile',
            'alipay_account' => 'required',
            'alipay_name' => 'required',
        ]);

        $user = User::find(Auth()->user()->id);

        if($user->status == 2){
            return $this->responseError('该卡已激活无需重新激活！');
        }

        $user->mobile = $request->get('mobile');
        $user->alipay_account = $request->get('alipay_account');
        $user->alipay_name = $request->get('alipay_name');
        $user->status = 2;
        $user->activation_at = Carbon::now();
        $user->save();
        if(!$user->save()){

            return $this->responseError('激活失败');
        }

        return $this->responseSuccess(true);
    }

    public function userInfo() {
        $user = User::find(Auth()->user()->id);

        if(!$user){
            return $this->responseError('用户信息获取失败');
        }

        if($user->status != 2 ){
            return $this->responseError('该卡尚未激活！');
        }

        return $this->responseSuccess([
            'username' => $user->id,
            'mobile' => $user->mobile,
            'alipay_account' => $user->alipay_account,
            'alipay_name' => $user->alipay_name,
            'activation_at' => $user->activation_at,
        ]);
    }
}
