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
        Schema::create('pemakaian_barangs', function (Blueprint $table) {
            $table->id();
            $table->string('kode');
            $table->timestamp('tanggal');
            $table->string('barang_asal')->nullable();
            $table->decimal('qty', 15,2)->nullable();
            $table->string('barang_tujuan')->nullable();
            $table->string('keperluan')->nullable();
            $table->string('keterangan')->nullable();
            $table->string('operator')->nullable();
            $table->timestamp('deleted_at')->nullable();
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
        Schema::dropIfExists('pemakaian_barangs');
    }
};
