<?php

namespace App\Admin\Controllers;

use App\Models\Withdraw;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class WithdrawController extends Controller
{
    use HasResourceActions;

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->header('提现管理')
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
    public function show($id, Content $content)
    {
        return $content
            ->header('Detail')
            ->description('description')
            ->body($this->detail($id));
    }

    /**
     * Edit interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return $content
            ->header('Edit')
            ->description('description')
            ->body($this->form()->edit($id));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        return $content
            ->header('Create')
            ->description('description')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Withdraw);

        $grid->model()->orderBy('status', 'asc')->orderBy('id', 'desc');

        $grid->user()->id('用户名');
        $grid->created_at('申请日期');
        $grid->user()->alipay_name('支付宝账户姓名');
        $grid->user()->mobile('电话');
        $grid->user()->alipay_account('支付宝账户');
        $grid->price('申请金额');
        $grid->status('状态')->display(function ($status){
            $color = array_get(Withdraw::$statusColors, $status, 'grey');
            $status = array_get(Withdraw::$status, $status, '未知');

            return "<span class=\"badge bg-$color\">$status</span>";
        });

        $grid->disableCreateButton();//禁用创建按钮
        $grid->disableActions();//禁用行操作列

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(Withdraw::findOrFail($id));

        $show->id('Id');
        $show->user_id('User id');
        $show->price('Price');
        $show->status('Status');
        $show->created_at('Created at');
        $show->updated_at('Updated at');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Withdraw);

        $form->number('user_id', 'User id');
        $form->decimal('price', 'Price');
        $form->switch('status', 'Status');

        return $form;
    }
}
