<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddProviderSupport extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('provider_id')->after('id')->unsigned();
            $table->integer('profile_id')->after('provider_id')->unsigned();
            $table->text('refresh_token')->nullable();
            $table->dropColumn('password');
            $table->dropColumn('name');

            $table->foreign('profile_id')
                ->references('id')
                ->on('profiles')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function($table) {
            $table->dropColumn('provider_id');
            $table->dropColumn('profile_id');
            $table->dropColumn('refresh_token');
        });
    }
}
