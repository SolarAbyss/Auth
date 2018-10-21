<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProfileTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('username');
            $table->string('country')->nullable();
            $table->string('organization_name')->nullable();
            $table->string('throughfare')->nullable(); // Street Address 1 
            $table->string('throughfare_2')->nullable(); // Street Address 2 
            $table->string('throughfare_3')->nullable(); // Street Address 3
            $table->string('premise')->nullable(); // Apartment, Suite, Boxnumber, etc.
            $table->string('sub_premise')->nullable(); // Apartment, Suite, Boxnumber, etc.
            $table->string('locality')->nullable(); // City / Town
            $table->string('dependent_locality')->nullable();
            $table->string('administrative_area')->nullable(); // State / Province / Region (ISO code when available)
            $table->string('postal_code')->nullable(); // Postal Code / Zip Code
            $table->string('division')->nullable();
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
        Schema::dropIfExists('profiles');
    }
}
