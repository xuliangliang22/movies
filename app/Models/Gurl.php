<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gurl extends Model
{
    //
    protected $table = 'ca_gurls';

    /**
     * 与分类表ca_arctypes,一对多反向
     */
    public function arctypes()
    {
        return $this->belongsTo('App\Models\Arctype', 'arctype_id', 'id');
    }

    /**
     * 与规则表的关系ca_rules,一对一
     */
    public function rules()
    {
        return $this->hasOne('App\Models\Rule', 'gurl_id', 'id');
    }
}
