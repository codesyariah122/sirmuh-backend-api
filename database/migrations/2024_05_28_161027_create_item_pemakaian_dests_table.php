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
        Schema::create('itempemakaiandest', function (Blueprint $table) {
            $table->id();
            $table->string('kode_pemakaian')->nullable();
            $table->string('barang')->nullable();
            $table->decimal('qty', 15,2)->nullable();
            $table->decimal('harga', 15,2)->nullable();
            $table->decimal('total', 15,2)->nullable();
            $table->string('supplier')->nullable();
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
        Schema::dropIfExists('itempemakaiandest');
    }
};
