<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->json('items'); // array of {product_id, name, qty, price}
            $table->integer('quantity')->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('status')->default('created'); // created, processing, shipped, delivered, cancelled, delayed
            $table->date('expected_delivery')->nullable();
            $table->boolean('notified_delay')->default(false);
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
