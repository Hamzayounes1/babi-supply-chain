<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::table('suppliers', function (Blueprint $table) {
        $table->integer('performance_score')->nullable();
        $table->integer('on_time_percentage')->nullable();
    });
}

public function down()
{
    Schema::table('suppliers', function (Blueprint $table) {
        $table->dropColumn(['performance_score', 'on_time_percentage']);
    });
}

};
