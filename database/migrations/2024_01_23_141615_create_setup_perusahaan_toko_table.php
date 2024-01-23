<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('setup_perusahaan_toko', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('setup_perusahaan_id')->nullable();
            $table->unsignedBigInteger('toko_id')->nullable();

            $table->foreign('setup_perusahaan_id')->references('id')->on('setup_perusahaan')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('toko_id')->references('id')->on('tokos')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('setup_perusahaan_toko');
    }
};
