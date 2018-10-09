<?php

namespace App\Admin\Controllers;

use App\Models\User;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Http\Request;

class UserController extends Controller {

    use HasResourceActions;

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content) {
        return $content
            ->header('用户管理')
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
            ->header('用户详情')
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
            ->header('编辑用户')
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
            ->header('新增用户')
            ->description(' ')
            ->body($this->createForm());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid() {
        $grid = new Grid(new User);
        $status = \Request::get('status', 2);

        $grid->model()->where('status', $status)->orderBy('id', 'desc');

        $grid->id('用户名');
        $grid->original_price('发行价');
        $grid->retail_price('零售价');
        $grid->mobile('电话');
        $grid->alipay_account('支付宝账户');
        $grid->alipay_name('支付宝账户姓名');
        $grid->status('状态')->display(function ($status) {
            $color = array_get(User::$statusColors, $status, 'grey');
            $status = getUserStatus($status);

            return "<span class=\"badge bg-$color\">$status</span>";
        });
        $grid->initial_password('初始密码');
        $grid->activation_at('激活时间')->display(function ($value){

            return $value ? : '—';
        });

        //筛选
        $grid->filter(function ($filter) {
            $filter->equal('id', '用户名');
            $filter->equal('status', '状态')->radio([
                0    => '待售',
                1    => '待激活',
                2    => '已激活',
            ]);
        });

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id) {
        $show = new Show(User::findOrFail($id));

        $show->id('用户名');
        $show->original_price('发行价');
        $show->retail_price('零售价');
        $show->mobile('电话');
        $show->alipay_account('支付宝账号');
        $show->alipay_name('支付宝用户名');
        $show->status('状态')->as(function ($status) {

            return getUserStatus($status);
        });
        $show->activation_at('激活时间')->as(function ($value){

            return $value ? substr($value, 0, 10) : '—';
        });

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function createForm() {
        $form = new Form(new User);

        $form->number('number', '新增数量')->rules('required|numeric|min:1|max:50')->default(1);
        $form->decimal('original_price', '发行价')->rules('required|numeric');
        $form->decimal('retail_price', '零售价')->rules('required|numeric');

        $form->tools(function (Form\Tools $tools) {
            $tools->disableDelete();
            $tools->disableView();
        });

        $form->disableViewCheck();
        $form->disableEditingCheck();

        return $form;
    }

    protected function form() {
        $form = new Form(new User);

        $form->text('id', '用户名')->readOnly();
        $form->decimal('original_price', '发行价');
        $form->decimal('retail_price', '零售价');
        $form->text('alipay_name', '支付宝用户名');
        $form->text('alipay_account', '支付宝账号');
        $form->text('mobile', '联系电话');
        $form->password('password', '重置密码');

        $form->tools(function (Form\Tools $tools) {
            $tools->disableDelete();
            $tools->disableView();
        });

        $form->disableViewCheck();
        $form->disableEditingCheck();

        $form->saving(function (Form $form) {
            $form->password = bcrypt($form->password);
        });

        return $form;
    }

    public function store(Request $request) {
        $request->validate([
            'number' => 'required|numeric|min:1|max:10000',
            'original_price' => 'required|numeric',
            'retail_price' => 'required|numeric',
        ]);
        $datetime = Carbon::now();
        $original_price = $request->get('original_price');
        $retail_price = $request->get('retail_price');

        for ($i = 0; $i < $request->get('number'); $i++) {
            $password = makeInvitationCode(10);
            $data[$i]['password'] = md5($password);
            $data[$i]['initial_password'] = $password;
            $data[$i]['original_price'] = $original_price;
            $data[$i]['retail_price'] = $retail_price;
            $data[$i]['created_at'] = $datetime;
            $data[$i]['updated_at'] = $datetime;
        }

        User::insert($data);
        admin_toastr('创建成功', 'success');
    }

    public function complexToday(Content $content) {
        return $content
            ->header('今日动态')
            ->description(' ')
            ->body($this->complexTodayGrid());
    }


    protected function complexTodayGrid() {
        $grid = new Grid(new User);
        $grid->model()->where('status', '1');

        $grid->model()->orderBy('id', 'desc');

        $grid->id('用户名');
        $grid->mobile('电话');
        $grid->alipay_account('支付宝账户');
        $grid->alipay_name('支付宝账户姓名');
        $grid->activation_at('激活时间')->display(function ($value){

            return $value ? substr($value, 0, 10) : '—';
        });
        $grid->complexes('历史总次/分润总计')->display(function ($complexes){
            $count = $amount = 0;
            foreach ($complexes as $item){
                $count += $item['history_read_count'];
                $amount += $item['history_amount'];
            }
            return "{$count} / {$amount}";
        });

        $grid->column('套现总次数')->display(function () {
            return  123;
        });
        $grid->column('套现总金额')->display(function () {

            return 332;
        });

        $grid->column('浏览频度(秒)	')->display(function () {
            return config('ad_frequency');
        });
        $grid->column('最大次数')->display(function () {
            return config('max_visits');
        });

        $grid->column('当日总次')->display(function () {
            return  !is_null(\Redis::get("v_{$this->id}_".date('Ymd')))?:0;
        });
        $grid->column('当日金额')->display(function () {
            $amount = \Redis::get("a_{$this->id}_".date('Ymd'));
            return is_null($amount)?0:$amount/100;
        });

        //筛选
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->equal('user.mobile', '电话');
            $filter->equal('user.alipay_account', '支付宝账户');
        });

        $grid->disableCreateButton();//禁用创建按钮
        $grid->disableExport();//禁用导出数据按钮
        $grid->disableRowSelector();//禁用行选择checkbox
        $grid->disableActions();//禁用行操作列

        return $grid;
    }
}
