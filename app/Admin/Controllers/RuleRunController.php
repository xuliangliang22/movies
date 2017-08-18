<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Arctype;
use App\Models\Rule;
use Encore\Admin\Controllers\StringOutput;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\Output;

class RuleRunController extends Controller
{
    /**
     * Index interface.
     *
     * @return Content
     */
    public function index(Request $request)
    {
        if (isset($request->type)) {
            $rest = '';
            $type = $request->type;

            if ($type == 'arctype') {
                //获得当前分类下的所有采集规则,一对多
                $arctypeId = $request->id;
                $rest = $this->_arctype($arctypeId);
            }elseif ($type == 'rule') {
                $ruleId = $request->id;
                $rest = $this->_rule($ruleId);
            } elseif ($type == 'command') {
                $ruleId = $request->id;
                $args = $request->fullUrl();
                $rest = $this->_command($ruleId, $args);
            }
            echo $rest;
            exit;
        }

        return Admin::content(function (Content $content) {
            $content->header('Command');
            $content->description('Run');

            $content->row(function (Row $row) {
                $row->column(12, function (Column $column) {
//                    $form = new \Encore\Admin\Widgets\Form();
                    $form = new \App\Admin\MyExtends\RuleForm();
//                    $form->action(admin_url('rule_run'));

                    $form->select('arctype', '分类名称')->options(Arctype::selectOptions())
                        ->help('对参数--queue说明:list(列表页)content(内容页)pic(图片)dede(部署上线)cdn(上传文件)all(一键运行)');
                    //不同的按扭怎么做

                    $column->append((new Box('规则运行', $form))->style('success'));
                });
            });
        });
    }

    /**
     * 得到对应分类下的规则数据,可以是多个
     * @param $arctypeId
     * @return string
     */
    public function _arctype($arctypeId)
    {
        $str = '';
        $gurls = Rule::where('type_id', $arctypeId)->get();


        foreach ($gurls as $key => $value) {
            $str .= ' <option value="' . $value->id . '"> typeid -- '. $value->arctypes->dede_id .' -- '. $value->rule_name . '--' . $value->site_url . '</option>';
        }

        if (empty($str) === false) {
            $str = '<div class="form-group 1"><label for="rules" class="col-sm-2 control-label">采集规则</label><div class="col-sm-8"><select class="form-control rules" style="width: 100%;" name="rules"><option value="0" selected>Root</option>' . $str . '</select></div></div>';
        }
        return $str;
    }
    

    /**
     * 得到对应采集链接列表下的规则
     * @param $gurlid 采集链接id
     * @return string
     */
    public function _rule($ruleId)
    {
        $str = '<div class="rule">';
        $argStr = '';
        $ruleStr = '';
        $rule = Rule::find($ruleId);

        if ($rule) {
            //取出参数
            $args = $rule->rule_args;
            $args = explode(',', $args);
            foreach ($args as $key => $value) {
                $argName = $value;
                if (stripos($argName, '?') !== false) {
                    $argName = str_replace('?', '_nmust', $argName);
                }

                $argStr .= '<div class="form-group 1 args"><label for="' . $argName . '" class="col-sm-2 control-label">参数 ' . $value . '</label><div class="col-sm-8"><div class="input-group"><span class="input-group-addon"><i class="fa fa-pencil"></i></span><input type="text" id="' . $argName . '" name="' . $argName . '" value="" class="form-control arg1" placeholder="Input 参数' . $value . '"></div></div></div>';
            }

            //规则名称,命令数据
            if (empty($rule->rule_command) === false) {
                $ruleStr = '<div class="form-group 1"><label for="rule" class="col-sm-2 control-label">规则名称</label><div class="col-sm-8"><div class="input-group"><span class="input-group-addon"><i class="fa fa-pencil"></i></span><input data-ruleid="' . $rule->id . '" type="text" id="rule" name="rule" value="' . $rule->rule_command . '" class="form-control"/></div><span class="help-block"><i class="fa fa-info-circle"></i>&nbsp;文件位置: ' . $rule->file_path . '</span></div></div>';
            }

            $str .= $argStr . $ruleStr . '</div>';
        }
        return $str;
    }

    /**
     * 第一步运行列表页命令
     */
    public function _command($ruleId, $args)
    {
        set_time_limit(0);
        $str = '';
        //利用$ruleId来获得当前规则内容
        $ruleObj = Rule::where('id', $ruleId)->first();
        //数据库中保存的参数
        $argsDb = explode(',', $ruleObj->rule_args);
        $args = parse_url($args, PHP_URL_QUERY);
        $args = explode('&', $args);
        $argsRel = array();
        foreach ($args as $k => $v) {
            list($ak, $av) = explode('=', $v);
            $argsRel[$ak] = $av;
        }

        $argsFinal = [];
        foreach ($argsDb as $key => $value) {
            if (stripos($value, '?') === false && (in_array($value, array_keys($argsRel)) === false || empty($argsRel[$value]))) {
                $str = '列表命令参数不正确 ' . $value . ' 请重新提交!';
                return $str;
            }

            //得到参数,下标所对应的值,$argsRel是指表单提交过来的参数列表
            if (stripos($value, '?') !== false) {
                $tmpValue = str_replace('?', '', $value);
                $relValue = str_replace('?', '_nmust', $value);
                $argsFinal[$tmpValue] = $argsRel[$relValue];
            } elseif ($value == '--queue') {
                $argsFinal[$value] = '--queue=' . $argsRel[$value];
            } else {
                $argsFinal[$value] = $argsRel[$value];
            }
        }
        $command = implode(' ', $argsFinal);
        $command = trim($ruleObj->rule_command) . ' ' . trim($command);
//        dd($command);
        $str = $this->runArtisan($command);
        return $str;
    }

    public function runArtisan($command)
    {
        // If Exception raised.
        if (1 === Artisan::handle(
                new ArgvInput(explode(' ', 'artisan ' . trim($command))),
                $output = new StringOutput2()
            )
        ) {
            return $output->getContent();
        }

        return sprintf('<pre>%s</pre>', $output->getContent());
    }


}


class StringOutput2 extends Output
{
    public $output = '';

    public function clear()
    {
        $this->output = '';
    }

    protected function doWrite($message, $newline)
    {
        $this->output .= $message . ($newline ? "\n" : '');
    }

    public function getContent()
    {
        return trim($this->output);
    }
}
