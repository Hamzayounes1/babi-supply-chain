<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWarehouseAndLocationToInventories extends Migration
{
    public function up()
    {
        Schema::table('inventories', function (Blueprint $table) {
            // add warehouse foreign key (nullable for backwards compatibility)


            // add structured location pieces
            if (! Schema::hasColumn('inventories', 'location_aisle')) {
                $table->string('location_aisle')->nullable()->after('warehouse_id');
            }
            if (! Schema::hasColumn('inventories', 'location_row')) {
                $table->string('location_row')->nullable()->after('location_aisle');
            }
            if (! Schema::hasColumn('inventories', 'location_shelf')) {
                $table->string('location_shelf')->nullable()->after('location_row');
            }
            if (! Schema::hasColumn('inventories', 'location_bin')) {
                $table->string('location_bin')->nullable()->after('location_shelf');
            }

            // human-friendly label for quick display/search
            if (! Schema::hasColumn('inventories', 'location_label')) {
                $table->string('location_label')->nullable()->after('location_bin');
            }

            // add foreign key if warehouses table exists
            if (Schema::hasTable('warehouses')) {
                $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
            }
        });
    }

    public function down()
    {
        Schema::table('inventories', function (Blueprint $table) {
            if (Schema::hasColumn('inventories', 'warehouse_id')) {
                // drop FK first if present
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $doctrineTable = $sm->listTableDetails($table->getTable());
                if ($doctrineTable->hasForeignKey('inventories_warehouse_id_foreign')) {
                    $table->dropForeign(['warehouse_id']);
                }
                $table->dropColumn('warehouse_id');
            }
            foreach (['location_aisle','location_row','location_shelf','location_bin','location_label'] as $c) {
                if (Schema::hasColumn('inventories', $c)) $table->dropColumn($c);
            }
        });
    }
}
