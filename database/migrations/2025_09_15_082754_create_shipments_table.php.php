<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShipmentsTable extends Migration
{
    public function up()
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('prepared_by')->nullable();
            $table->string('reference')->nullable();
            $table->string('status')->default('Prepared'); // Prepared, Shipped, Delivered, Cancelled
            $table->text('destination')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamps();

            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
            $table->foreign('prepared_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('shipments');
    }
}
