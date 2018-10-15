<?php

namespace App\Admin\Extensions;

use Carbon\Carbon;
use Encore\Admin\Grid\Exporters\AbstractExporter;
use Maatwebsite\Excel\Facades\Excel;

class ExcelExpoter extends AbstractExporter
{
    public function export()
    {
        Excel::create(Carbon::now(), function($excel) {

            $excel->sheet('Sheetname', function($sheet) {

                $head = ['账户', '支付宝账号', '金额', '申请时间'];
                // 这段逻辑是从表格数据中取出需要导出的字段
                $bodyRows = collect($this->getData())->map(function ($item) {

                    return [
                        'id' => $item['user']['id'],
                        'alipay_account' => $item['user']['alipay_account'],
                        'price' => $item['price'],
                        'created_at' => $item['created_at'],
                    ];
                });
                $rows = collect([$head])->merge($bodyRows);
                $sheet->rows($rows);

            });

        })->export('xls');
    }
}