<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\ExcelExpoter;
use App\Admin\Extensions\Tools\ChangeUserStatus;
use App\Admin\Extensions\Tools\UserTool;
use App\Http\Controllers\RedisController;
use App\Models\AdminConfig;
use App\Models\Service;
use App\Models\User;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use function Couchbase\defaultDecoder;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Predis\Client;

class UserController extends Controller
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
            ->header('用户管理'.env('LOCAL_IP'))
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
    public function edit($id, Content $content)
    {
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
    public function create(Content $content)
    {
        return $content
            ->header('新增用户')
            ->description(' ')
            ->body($this->createForm());
    }

    public function destroy($id)
    {
        $ids = explode(',', $id);
        $service = Service::all();
        $guzzle = new \GuzzleHttp\Client();
        collect($ids)->map(function ($id) use ($service, $guzzle){
            if(count($service)){
                foreach ($service as $item){
                    $guzzle->get("http://{$item->ip}:{$item->port}/clear-redis?user_id={$id}&token=1024gfb1024");
                }
            }
        });

        if ($this->form()->destroy($id)) {
            $data = [
                'status'  => true,
                'message' => trans('admin.delete_succeeded'),
            ];
        } else {
            $data = [
                'status'  => false,
                'message' => trans('admin.delete_failed'),
            ];
        }

        return response()->json($data);
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new User);
        $status = \Request::get('user', 2);

        $grid->model()->where('status', $status)->orderBy('id', 'desc');

        $grid->id('用户名');
        $grid->staff('所属员工')->display(function ($staff){
            if($staff){
                return $staff['name'];
            }
            return '-';
        });
//        $grid->original_price('发行价');
//        $grid->retail_price('零售价');
        $grid->validity_period('有效期限/天')->sortable();
        $grid->mobile('电话');
        $grid->realname('姓名');
        $grid->alipay_account('支付宝账户');
        $grid->alipay_name('淘宝号');
        $grid->status('状态')->display(function ($status) {
            $color = array_get(User::$statusColors, $status, 'grey');
            $status = getUserStatus($status);

            return "<span class=\"badge bg-$color\">$status</span>";
        });
        $grid->initial_password('初始密码');
        $grid->column('是否变更')->display(function () {
            return $this->password == md5($this->initial_password) ? '否' : '<span class="badge bg-danger">是</span>';
        });
        $grid->activation_at('激活时间')->display(function ($value) {

            return $value ?: '—';
        })->sortable();
        $grid->expiration_at('有效期至')->display(function ($value) {

            return $value ?: '—';
        })->sortable();
        $grid->remarks('备注');

        $grid->actions(function ($action) {
            $action->disableDelete();
        });

        //筛选
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->equal('id', '用户名');
            $filter->between('id', '用户名');
            $filter->like('mobile', '电话');
            $filter->date('activation_at', '激活时间');
            $filter->like('staff.name', '所属员工');
        });

        $grid->tools(function ($tools) {
            $url = url('admin/edit-expiration');
            $url2 = url('admin/add-days');
            $tools->append(new UserTool());
            $tools->append("<a class='btn btn-sm btn-info' href='{$url}'>批量修改有效期</a>");
            $tools->append("<a class='btn btn-sm btn-warning' href='{$url2}'>增加/减少天数</a>");
            $tools->batch(function ($batch) {
                $batch->add('出售', new ChangeUserStatus(1));
                $batch->add('冻结', new ChangeUserStatus(3));
                $batch->add('解冻', new ChangeUserStatus(2));
            });
        });

        $excel = new ExcelExpoter();
        $excel->setAttr(
            ['用户名', '初始密码', '有效期(月)', '状态'],
            ['id', 'initial_password', 'validity_period', 'status'],
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
    protected function detail($id)
    {
        $show = new Show(User::findOrFail($id));

        $show->id('用户名');
//        $show->original_price('发行价');
//        $show->retail_price('零售价');
        $show->validity_period('有效期限/天');
        $show->mobile('电话');
        $show->realname('姓名');
        $show->alipay_account('支付宝账号');
        $show->alipay_name('淘宝号');
        $show->status('状态')->as(function ($status) {

            return getUserStatus($status);
        });
        $show->activation_at('激活时间')->as(function ($value) {

            return $value ? substr($value, 0, 10) : '—';
        });
        $show->remarks('备注');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function createForm()
    {
        $form = new Form(new User);

        $form->number('number', '新增数量')->rules('required|numeric|min:1|max:50')->default(1);
        $form->number('validity_period', '有效期限/天')->rules('required|numeric|min:1')->default(3);
//        $form->decimal('original_price', '发行价')->rules('required|numeric');
//        $form->decimal('retail_price', '零售价')->rules('required|numeric');

        $form->tools(function (Form\Tools $tools) {
            $tools->disableDelete();
            $tools->disableView();
        });

        $form->disableViewCheck();
        $form->disableEditingCheck();

        return $form;
    }

    protected function form()
    {
        $form = new Form(new User);

        $form->text('id', '用户名')->readOnly();
//        $form->decimal('original_price', '发行价');
//        $form->decimal('retail_price', '零售价');
//        $form->number('validity_period', '有效期限/天');
        $form->text('realname', '姓名');
        $form->text('alipay_name', '淘宝号');
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

        $form->textarea('remarks', '备注');

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

    public function store(Request $request)
    {
        $request->validate([
            'number'          => 'required|numeric|min:1|max:10000',
            'validity_period' => 'required|numeric|min:1',
//            'original_price'  => 'required|numeric',
//            'retail_price'    => 'required|numeric',
        ]);
        $datetime = Carbon::now();
//        $original_price = $request->get('original_price');
//        $retail_price = $request->get('retail_price');
        $validity_period = $request->get('validity_period');

        for ($i = 0; $i < $request->get('number'); $i++) {
            $password = makeInvitationCode(6);
            $data[$i]['password'] = md5($password);
            $data[$i]['initial_password'] = $password;
//            $data[$i]['original_price'] = $original_price;
//            $data[$i]['retail_price'] = $retail_price;
            $data[$i]['created_at'] = $datetime;
            $data[$i]['updated_at'] = $datetime;
            $data[$i]['validity_period'] = $validity_period;
        }

        User::insert($data);
        admin_toastr('创建成功', 'success');
    }

    public function complexToday(Content $content)
    {
        return $content
            ->header('今日动态')
            ->description(' ')
            ->body($this->complexTodayGrid());
    }


    protected function complexTodayGrid()
    {
        $config = $this->client->get('config');

        if ($config) {
            $config = json_decode($config, true);
        } else {
            $config = AdminConfig::select('name', 'value')->get()->pluck('value', 'name')->toArray();
            $this->client->set('config', json_encode($config));
        }

        $grid = new Grid(new User);

        $grid->model()->where('status', '2')->orderBy('id', 'desc');

        $grid->id('用户名');
        $grid->mobile('电话');
        $grid->realname('姓名');
        $grid->alipay_account('支付宝账户');
        $grid->alipay_name('淘宝号');
        $grid->activation_at('激活时间')->display(function ($value) {

            return $value ? substr($value, 0, 10) : '—';
        });

        $grid->column('浏览频度(秒)	')->display(function () use ($config) {
            return $config['ad_frequency'];
        });
        $grid->column('最大次数')->display(function () use ($config) {
            return $config['max_visits'];
        });

        $redis = new RedisController();

        $grid->column('当日总次')->display(function () use ($redis) {

            return $redis->userTodayVisit($this->id);
        });
        $grid->column('当日金额')->display(function () use ($redis) {

            return $redis->userTodayAmount($this->id) / 10000;
        });

        $grid->amount('余额')->display(function ($amount) {

            return $amount / 10000;
        })->sortable();

        $grid->history_read_count('历史浏览次数');
        $grid->history_amount('历史分润总额')->display(function ($history_amount) {

            return $history_amount / 10000;
        });

        $grid->withdraws('套现总次数/套现总金额')->display(function ($withdraws) use ($redis) {
            $count = count($withdraws);
            $amount = 0;
            foreach ($withdraws as $item) {
                $amount += $item['price'];
            }
            $amount = $amount / 10000;

            $res = $this->history_amount + $redis->userTodayAmount($this->id);
            $aa = $amount * 10000 + $this->amount;
            $str = '';
            if ($this->id < 1000000) {
                if ($res > $aa) {
                    $res /= 10000;
                    $aa /= 10000;
                    $str = "/ <span style='color: red'>{$res} > {$aa}";
                } else if ($res < $aa) {
                    $res /= 10000;
                    $aa /= 10000;
                    $str = "/ <span style='color: deepskyblue'>{$res} < {$aa}</span>";
                }
            }else{
                if ($res > $aa) {
                    $res /= 10000;
                    $aa /= 10000;
                    $str = "/ <span style='color: darkorange'>{$res} > {$aa}";
                } else if ($res < $aa) {
                    $res /= 10000;
                    $aa /= 10000;
                    $str = "/ <span style='color: green'>{$res} < {$aa}</span>";
                }
            }

            return "{$count} / {$amount}{$str}";
        });

        //筛选
        $grid->filter(function ($filter) {
            $filter->equal('id', '用户名');
            $filter->equal('mobile', '电话');
            $filter->equal('alipay_account', '支付宝账户');
        });

        $grid->disableCreateButton();//禁用创建按钮
        $grid->disableExport();//禁用导出数据按钮
        $grid->disableRowSelector();//禁用行选择checkbox
        $grid->disableActions();//禁用行操作列

        $grid->perPages([50, 100, 200, 500, 1000]);

        return $grid;
    }

    public function changeStatus(Request $request)
    {
        $status = $request->get('action');
        $changed = 0;

        $service = Service::all();
        $guzzle = new \GuzzleHttp\Client();

        foreach (User::find($request->get('ids')) as $product) {
            if ($status == 1) {   //出售
                if ($product->status == 0) {
                    $product->status = $status;
                    $product->save();
                    $changed++;
                }
            }

            if ($status == 3 || $status == 2) {
                if ($status == 3) {   //禁用
                    if ($product->status == 2) {
                        $product->status = $status;
                        $product->remember_token = makeInvitationCode(6);
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

                if(count($service)){
                    foreach ($service as $item){
                        $guzzle->get("http://{$item->ip}:{$item->port}/clear-redis?user_id={$product->id}&token=1024gfb1024");
                    }
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

    public function showEditExpiration(Content $content)
    {
        return $content
            ->header('批量修改到期时间')
            ->description(' ')
            ->body(view('admin.expiration'));
    }

    public function editExpiration(Request $request)
    {
        $request->validate([
            'start_id'    => 'required|integer',
            'end_id'      => 'required|integer',
            'new_validity_period' => 'integer',
            'add_validity_period' => 'integer',
        ]);

        $start_id = $request->get('start_id');
        $end_id = $request->get('end_id');
        $new_validity_period = $request->get('new_validity_period');
        $add_validity_period = $request->input('add_validity_period');
        $ids = [];
        for ($i = $start_id; $i <= $end_id; $i++) {
            $ids[] = $i;
        }

        if(count($ids) > 1000){
            return response()->json([
                'status'  => false,
                'message' => '修改的用户最多为1000个'
            ]);
        }
        $users = User::whereBetween('id', [$start_id, $end_id])->get();
        if (count($users)) {
            $validity_period = $users->first()->validity_period;
            foreach ($users as $user) {
                if ($validity_period != $user->validity_period) {
                    return response()->json([
                        'status'  => false,
                        'message' => $user->id . '与其他账号有效期不一致！'
                    ]);
                }
            }

            $diff_array = array_diff($ids, $users->pluck('id')->toArray());
            if ($diff_array) {
                return response()->json([
                    'status'  => false,
                    'message' => '存在不连续的用户名' . implode(',', $diff_array)
                ]);
            } else {

                $service = Service::all();
                $guzzle = new \GuzzleHttp\Client();

                $res = $users->map(function ($user) use ($new_validity_period, $add_validity_period, $service, $guzzle){
                    if($add_validity_period > 0){

                        if(!is_null($user->expiration_at)){
                            $expiration_at = Carbon::createFromFormat('Y-m-d H:i:s', $user->expiration_at)->addDays($add_validity_period);
                            $userUpdate =  User::where('id', $user->id)->update([
                                'expiration_at'   => $expiration_at
                            ]);
                        }


                    }else{

                        if(!is_null($user->activation_at)){
                            $expiration_at = Carbon::createFromFormat('Y-m-d H:i:s', $user->activation_at)->addMonths($new_validity_period);
                        }else{
                            $expiration_at = null;
                        }

                        $userUpdate =  User::where('id', $user->id)->update([
                            'validity_period' => $new_validity_period,
                            'expiration_at'   => $expiration_at
                        ]);
                    }



                    if(count($service)){
                        foreach ($service as $item){
                            $guzzle->get("http://{$item->ip}:{$item->port}/clear-redis?user_id={$user->id}&token=1024gfb1024");
                        }
                    }

                    return $userUpdate;
                });

                if($add_validity_period > 0){
                    $message = '成功为'. $res->sum() .'条记录的有效期增加了'.$add_validity_period.'天';
                }else{
                    $message = '成功修改'. $res->sum() .'条记录的有效期为'.$new_validity_period.'个月';
                }

                return response()->json([
                    'status'  => true,
                    'message' => $message
                ]);
            }
        } else {
            return response()->json([
                'status'  => false,
                'message' => '不存在用户'
            ]);
        }

    }

    public function showAddDays(Content $content)
    {
        return $content
            ->header('增加/减少天数')
            ->description(' ')
            ->body(view('admin.addDays'));
    }

    public function editAddDays(Request $request)
    {
        $request->validate([
            'add_validity_period' => 'required|integer|min:1',
            'type' => 'required|integer|min:1',
        ]);

        $add_days = $request->input('add_validity_period');
        $type = $request->input('type');

        if($type == 1){ //增加
            $type_text = '增加';
            $users = DB::update(
                'update users set expiration_at= DATE_ADD(expiration_at, INTERVAL ? DAY) where status = 2 and expiration_at > ?',
                [$add_days, Carbon::now()]
            );
        }else{ //减少
            $type_text = '减少';
            $users = DB::update(
                'update users set expiration_at= DATE_SUB(expiration_at, INTERVAL ? DAY) where status = 2 and expiration_at > ?',
                [$add_days, Carbon::now()]
            );
        }



        if($users){
            $service = Service::all();
            $guzzle = new \GuzzleHttp\Client();
            if(count($service)){
                foreach ($service as $item){
                    $guzzle->get("http://{$item->ip}:{$item->port}/clear-redis-all?token=1024gfb1024");
                }
            }

            return response()->json([
                'status'  => true,
                'message' => '成功为'. $users .'位用户'.$type_text.'了'.$add_days.'天有效期,成功清空所有服务器的Redis数据！'
            ]);
        }else{
            return response()->json([
                'status'  => false,
                'message' => "操作失败！需要增加天数的用户数为{$users}"
            ]);
        }
    }
}
