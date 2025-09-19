<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Because SQLite doesn't allow altering a column nullability, we create a new table,
     * copy rows, drop the old table and rename the new one.
     */
    public function up(): void
    {
        if (Schema::hasColumn('orders', 'supplier_id')) {
            // create new table with the desired schema
            Schema::create('orders_new', function (Blueprint $table) {
                $table->id();
                $table->foreignId('buyer_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
                $table->string('status')->default('Pending');
                $table->dateTime('order_date')->nullable();
                $table->dateTime('expected_date')->nullable();
                $table->decimal('total', 12, 2)->default(0);
                $table->timestamps();
            });

            // copy existing data if any
            $rows = DB::table('orders')->get();
            foreach ($rows as $r) {
                DB::table('orders_new')->insert([
                    'id' => $r->id,
                    'buyer_id' => $r->buyer_id ?? null,
                    'supplier_id' => $r->supplier_id ?? null,
                    'status' => $r->status ?? 'Pending',
                    'order_date' => $r->order_date ?? null,
                    'expected_date' => $r->expected_date ?? null,
                    'total' => $r->total ?? 0,
                    'created_at' => $r->created_at ?? now(),
                    'updated_at' => $r->updated_at ?? now(),
                ]);
            }

            Schema::drop('orders');
            Schema::rename('orders_new', 'orders');
        }
    }

    public function down(): void
    {
        // best-effort rollback: recreate original table with NOT NULL supplier_id
        if (! Schema::hasColumn('orders', 'supplier_id')) {
            Schema::create('orders_old', function (Blueprint $table) {
                $table->id();
                $table->foreignId('buyer_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('supplier_id')->constrained('suppliers');
                $table->string('status')->default('Pending');
                $table->dateTime('order_date')->nullable();
                $table->dateTime('expected_date')->nullable();
                $table->decimal('total',12,2)->default(0);
                $table->timestamps();
            });

            $rows = DB::table('orders')->get();
            foreach ($rows as $r) {
                DB::table('orders_old')->insert([
                    'id' => $r->id,
                    'buyer_id' => $r->buyer_id ?? null,
                    // If supplier_id was null, set to 0 to avoid NOT NULL issues; you may change this behaviour.
                    'supplier_id' => $r->supplier_id ?? 0,
                    'status' => $r->status ?? 'Pending',
                    'order_date' => $r->order_date ?? null,
                    'expected_date' => $r->expected_date ?? null,
                    'total' => $r->total ?? 0,
                    'created_at' => $r->created_at ?? now(),
                    'updated_at' => $r->updated_at ?? now(),
                ]);
            }

            Schema::drop('orders');
            Schema::rename('orders_old', 'orders');
        }
    }
};
