<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReceiptItemsTable extends Migration
{
    public function up()
    {
        Schema::create('receipt_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('receipt_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity')->default(0);
            $table->decimal('unit_cost', 12, 4)->default(0);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('receipt_id')->references('id')->on('receipts')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::dropIfExists('receipt_items');
    }
}
