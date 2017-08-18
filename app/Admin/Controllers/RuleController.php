<?php

namespace App\Admin\Controllers;

use App\Models\Arctype;
use App\Models\Rule;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use App\Models\Gurl;

class RuleController extends Controller
{
    use ModelForm;

    /**
     * Index interface.
     *
     * @return Content
     */
    public function index()
    {
        return Admin::content(function (Content $content) {

            $content->header('header');
            $content->description('description');

            $content->body($this->grid());
        });
    }

    /**
     * Edit interface.
     *
     * @param $id
     * @return Content
     */
    public function edit($id)
    {
        return Admin::content(function (Content $content) use ($id) {

            $content->header('header');
            $content->description('description');

            $content->body($this->form()->edit($id));
        });
    }

    /**
     * Create interface.
     *
     * @return Content
     */
    public function create()
    {
        return Admin::content(function (Content $content) {

            $content->header('header');
            $content->description('description');

            $content->body($this->form());
        });
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Admin::grid(Rule::class, function (Grid $grid) {

            $grid->column('id','序号')->sortable();
            $grid->column('rule_name','规则名称');
            //网站链接地址
            $grid->column('site_url','网站链接地址');

            //分类名称
            $grid->arctypes()->dede_typename('所属分类');

            //命令
            $grid->column('rule_command','命令');
            //参数
            $grid->column('rule_args','参数');
            //对应的文件路径
            $grid->column('file_path','文件路径');

            $grid->created_at();
            $grid->updated_at();
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Admin::form(Rule::class, function (Form $form) {

            $form->display('id', 'ID');
            $form->text('rule_name','规则名称')->rules('required');
            $form->text('site_url','采集链接地址')->rules('required');
            $form->select('type_id','所属分类')->options(Arctype::selectOptions());
            $form->text('rule_command','php artisan命令')->rules('required');
            $form->text('rule_args','命令参数')->help('以逗号分隔,可有可无参数,用包含?,其他为必须参数,--queue后面不需要加=.example:page_start,page_tot,dbname,table_name,typeid,aid?,--queue');

            $form->text('file_path','命令所有路径')->help('只须填写Commands下面路径即可.example:Caiji/DaluTvs.php')->rules('required');

            $form->display('created_at', 'Created At');
            $form->display('updated_at', 'Updated At');

            //保存之前
            $form->saving(function (Form $form) {
                //edit=put create=post
                if(stripos($form->file_path,str_replace('\\','/',app_path())) === false) {
                    $commandPath = app_path() . '/Console/Commands/' . trim($form->file_path, '/\\');
                    $commandPath = str_replace('\\', '/', $commandPath);
                    $form->file_path = $commandPath;
                }
            });
        });
    }
}
