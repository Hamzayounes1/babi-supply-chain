<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProductIdToOrdersTable extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // 1) add a nullable unsignedBigInteger column
            $table
                ->unsignedBigInteger('product_id')
                ->nullable()
                ->after('id');
        });

        // 2) then add the FK constraint in a separate call
        Schema::table('orders', function (Blueprint $table) {
            $table
                ->foreign('product_id')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
        });
    }
}