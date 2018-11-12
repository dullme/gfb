<?php

namespace App\Http\Controllers;

use App\Models\Complex;
use App\Models\User;
use App\Models\Withdraw;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
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
