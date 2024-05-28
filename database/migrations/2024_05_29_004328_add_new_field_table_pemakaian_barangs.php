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
        Schema::table('pemakaian_barangs', function (Blueprint $table) {
            $table->decimal('harga_proses', 15,2)->after('total')->nullable();
            $table->decimal('biaya_operasional', 15,2)->after('total')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pemakaian_barangs', function (Blueprint $table) {
            $table->dropColumn('harga_proses');
            $table->dropColumn('biaya_operasional');
        });
    }
};
