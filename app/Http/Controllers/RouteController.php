<?php

namespace App\Http\Controllers;

use App\Models\Complex;
use App\Models\User;
use App\Models\Withdraw;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class RouteController extends Controller
{

    public function index() {

        return view('welcome');
    }

    public function download() {

        return redirect(config('app_download'));
    }

    public function delete() {
        return app('db')->table('oauth_access_tokens')
            ->where('revoked', true)
            ->delete();
    }

    public function doing() {
        $this->import('1-5001.xlsx');
        $this->import('5001-10001.xlsx');
        $this->import('10001-15001.xlsx');
        $this->import('15001-20612.xlsx');

    }

    public function import($file)
    {
        Excel::load(base_path($file), function($reader) {
            $data = $reader->all();
            $res = $data->map(function ($item){
                if(strpos($item->alipay_account,'@') !== false){
                    $alipay_account = $item->alipay_account;
                }else{
                    $alipay_account = intval($item->alipay_account);
                }

                if(intval($item->status) == 0){
                    $activation_at = null;
                    $expiration_at = optional($item->expiration_at)->toDateTimeString();
                }else{
                    $activation_at = optional($item->activation_at)->toDateTimeString();
                    $expiration_at = optional($item->expiration_at)->addDay()->toDateTimeString();
                }

                return [
                    'id' => intval('1'.intval($item->id)),
                    'original_price' => 1200,
                    'retail_price' => 1200,
                    'mobile' => intval($item->mobile),
                    'alipay_account' => $alipay_account,
                    'alipay_name' => $item->alipay_name,
                    'status' => intval($item->status) == 0 ? 0 : 2,
                    'password' => md5(intval($item->password)),
                    'validity_period' => intval($item->validity_period),
                    'initial_password' => intval($item->password),
                    'activation_at' => $activation_at,
                    'expiration_at' => $expiration_at,
                    'created_at' => optional($item->activation_at)->toDateTimeString(),
                    'amount' => intval($item->amount * 10000),
                    'history_amount' => intval($item->history_amount * 10000),
                ];
            });
            $ress = $res->split(2);

            User::insert($ress->first()->toArray());
            User::insert($ress->last()->toArray());
            echo 'ok';
        });
    }


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
