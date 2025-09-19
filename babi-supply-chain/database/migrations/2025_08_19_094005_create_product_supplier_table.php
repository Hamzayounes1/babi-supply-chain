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
    Schema::create('product_supplier', function (Blueprint $table) {
        $table->id();
        $table->foreignId('product_id')->constrained()->onDelete('cascade');
        $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
        $table->decimal('cost_price', 12, 2);
        $table->integer('lead_time_days')->default(0);
        $table->timestamps();
        $table->unique(['product_id', 'supplier_id']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_supplier');
    }
};
