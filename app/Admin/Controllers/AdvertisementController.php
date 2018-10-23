<?php

namespace App\Admin\Controllers;

use Cache;
use App\Admin\Extensions\Tools\ChangeAdvertisementStatus;
use App\Models\Advertisement;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Http\Request;

class AdvertisementController extends Controller {

    use HasResourceActions;

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content) {
        return $content
            ->header('广告资源')
            ->description(' ')
            ->body($this->grid());
    }

    /**
     * Show interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function show($id, Content $content) {
        return $content
            ->header('广告详情')
            ->description(' ')
            ->body($this->detail($id));
    }

    /**
     * Edit interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content) {
        return $content
            ->header('编辑广告')
            ->description(' ')
            ->body($this->form()->edit($id));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content) {
        return $content
            ->header('新增广告')
            ->description(' ')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid() {
        $grid = new Grid(new Advertisement);

        $grid->title('广告标题');
        $grid->img_uri('广告')->display(function ($value) {
            return $value ?: $this->img;
        })->image();
        $grid->ad_expenses('广告经费');
        $grid->divided_count('被分润次数');
        $grid->divided_amount('被分润金额');
        $grid->created_at('上架时间');
        $grid->status('状态')->display(function ($status) {
            $color = array_get(Advertisement::$statusColors, $status, 'grey');
            $status = getAdvertisementStatus($status);

            return "<span class=\"badge bg-$color\">$status</span>";
        });

        $grid->tools(function ($tools) {
            $tools->batch(function ($batch) {
                $batch->add('下架', new ChangeAdvertisementStatus(0));
                $batch->add('上架', new ChangeAdvertisementStatus(1));
            });
        });

        //筛选
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->like('title', '标题');
        });

        $grid->disableExport();//禁用导出数据按钮

        return $grid;
    }

    public function store() {
        Cache::forget('advertisement');

        return $this->form()->store();
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id) {
        $show = new Show(Advertisement::findOrFail($id));

        $show->title('标题');
        $show->img_uri('广告')->as(function ($value) {
            return $value ?: $this->img;
        })->image();
        $show->ad_expenses('广告经费');
        $show->divided_count('被分润次数');
        $show->divided_amount('被分润金额');
        $show->status('状态')->as(function ($value){
            return getAdvertisementStatus($value);
        });

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form() {
        $form = new Form(new Advertisement);

        $form->text('title', '广告标题	')->rules('required');
        $form->decimal('ad_expenses', '广告经费')->rules('required');
        $form->image('img', '广告文件')->rules('required_without_all:img_uri');
        $form->url('img_uri', '广告链接')->rules('url|required_without_all:img');

        $form->switch('status', '状态');

        return $form;
    }

    public function changeStatus(Request $request) {
        Cache::forget('advertisement');
        foreach (Advertisement::find($request->get('ids')) as $product) {
            $product->status = $request->get('action');
            $product->save();
        }
    }
}
