<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTotalValueToShipments extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('shipments')) return;

        if (! Schema::hasColumn('shipments', 'total_value')) {
            Schema::table('shipments', function (Blueprint $table) {
                $table->decimal('total_value', 14, 2)->default(0)->after('destination');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('shipments', 'total_value')) {
            Schema::table('shipments', function (Blueprint $table) {
                $table->dropColumn('total_value');
            });
        }
    }
}
