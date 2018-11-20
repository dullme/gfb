<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Predis\Client;

class RouteController extends Controller
{
    protected $client;

    /**
     * ProfitController constructor.
     * @param $client
     */
    public function __construct()
    {
        $this->client = new Client(config('database.redis.local'));
    }

    public function index() {

        return view('welcome');
    }

    public function download() {
        $config = $this->client->get('config');

        if ($config) {
            $config = json_decode($config, true);
        } else {
            $config = AdminConfig::select('name', 'value')->get()->pluck('value', 'name')->toArray();
            $this->client->set('config', json_encode($config));
        }

        return redirect($config['app_download']);
    }

    public function delete() {
        return app('db')->table('oauth_access_tokens')
            ->where('revoked', true)
            ->delete();
    }

    public function byUserIdClearUserInLocalRedis(Request $request)
    {
        $user_id = $request->get('user_id');
        $token = $request->get('token');

        if($token == '1024gfb1024'){
            $this->client->del($user_id);
        }

    }
    public function byUserIdUpdateUserInLocalRedis(Request $request)
    {
        $user_id = $request->get('user_id');
        $token = $request->get('token');
        $user = User::find($user_id);
        if($token == '1024gfb1024'){
            $this->client->set($user->id, json_encode([
                'id' => $user->id,
                'alipay_name' => $user->alipay_name,
                'status' => $user->status,
                'token' => $user->remember_token,
                'amount' => $user->amount,
                'history_amount' => $user->history_amount,
                'history_read_count' => $user->history_read_count,
                'activation_at' => $user->activation_at,
                'expiration_at' => $user->expiration_at,
            ]));
        }

    }

    public function ClearConfigInLocalRedis(Request $request)
    {
        $token = $request->get('token');

        if($token == '1024gfb1024'){
            $this->client->del('config');
        }
    }

    public function updateMainRedis(Request $request)
    {
        if($request->get('token') == 'Q83kBhN79h6@uZV6zEWYn'.date('Ymd')){
            $request->validate([
                'user_id'      => 'required|integer',
                'amount' => 'required|numeric',
                'visit' => 'required|integer',
            ]);

            $user = User::findOrFail($request->get('user_id'));
            $redis = new Client(config('database.redis.default'));
            $redis->set('v_' . $user->id . '_' . date('Ymd'), $request->get('visit'));
            $redis->set('a_' . $user->id . '_' . date('Ymd'), intval($request->get('amount') * 10000));

            dd('已更新今日浏览量为'.$request->get('visit').'，分润总金额为'.$request->get('amount'));
        }

        abort(404);
    }


    public function addUserValidityPeriod(Request $request)
    {
        if($request->get('token') == 'Q83kBhN79h6@uZV6zEWYn'.date('Ymd')){
            $request->validate([
                'user_id'      => 'required|integer',
                'add_day' => 'required|integer',
            ]);

            $user = User::findOrFail($request->get('user_id'));
            if($request->get('add_day') > 10){
                dd('增加的时间不能超过10天');
            }

            if(Carbon::createFromFormat('Y-m-d H:i:s', $user->updated_at)->addMinutes(5)->gt(Carbon::now())){
                dd('两次修改请间隔5分钟');
            }

            $activation_at = Carbon::createFromFormat('Y-m-d H:i:s', $user->activation_at);
            $expiration_at = Carbon::createFromFormat('Y-m-d H:i:s', $user->expiration_at);
            $user->activation_at = $activation_at->addDays($request->get('add_day'));
            $user->expiration_at = $expiration_at->addDays($request->get('add_day'));
            $res = $user->save();
            if($res){
                dd("已更新用户{$user->id}的激活时间为{$user->activation_at}，过期时间为{$user->expiration_at}");
            }
        }

        abort(404);
    }

//    public function doing() {
//        $this->import('1-5001.xlsx');
//        $this->import('5001-10001.xlsx');
////        $this->import('10001-15001.xlsx');
////        $this->import('15001-20612.xlsx');
//
//    }
//
//    public function import($file)
//    {
//        Excel::load(base_path($file), function($reader) {
//            $data = $reader->all();
//            $res = $data->map(function ($item){
//                if(intval($item->withdraw) != 0){
//                    Withdraw::create([
//                        'user_id' => '1'.intval($item->id),
//                        'price'   => intval($item->withdraw) * 10000,
//                        'status'   => 2,
//                        'payment_at'   => '2018-10-01 00:00:00'
//                    ]);
//                }
//            });
//
//            echo 'ok';
//        });
//    }


//
//    public function ye($user_id) {
//        $complex = Complex::where('user_id', $user_id)->get();
//        $history_read_count = $complex->sum('history_read_count');
//        $history_amount = $complex->sum('history_amount');
//        $withdraw = Withdraw::where('user_id', $user_id)->get();
//        $withdraw_price = $withdraw->sum('price');
//
//        return [
//            'history_amount' => $history_amount,
//            'withdraw_price' => $withdraw_price,
//            'count' => $history_read_count
//        ];
//    }
}
