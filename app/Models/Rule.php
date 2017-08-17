<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rule extends Model
{
    protected $table = 'ca_rules';
    /**
     * 与采集链接表关联,一对一
     */
    public function gurls()
    {
        return $this->belongsTo('App\Models\Gurl', 'gurl_id', 'id');
    }

}
