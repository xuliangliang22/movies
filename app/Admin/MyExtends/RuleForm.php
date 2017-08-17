<?php

namespace App\Admin\MyExtends;

use Encore\Admin\Widgets\Form;

class RuleForm extends Form
{
    public function render()
    {
        return view('rule.form', $this->getVariables())->render();
    }
}












