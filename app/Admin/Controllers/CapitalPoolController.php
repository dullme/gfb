<?php

namespace App\Admin\Controllers;

use App\Models\CapitalPool;
use App\Http\Controllers\Controller;
use App\Models\Complex;
use App\Models\User;
use App\Models\Withdraw;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Encore\Admin\Layout\Row;
use Encore\Admin\Layout\Column;
use Predis\Client;

class CapitalPoolController extends Controller {

    use HasResourceActions;

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content) {
        $amount = getTodayAmount();

        return $content
            ->header('资金池管理')
            ->description(' ')
            ->row(view('admin.capitalPools', compact('amount')))
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
            ->header('广告费详情')
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
            ->header('编辑广告费')
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
            ->header('新增广告费')
            ->description(' ')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid() {
        $grid = new Grid(new CapitalPool);

        $grid->created_at('变动时间');
        $grid->type('类型');
        $grid->price('广告费总额');
        $grid->Balance('资金池余额');
        $grid->change_amount('变动金额');

        $grid->disableFilter();
        $grid->disableExport();//禁用导出数据按钮
        $grid->disableRowSelector();//禁用行选择checkbox
        $grid->disableActions();//禁用行操作列

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id) {
        $show = new Show(CapitalPool::findOrFail($id));

        $show->created_at('变动时间');
        $show->type('类型');
        $show->price('广告费总额');
        $show->Balance('资金池余额');
        $show->change_amount('变动金额');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form() {
        $form = new Form(new CapitalPool);

        $form->text('type', '类型');
        $form->decimal('price', '广告费总额');
        $form->decimal('Balance', '资金池余额');
        $form->decimal('change_amount', '变动金额');

        return $form;
    }
}
