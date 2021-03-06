<?php

namespace App\Admin\Extensions\Tools;

use Encore\Admin\Grid\Tools\BatchAction;

class ChangeUserStatus extends BatchAction
{
    protected $action;

    public function __construct($action = 1)
    {
        $this->action = $action;
    }

    public function getActionName()
    {
        if($this->action == 1){
            return '出售';
        }elseif ($this->action == 2){
            return '解冻';
        }elseif ($this->action == 3){
            return '冻结';
        }else{
            return '未知';
        }
    }

    public function script()
    {
        $confirm = trans('admin.confirm');
        $cancel = trans('admin.cancel');
        $actionName = $this->getActionName();

        return <<<EOT

$('{$this->getElementClass()}').on('click', function() {

    var id = {$this->grid->getSelectedRowsName()}().join();

    swal({
        title: "确定设置为$actionName",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#DD6B55",
        confirmButtonText: "$confirm",
        showLoaderOnConfirm: true,
        cancelButtonText: "$cancel",
        preConfirm: function() {
            return new Promise(function(resolve) {
                $.ajax({
                    method: 'post',
                    url: '{$this->resource}/changeStatus',
                    data: {
                        _token:LA.token,
                        ids: selectedRows(),
                        action: {$this->action}
                    },
                    success: function (data) {
                        $.pjax.reload('#pjax-container');

                        resolve(data);
                    }
                });
            });
        }
    }).then(function(result) {
        var data = result.value;
        if (typeof data === 'object') {
            if (data.status) {
                swal(data.message, '', 'success');
            } else {
                swal(data.message, '', 'error');
            }
        }
    });
});

EOT;

    }
}
