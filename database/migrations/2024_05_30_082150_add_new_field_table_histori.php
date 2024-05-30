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
        Schema::table('histori', function (Blueprint $table) {
            $table->string('user')->collation('utf8mb4_unicode_ci')->after('tanggal')->nullable();
            $table->string('routes')->after('keterangan')->nullable();
            $table->string('route_name')->after('routes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('histori', function (Blueprint $table) {
            $table->dropColumn('user');
            $table->dropColumn('routes');
        });
    }
};
