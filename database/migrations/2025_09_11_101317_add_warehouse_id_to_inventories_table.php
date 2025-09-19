<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            // nullable at first (to avoid breaking existing rows)
            $table->unsignedBigInteger('warehouse_id')->nullable()->after('product_id');

            // If you already have warehouses table:
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn('warehouse_id');
        });
    }
};
