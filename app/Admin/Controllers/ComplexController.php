<?php

namespace App\Admin\Controllers;

use App\Models\AdminConfig;
use App\Models\Complex;
use App\Http\Controllers\Controller;
use App\Models\User;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Predis\Client;

class ComplexController extends Controller
{
    use HasResourceActions;

    protected $client;

    /**
     * ProfitController constructor.
     * @param $client
     */
    public function __construct()
    {
        $this->client = new Client(config('database.redis.local'));
    }

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->header('分润查询')
            ->description(' ')
            ->body($this->grid());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $config = $this->client->get('config');

        if ($config) {
            $config = json_decode($config, true);
        } else {
            $config = AdminConfig::select('name', 'value')->get()->pluck('value', 'name')->toArray();
            $this->client->set('config', json_encode($config));
        }

        $grid = new Grid(new Complex);

        $grid->model()->orderBy('id', 'desc');

        $grid->id('Id');
        $grid->created_at('统计日期')->display(function ($value){
            return substr($value, 0, 10);
        });
        $grid->user()->id('用户名');
        $grid->user()->mobile('电话');
        $grid->user()->alipay_account('支付宝账户');
        $grid->user()->alipay_name('支付宝账户姓名');
        $grid->user()->activation_at('激活时间')->display(function ($value){

            return $value ? substr($value, 0, 10) : '—';
        });
        $grid->history_read_count('历史总次');
        $grid->history_amount('历史分润总计')->display(function ($history_amount){
            $history_amount = $history_amount /10000;
            if($history_amount < 0){
                $history_amount .= ' (提前提现)';
            }
            return $history_amount;
        });
        $grid->column('浏览频度(秒)	')->display(function () use($config) {

            return $config['ad_frequency'];
        });
        $grid->column('最大次数')->display(function () use($config) {
            return $config['max_visits'];
        });

        //筛选
        $grid->filter(function ($filter) {
            $filter->equal('user.id', '用户名');
            $filter->equal('user.mobile', '电话');
            $filter->equal('user.alipay_account', '支付宝账户');
        });

        $grid->disableCreateButton();//禁用创建按钮
        $grid->disableExport();//禁用导出数据按钮
//        $grid->disableRowSelector();//禁用行选择checkbox
        $grid->disableActions();//禁用行操作列

        $grid->perPages([50, 100, 200, 500, 1000]);

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
        $show = new Show(Complex::findOrFail($id));

        $show->id('Id');
        $show->user_id('User id');
        $show->history_read_count('History read count');
        $show->history_amount('History amount');
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
        $form = new Form(new Complex);

        $form->number('user_id', 'User id');
        $form->number('history_read_count', 'History read count');
        $form->decimal('history_amount', 'History amount')->default(0.00);

        return $form;
    }
}
