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
        Schema::table('pembayaran_angsuran', function (Blueprint $table) {
            $table->string('operator')->after('tanggal')->nullable();
            $table->longText('keterangan')->after('jumlah')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pembayaran_angsuran', function (Blueprint $table) {
            $table->dropColumn('operator');
            $table->dropColumn('keterangan');
        });
    }
};
