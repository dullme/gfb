<?php
/**
 * Created by PhpStorm.
 * User: jinjialei
 * Date: 2017/11/3
 * Time: 上午10:45
 */

namespace App\Http\Proxy;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class TokenProxy {

    protected $http;

    /**
     * TokenProxy constructor.
     * @param $http
     */
    public function __construct(Client $http) {
        $this->http = $http;
    }

    public function login($mobile, $password) {

        return $this->proxy('password', [
            'username' => $mobile,
            'password' => $password,
            'scope'    => '',
        ]);
    }

    public function refresh() {
        $refreshToken = request()->cookie('refreshToken');

        return $this->proxy('refresh_token', [
            'refresh_token' => $refreshToken
        ]);
    }

    public function logout() {
        $user = auth()->guard('api')->user();
        if(!is_null($user)){
            $accessToken = $user->token();
            app('db')->table('oauth_refresh_tokens')
                ->where('access_token_id', $accessToken->id)
                ->update([
                    'revoked' => true,
                ]);
            $accessToken->revoke();
        }
        app('cookie')->queue(app('cookie')->forget('refreshToken'));

        return response()->json([
            'message' => 'Logout!'
        ]);
    }

    public function logoutOthers($user_id) {
        app('db')->table('oauth_access_tokens')
            ->where('user_id', $user_id)
            ->delete();
    }

    public function proxy($grantType, array $data = []) {
        $data = array_merge($data, [
            'client_id'     => config('passport.client_id'),
            'client_secret' => config('passport.client_secret'),
            'grant_type'    => $grantType
        ]);

        try {
            $response = $this->http->post(url('oauth/token'), [
                'form_params' => $data
            ]);
        } catch (RequestException $e) {
            return [
                'error' => $e->getMessage(),
                'message' => '用户名或密码错误!'
            ];
        }

        $token = json_decode((string) $response->getBody(), true);

        return [
            'token'      => $token['access_token'],
            'auth_id'      => md5($token['refresh_token']),
            'expires_in' => $token['expires_in'],
        ];
    }
}