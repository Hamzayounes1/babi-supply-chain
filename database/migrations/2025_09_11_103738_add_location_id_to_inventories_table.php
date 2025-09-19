<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

 class AddLocationIdToInventoriesTable extends Migration
{
    public function up()
    {
        Schema::table('inventories', function (Blueprint $table) {
            if (! Schema::hasColumn('inventories', 'warehouse_id')) {
                $table->unsignedBigInteger('warehouse_id')->nullable()->after('id');
                $table->string('location')->nullable()->after('warehouse_id'); // ex: "A1-02"
                $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
            }
        });
    }

    public function down()
    {
        Schema::table('inventories', function (Blueprint $table) {
            if (Schema::hasColumn('inventories', 'warehouse_id')) {
                $table->dropForeign(['warehouse_id']);
                $table->dropColumn(['warehouse_id', 'location']);
            }
        });
    }
}
