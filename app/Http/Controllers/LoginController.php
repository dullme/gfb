<?php

namespace App\Http\Controllers;

use App\Http\Proxy\TokenProxy;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends ResponseController
{
    protected $proxy;

    /**
     * LoginController constructor.
     * @param $proxy
     */
    public function __construct(TokenProxy $proxy) {
        $this->proxy = $proxy;
    }

    /**
     * 用户登录
     * @param LoginRequest $request
     * @return $this|\Illuminate\Http\JsonResponse
     */
    public function userLogin(LoginRequest $request) {
        $user = User::find($request->get('username'));
        if($user && $user->password == md5($request->get('password'))){
            $proxy = $this->proxy->login($request->get('username'),$request->get('password'));
        }else{

            return $this->responseError('卡密有误！');
        }

        Auth::logoutOtherDevices($request->get('password'));

        return $this->responseSuccess(array_merge([
            'activated' => $user->status == 2 ? true : false
        ], $proxy));
    }

    /**
     * 用户注销
     * @return \Illuminate\Http\JsonResponse
     */
    public function userLogout() {

        return $this->proxy->logout();
    }

    /**
     * 重置 Token
     * @return mixed
     */
    public function refresh() {

        return $this->proxy->refresh();
    }
}
