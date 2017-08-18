<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rule extends Model
{
    protected $table = 'ca_rules';
    /**
     * 与分类表关联,一对多
     */
    public function arctypes()
    {
        return $this->belongsTo('App\Models\Arctype', 'type_id', 'id');
    }

}
