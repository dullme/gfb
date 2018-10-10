<?php

namespace App\Admin\Extensions\Tools;

use Encore\Admin\Admin;
use Encore\Admin\Grid\Tools\AbstractTool;
use Illuminate\Support\Facades\Request;

class UserTool extends AbstractTool {

    protected function script() {
        $url = Request::fullUrlWithQuery(['user' => '_user_']);

        return <<<EOT

        $('input:radio.user-type').change(function () {
        
            var url = "$url".replace('_user_', $(this).val());
        
            $.pjax({container:'#pjax-container', url: url });
        
        });

EOT;
    }

    public function render() {
        Admin::script($this->script());

        $options = [
            '2' => '已激活',
            '1' => '待激活',
            '0' => '待售',
            '3' => '已冻结',
        ];

        return view('admin.tools.userTool', compact('options'));
    }
}