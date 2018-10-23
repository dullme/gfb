<?php

namespace App\Admin\Extensions;

use Carbon\Carbon;
use Encore\Admin\Grid\Exporters\AbstractExporter;
use Maatwebsite\Excel\Facades\Excel;

class ExcelExpoter extends AbstractExporter
{
    protected $head = [];
    protected $body = [];
    protected $display = [];

    public function setAttr($head, $body, $display = []) {
        $this->head = $head;
        $this->body = $body;
        $this->display = $display;
    }

    public function export() {
        Excel::create(Carbon::now(), function ($excel) {

            $excel->sheet('Sheetname', function ($sheet) {

                $head = $this->head;
                $body = $this->body;
                $bodyRows = collect($this->getData())->map(function ($item) use ($body) {
                    foreach ($body as $keyName) {
                        $functionName = $this->getKeyName($keyName);
                        if (in_array($functionName, $this->display)) {
                            $arr[] = $this->$functionName(array_get($item, $keyName));
                        } else {
                            $arr[] = array_get($item, $keyName);
                        }
                    }

                    return $arr;
                });

                $rows = collect([$head])->merge($bodyRows);
                $sheet->rows($rows);

            });

        })->export('xls');
    }

    public function price($value) {
        return $value / 10000;
    }

    public function getKeyName($keyName) {
        return count(explode('.', $keyName)) > 1 ? substr($keyName, strripos($keyName, ".") + 1) : $keyName;
    }
}