<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNeededOrders extends Migration
{
    public function up(): void
    {
        // 1) Add the new columns (all nullable or with defaults so SQLite will accept them)
        Schema::table('orders', function (Blueprint $table) {


            $table->integer('quantity')
                  ->default(1)
                  ->after('product_id');

            $table->boolean('delivery')
                  ->default(false)
                  ->after('quantity');

            $table->timestamp('delivery_date')
                  ->nullable()
                  ->after('delivery');
        });

        // 2) Add the foreign key constraint in a separate call
        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('product_id')
                  ->references('id')
                  ->on('products')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn([
                'product_id',
                'quantity',
                'delivery',
                'delivery_date',
            ]);
        });
    }
}