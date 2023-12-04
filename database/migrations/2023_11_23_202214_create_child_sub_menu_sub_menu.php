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
        Schema::create('child_sub_menu_sub_menu', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sub_menu_id')->nullable();
            $table->unsignedBigInteger('child_sub_menu_id')->nullable();

            $table->foreign('sub_menu_id')->references('id')->on('sub_menus')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('child_sub_menu_id')->references('id')->on('child_sub_menus')->onDelete('cascade')->onUpdate('cascade');
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
        Schema::dropIfExists('child_sub_menu_sub_menu');
    }
};
