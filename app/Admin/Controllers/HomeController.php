<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Encore\Admin\Controllers\Dashboard;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Predis\Client;

class HomeController extends Controller {

    public function index(Content $content) {
        $amount = getTodayAmount();
        $users = User::all();
        $data = [
            ['name' => '待售', 'value' => $users->where('status', 0)->count()],
            ['name' => '待激活', 'value' => $users->where('status', 1)->count()],
            ['name' => '已激活', 'value' => $users->where('status', 2)->count()],
            ['name' => '今日访问数', 'value' => $amount['visits']],
            ['name' => '分润总额', 'value' => $amount['amount']],
            ['name' => '提现总额', 'value' => $amount['withdraw']]
        ];

        return $content
            ->header('首页')
            ->description(' ')
            ->row(view('admin.home', compact('data')));
    }
}
