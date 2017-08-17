<?php

namespace App\Admin\Controllers;

use Encore\Admin\Form;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use App\Models\Arctype;

use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Row;
use Encore\Admin\Tree;
use Encore\Admin\Widgets\Box;

class ArctypeController extends Controller
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
            $content->header(trans('admin::lang.menu'));
            $content->description(trans('admin::lang.list'));

            $content->row(function (Row $row) {
                $row->column(6, $this->treeView()->render());

                $row->column(6, function (Column $column) {
                    $form = new \Encore\Admin\Widgets\Form();
                    $form->action(admin_url('arctypes'));

                    $form->select('top_id', '上级分类')->options(Arctype::selectOptions());
                    $form->text('dede_id', '对应dede系统中栏目id')->rules('required');
                    $form->text('dede_typename', '对应dede系统中栏目名称')->rules('required');

//                    $form->hasMany('gurls', function (Form\NestedForm $form) {
//                        $form->text('site_name');
//                        $form->text('gurl');
//                    });
                    $column->append((new Box(trans('admin::lang.new'), $form))->style('success'));
                });
            });
        });
    }


    /**
     * @return \Encore\Admin\Tree
     */
    protected function treeView()
    {
        return Arctype::tree(function (Tree $tree) {
            $tree->disableCreate();

            $tree->branch(function ($branch) {
                $payload = "<i>{$branch['id']}</i>&nbsp;&nbsp;&nbsp;&nbsp;<strong>{$branch['dede_id']}--{$branch['dede_typename']}</strong>";
                return $payload;
            });
        });
    }

    /**
     * Edit interface.
     *
     * @param string $id
     *
     * @return Content
     */
    public function edit($id)
    {
        return Admin::content(function (Content $content) use ($id) {
            $content->header(trans('admin::lang.menu'));
            $content->description(trans('admin::lang.edit'));

            $content->row($this->form()->edit($id));
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    public function form()
    {
        return Arctype::form(function (Form $form) {
            $form->display('id', 'ID');

            $form->select('top_id', '上级分类')->options(Arctype::selectOptions());
            $form->text('dede_id', '对应dede表中的栏目')->rules('required');
            $form->text('dede_typename', '对应dede表中的栏目名称')->rules('required');

            $form->hasMany('gurls', function (Form\NestedForm $form) {
                $form->text('site_name');
                $form->text('gurl');
            });

            $form->display('created_at', trans('admin::lang.created_at'));
            $form->display('updated_at', trans('admin::lang.updated_at'));
        });
    }

}
