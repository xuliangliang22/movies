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
     * 与下载链接表,ca_gurls,一对多
     */
    public function gurls()
    {
        return $this->hasMany('App\Models\Gurl','arctype_id','id');
    }

}
