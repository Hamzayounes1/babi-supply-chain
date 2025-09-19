<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * For SQLite we recreate the table with the desired schema.
     */
    public function up(): void
    {
        if (!Schema::hasTable('order_items')) {
            return;
        }

        // create new table with the desired schema
        Schema::create('order_items_new', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('product_name');
            $table->string('product_sku')->nullable();
            $table->integer('quantity')->default(1);
            // make unit_price nullable and default 0
            $table->decimal('unit_price', 12, 2)->nullable()->default(0);
            $table->timestamps();
        });

        // copy existing data
        $rows = DB::table('order_items')->get();
        foreach ($rows as $r) {
            DB::table('order_items_new')->insert([
                'id' => $r->id,
                'order_id' => $r->order_id,
                'product_name' => $r->product_name,
                'product_sku' => $r->product_sku,
                'quantity' => $r->quantity ?? 1,
                // if existing unit_price is null or empty, set 0
                'unit_price' => isset($r->unit_price) && $r->unit_price !== null ? $r->unit_price : 0,
                'created_at' => $r->created_at ?? now(),
                'updated_at' => $r->updated_at ?? now(),
            ]);
        }

        // drop old table & rename
        Schema::drop('order_items');
        Schema::rename('order_items_new', 'order_items');
    }

    public function down(): void
    {
        // rollback: try to create previous schema with NOT NULL unit_price (best-effort)
        if (!Schema::hasTable('order_items')) {
            return;
        }

        Schema::create('order_items_old', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('product_name');
            $table->string('product_sku')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 12, 2); // NOT NULL
            $table->timestamps();
        });

        $rows = DB::table('order_items')->get();
        foreach ($rows as $r) {
            DB::table('order_items_old')->insert([
                'id' => $r->id,
                'order_id' => $r->order_id,
                'product_name' => $r->product_name,
                'product_sku' => $r->product_sku,
                'quantity' => $r->quantity ?? 1,
                // replace null with 0 to satisfy NOT NULL during rollback
                'unit_price' => $r->unit_price ?? 0,
                'created_at' => $r->created_at ?? now(),
                'updated_at' => $r->updated_at ?? now(),
            ]);
        }

        Schema::drop('order_items');
        Schema::rename('order_items_old', 'order_items');
    }
};
