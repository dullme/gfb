<?php

namespace App\Admin\Controllers;

use App\Models\Service;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Http\Request;

class ServiceController extends Controller
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
            ->header('服务器管理')
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
            ->header('详情')
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
            ->header('编辑')
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
            ->header('添加')
            ->description(' ')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Service);

        $grid->id('Id');
        $grid->ip('IP地址');
        $grid->port('端口号');
        $grid->remark('备注');

        $grid->tools(function ($tools){
            $tools->append('<a class="btn btn-sm btn-danger" href="'.url('/admin/service-refresh-client-redis').'">重置所有服务器配置信息</a>');
        });

        $grid->disableExport();
        $grid->disableFilter();

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
        $show = new Show(Service::findOrFail($id));

        $show->id('Id');
        $show->ip('IP地址');
        $show->port('端口号');
        $show->remark('备注');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Service);

        $form->ip('ip', 'IP地址')->rules('required');
        $form->text('port', '端口号')->rules('required');
        $form->text('remark', '备注');

        return $form;
    }

    public function refreshClientRedis()
    {
        $service = Service::all();
        $guzzle = new \GuzzleHttp\Client();
        if(count($service)){
            foreach ($service as $item){
                $guzzle->get("http://{$item->ip}:{$item->port}/clear-config?token=1024gfb1024");
            }
        }

        admin_toastr('服务器刷新成功', 'success');

        return back();
    }
}
