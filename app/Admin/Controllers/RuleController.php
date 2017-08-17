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

            $grid->id('ID')->sortable();
            $grid->column('rule_name','规则名称');
            $grid->column('gurl_id','采集链接地址')->display(function ($gid){
                $gurlobj = Gurl::find($gid);
                $arctypeObj = $gurlobj->arctypes;
                return '(dede) '.$arctypeObj->dede_id.' -- (dede) '.$arctypeObj->dede_typename.' -- '.$gurlobj->gurl;
            });
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
            $form->radio('gurl_id','采集链接地址')->options(Gurl::all()->pluck('gurl', 'id'))->rules('required');
            $form->text('rule_command','php artisan命令')->rules('required');
            $form->text('rule_args','命令参数')->help('以逗号分隔,可有可无参数,用包含?,其他为必须参数,--queue后面不需要加=.example:dbname,tablename,typeid,aid?,--queue');

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
