<?php

namespace App\Http\Controllers;

use App\Http\Proxy\TokenProxy;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;

class LoginController extends Controller
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

        return $this->proxy->login($request->get('username'),$request->get('password'));
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
