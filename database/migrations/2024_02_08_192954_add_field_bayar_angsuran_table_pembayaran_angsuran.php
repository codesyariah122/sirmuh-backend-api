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
            $table->decimal('bayar_angsuran')->after('kode_faktur')->nullable();
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
            $table->dropColumn('bayar_angsuran');
        });
    }
};
