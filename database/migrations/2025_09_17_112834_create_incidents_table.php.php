<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIncidentsTable extends Migration
{
    public function up()
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shipment_id')->nullable();
            $table->string('type')->nullable(); // e.g. "Delay", "Damage", "Customs"
            $table->text('message');
            $table->string('severity')->default('medium'); // low, medium, high
            $table->unsignedBigInteger('reported_by')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('shipment_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('incidents');
    }
}
