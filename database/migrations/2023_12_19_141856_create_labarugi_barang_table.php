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
        Schema::create('labarugi_barang', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('barang_id')->nullable();
            $table->unsignedBigInteger('labarugi_id')->nullable();

            $table->foreign('barang_id')->references('id')->on('barang')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('labarugi_id')->references('id')->on('labarugi')->onDelete('cascade')->onUpdate('cascade');
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
        Schema::dropIfExists('labarugi_barang');
    }
};
