<?php

namespace App\Admin\Extensions\Tools;

use Encore\Admin\Admin;
use Encore\Admin\Grid\Tools\AbstractTool;
use Illuminate\Support\Facades\Request;

class WithdrawTool extends AbstractTool {

    protected function script() {
        $url = Request::fullUrlWithQuery(['withdraw' => '_withdraw_']);

        return <<<EOT

        $('input:radio.withdraw-type').change(function () {
        
            var url = "$url".replace('_withdraw_', $(this).val());
        
            $.pjax({container:'#pjax-container', url: url });
        
        });

EOT;
    }

    public function render() {
        Admin::script($this->script());

        $options = [
            'search' => '套现查询',
            'to_be_confirmed' => '未导出',
            'be_confirmed' => '已导出',
            'confirmed' => '已确认',
        ];

        return view('admin.tools.withdrawTool', compact('options'));
    }
}