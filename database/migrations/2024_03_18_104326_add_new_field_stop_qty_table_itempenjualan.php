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
        Schema::table('itempenjualan', function (Blueprint $table) {
            $table->string('stop_qty')->after('last_qty')->default('False')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('itempenjualan', function (Blueprint $table) {
            $table->dropColumn('last_qty');
        });
    }
};
