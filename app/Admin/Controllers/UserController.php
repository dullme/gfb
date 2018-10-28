<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\ExcelExpoter;
use App\Admin\Extensions\Tools\ChangeUserStatus;
use App\Admin\Extensions\Tools\UserTool;
use App\Http\Controllers\RedisController;
use App\Models\User;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Http\Request;
use Predis\Client;

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
        $status = \Request::get('user', 2);

        $grid->model()->where('status', $status)->orderBy('id', 'desc');

        $grid->id('用户名');
        $grid->original_price('发行价');
        $grid->retail_price('零售价');
        $grid->validity_period('有效期限/月')->sortable();
        $grid->mobile('电话');
        $grid->alipay_account('支付宝账户');
        $grid->alipay_name('支付宝账户姓名');
        $grid->status('状态')->display(function ($status) {
            $color = array_get(User::$statusColors, $status, 'grey');
            $status = getUserStatus($status);

            return "<span class=\"badge bg-$color\">$status</span>";
        });
        $grid->initial_password('初始密码');
        $grid->column('是否变更')->display(function (){
            return $this->password == md5($this->initial_password)?'否':'<span class="badge bg-danger">是</span>';
        });
        $grid->activation_at('激活时间')->display(function ($value) {

            return $value ?: '—';
        })->sortable();
        $grid->expiration_at('有效期至')->display(function ($value) {

            return $value ?: '—';
        })->sortable();

        $grid->actions(function ($action) {
            $action->disableDelete();
        });

        //筛选
        $grid->filter(function ($filter) {
            $filter->equal('id', '用户名');
            $filter->like('mobile', '电话');
            $filter->date('activation_at', '激活时间');
        });

        $grid->tools(function ($tools) {
            $tools->append(new UserTool());
            $tools->batch(function ($batch) {
                $batch->disableDelete();
                $batch->add('出售', new ChangeUserStatus(1));
                $batch->add('冻结', new ChangeUserStatus(3));
                $batch->add('解冻', new ChangeUserStatus(2));
            });
        });

        $excel = new ExcelExpoter();
        $excel->setAttr(
            ['用户名', '初始密码', '有效期(月)', '状态'],
            ['id','initial_password', 'validity_period', 'status'],
            ['status']
        );
        $grid->exporter($excel);

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
        $show->validity_period('有效期限/月');
        $show->mobile('电话');
        $show->alipay_account('支付宝账号');
        $show->alipay_name('支付宝用户名');
        $show->status('状态')->as(function ($status) {

            return getUserStatus($status);
        });
        $show->activation_at('激活时间')->as(function ($value) {

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
        $form->number('validity_period', '有效期限/月')->rules('required|numeric|min:1')->default(3);
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
//        $form->number('validity_period', '有效期限/月');
        $form->text('alipay_name', '支付宝用户名');
        $form->text('alipay_account', '支付宝账号');
        $form->text('mobile', '联系电话');

        $form->password('password', '密码')->rules('required|confirmed')
            ->default(function ($form) {
                return $form->model()->password;
            });
        $form->password('password_confirmation', '确认密码')->rules('required')
            ->default(function ($form) {
                return $form->model()->password;
            });

        $form->ignore(['password_confirmation']);
        $form->tools(function (Form\Tools $tools) {
            $tools->disableDelete();
            $tools->disableView();
        });

        $form->disableViewCheck();
        $form->disableEditingCheck();
        $form->saving(function (Form $form) {
            if ($form->password && $form->model()->password != $form->password) {
                $form->password = md5($form->password);
            }
        });

        return $form;
    }

    public function store(Request $request) {
        $request->validate([
            'number'          => 'required|numeric|min:1|max:10000',
            'validity_period' => 'required|numeric|min:1',
            'original_price'  => 'required|numeric',
            'retail_price'    => 'required|numeric',
        ]);
        $datetime = Carbon::now();
        $original_price = $request->get('original_price');
        $retail_price = $request->get('retail_price');
        $validity_period = $request->get('validity_period');

        for ($i = 0; $i < $request->get('number'); $i++) {
            $password = makeInvitationCode(6);
            $data[$i]['password'] = md5($password);
            $data[$i]['initial_password'] = $password;
            $data[$i]['original_price'] = $original_price;
            $data[$i]['retail_price'] = $retail_price;
            $data[$i]['created_at'] = $datetime;
            $data[$i]['updated_at'] = $datetime;
            $data[$i]['validity_period'] = $validity_period;
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

        $grid->model()->where('status', '2')->orderBy('id', 'desc');

        $grid->id('用户名');
        $grid->mobile('电话');
        $grid->alipay_account('支付宝账户');
        $grid->alipay_name('支付宝账户姓名');
        $grid->activation_at('激活时间')->display(function ($value) {

            return $value ? substr($value, 0, 10) : '—';
        });

        $grid->column('浏览频度(秒)	')->display(function () {
            return config('ad_frequency');
        });
        $grid->column('最大次数')->display(function () {
            return config('max_visits');
        });

        $redis = new RedisController();

        $grid->column('当日总次')->display(function () use ($redis) {

            return $redis->userTodayVisit($this->id);
        });
        $grid->column('当日金额')->display(function () use ($redis) {

            return $redis->userTodayAmount($this->id) / 10000;
        });

        $grid->amount('余额')->display(function ($amount){

            return $amount / 10000;
        })->sortable();

        $grid->history_read_count('历史浏览次数');
        $grid->history_amount('历史分润总额')->display(function ($history_amount){

            return $history_amount / 10000;
        });

        $grid->withdraws('套现总次数/套现总金额')->display(function ($withdraws) {
            $count = count($withdraws);
            $amount = 0;
            foreach ($withdraws as $item) {
                $amount += $item['price'];
            }
            $amount = $amount/10000;
            return "{$count} / {$amount}";
        });

        //筛选
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->equal('mobile', '电话');
            $filter->equal('alipay_account', '支付宝账户');
        });

        $grid->disableCreateButton();//禁用创建按钮
        $grid->disableExport();//禁用导出数据按钮
        $grid->disableRowSelector();//禁用行选择checkbox
        $grid->disableActions();//禁用行操作列

        return $grid;
    }

    public function changeStatus(Request $request) {
        $status = $request->get('action');
        $changed = 0;
        foreach (User::find($request->get('ids')) as $product) {
            if ($status == 1) {   //出售
                if ($product->status == 0) {
                    $product->status = $status;
                    $product->save();
                    $changed++;
                }
            }

            if ($status == 3) {   //禁用
                if ($product->status == 2) {
                    $product->status = $status;
                    $product->save();
                    $changed++;
                }
            }

            if ($status == 2) {   //启用
                if ($product->status == 3) {
                    $product->status = $status;
                    $product->save();
                    $changed++;
                }
            }

        }

        if ($changed) {
            $data = [
                'status'  => true,
                'message' => '成功修改' . $changed . '条记录',
            ];
        } else {
            $data = [
                'status'  => false,
                'message' => '没有需要设置的记录',
            ];
        }

        return response()->json($data);
    }
}
