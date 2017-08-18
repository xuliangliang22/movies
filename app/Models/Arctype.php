<?php

namespace App\Models;

use Encore\Admin\Traits\AdminBuilder;
use Encore\Admin\Traits\ModelTree;
use Illuminate\Database\Eloquent\Model;


class Arctype extends Model
{
    //
    use ModelTree, AdminBuilder;

    protected $table = 'ca_arctypes';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setParentColumn('top_id');
        $this->setOrderColumn('sort');
        $this->setTitleColumn('dede_typename');
    }

    /**
     * 与规则表,ca_rules,一对多
     */
    public function rules()
    {
        return $this->hasMany('App\Models\Rule','type_id','id');
    }

}
