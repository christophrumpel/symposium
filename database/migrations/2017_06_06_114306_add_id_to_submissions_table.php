<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdToSubmissionsTable extends Migration
{
    public function up()
    {
        Schema::table('submissions', function (Blueprint $table) {
            /**
             * @todo
             * Made this nullable so the migrations wouldn't freak out.
             * Will need to remove `nullable()` once we determine the best way
             * to deal with the 'cannot add PRIMARY KEY' error.
             */
            $table->string('id', 36)->unique()->nullable();
        });
    }

    public function down()
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn('id');
        });
    }
}
