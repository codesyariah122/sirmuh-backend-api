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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('kode_po')->nullable();
            $table->integer('dp_awal')->nullable();
            $table->string('po_ke')->nullable();
            $table->integer('qty')->nullable();
            $table->string('nama_barang')->nullable();
            $table->string('kode_barang')->nullable();
            $table->integer('harga_satuan')->nullable();
            $table->string('subtotal')->nullable();
            $table->integer('sisa_dp')->nullable();
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
        Schema::dropIfExists('purchase_orders');
    }
};
