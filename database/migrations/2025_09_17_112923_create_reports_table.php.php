<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportsTable extends Migration
{
    public function up()
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shipment_id')->nullable();
            $table->string('title');
            $table->json('payload')->nullable(); // arbitrary JSON report content
            $table->unsignedBigInteger('created_by')->nullable();
            $table->json('shared_with')->nullable(); // e.g. [{"type":"email","to":"x@..."}]
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('reports');
    }
}
