<?php

namespace App\Admin\Controllers;

use App\Models\Arctype;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use App\Models\Gurl;

class GurlController extends Controller
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
        return Admin::grid(Gurl::class, function (Grid $grid) {

            $grid->id('ID')->sortable();
            $grid->column('site_name','网站名称');
            $grid->column('gurl','采集链接地址');
            $grid->column('arctype_id','所属分类')->display(function($arctypeId) {
                $arctypeObj = Arctype::find($arctypeId);
                $dedeId = $arctypeObj->dede_id;
                $dedeTypeName = $arctypeObj->dede_typename;
                return $dedeId.' -- '.$dedeTypeName;
            });

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
        return Admin::form(Gurl::class, function (Form $form) {

            $form->display('id', 'ID');
            $form->text('site_name','网站名称');
            $form->text('gurl','采集链接地址');
            $form->select('arctype_id','所属分类')->options(Arctype::selectOptions());

            $form->display('created_at', 'Created At');
            $form->display('updated_at', 'Updated At');
        });
    }
}
