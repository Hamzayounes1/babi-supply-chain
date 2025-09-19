<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShipmentItemsTable extends Migration
{
    public function up()
    {
        Schema::create('shipment_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shipment_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity')->default(0);
            $table->decimal('unit_cost', 12, 4)->default(0);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('shipment_id')->references('id')->on('shipments')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::dropIfExists('shipment_items');
    }
}
