<?php

namespace App\Http\Controllers;

use App\Models\Complex;
use App\Models\User;
use App\Models\Withdraw;
use Illuminate\Http\Request;

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
        $users = User::where('status', 0)->delete();
        echo $users;

        $users =  User::all();
        return $users->map(function ($user){
            $ye = $this->ye($user->id);
            $user->amount = $ye['history_amount'] - $ye['withdraw_price'];
            $user->history_amount = $ye['history_amount'];
            $user->history_read_count = $ye['count'];
            return $user->save();
        });
    }

    public function ye($user_id) {
        $complex = Complex::where('user_id', $user_id)->get();
        $history_read_count = $complex->sum('history_read_count');
        $history_amount = $complex->sum('history_amount');
        $withdraw = Withdraw::where('user_id', $user_id)->get();
        $withdraw_price = $withdraw->sum('price');

        return [
            'history_amount' => $history_amount,
            'withdraw_price' => $withdraw_price,
            'count' => $history_read_count
        ];
    }
}
