<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('buyer_id')->nullable();
    $table->unsignedBigInteger('supplier_id')->nullable();
    $table->string('title');
    $table->text('terms')->nullable();
    $table->date('start_date')->nullable();
    $table->date('end_date')->nullable();
    $table->decimal('value', 12, 2)->nullable();
    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buyer_contract');
    }
};
