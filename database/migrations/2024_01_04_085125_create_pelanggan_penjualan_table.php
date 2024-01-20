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
        Schema::create('pelanggan_penjualan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pelanggan_id')->nullable();
            $table->unsignedBigInteger('penjualan_id')->nullable();

            $table->foreign('pelanggan_id')->references('id')->on('pelanggan')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('penjualan_id')->references('id')->on('penjualan')->onDelete('cascade')->onUpdate('cascade');
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
        Schema::dropIfExists('pelanggan_penjualan');
    }
};
