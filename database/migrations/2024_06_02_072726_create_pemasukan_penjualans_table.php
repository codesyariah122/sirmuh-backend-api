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
        Schema::create('pemasukan_penjualans', function (Blueprint $table) {
            $table->id();
            $table->timestamp('tanggal')->nullable();
            $table->string('kd_transaksi')->nullable();
            $table->longText('keterangan')->nullable();
            $table->string('kode_kas')->nullable();
            $table->decimal('jumlah', 15,2)->default(0.0)->nullable();
            $table->string('operator')->nullable();
            $table->string('pelanggan')->nullable();
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
        Schema::dropIfExists('pemasukan_penjualans');
    }
};
