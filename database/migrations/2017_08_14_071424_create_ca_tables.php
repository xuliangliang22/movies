<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCaTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $connection = config('admin.database.connection') ?: config('database.default');
        //分类表
        if (!Schema::hasTable('ca_arctypes')) {
            //
            Schema::connection($connection)->create('ca_arctypes', function (Blueprint $table) {
                $table->increments('id');
                $table->tinyInteger('dede_id')->default(0)->comment('对应的dede表中栏目id');
                $table->string('dede_typename')->default('')->comment('对应的dede表中的栏目名称');
                $table->timestamps();
                $table->index('dede_id');
            });
        }

        //分类表
        if (!Schema::hasTable('ca_sites')) {
            //
            Schema::connection($connection)->create('ca_sites', function (Blueprint $table) {
                $table->increments('id');
                $table->string('site_name')->default('')->comment('网站名称');
                $table->timestamps();
                $table->unique('site_name');
            });
        }

        //中间表
        //分类表
        if (!Schema::hasTable('ca_arctype_site')) {
            //
            Schema::connection($connection)->create('ca_arctype_site', function (Blueprint $table) {
                $table->tinyInteger('type_id')->comment('分类id');
                $table->integer('site_id')->comment('网站id');
                $table->timestamps();
                $table->index('type_id');
                $table->index('site_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        $connection = config('admin.database.connection') ?: config('database.default');

        Schema::connection($connection)->dropIfExists('ca_arctypes');
        Schema::connection($connection)->dropIfExists('ca_sites');
        Schema::connection($connection)->dropIfExists('ca_arctype_site');
    }
}
