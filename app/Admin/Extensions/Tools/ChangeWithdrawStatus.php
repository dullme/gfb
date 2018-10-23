<?php

namespace App\Admin\Extensions\Tools;

use Encore\Admin\Grid\Tools\BatchAction;

class ChangeWithdrawStatus extends BatchAction
{
    protected $action;

    public function __construct($action = 2)
    {
        $this->action = $action;
    }

    public function script()
    {
        $confirm = trans('admin.confirm');
        $cancel = trans('admin.cancel');

        return <<<EOT

$('{$this->getElementClass()}').on('click', function() {

    var id = {$this->grid->getSelectedRowsName()}().join();

    swal({
        title: "确认提现",
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