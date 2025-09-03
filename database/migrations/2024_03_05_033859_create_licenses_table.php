<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
 * Run the migrations.
 */
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // internal name for licneses
            $table->string('product'); // ties to gumroad product
            $table->json('years'); // comma separated list of years, a single year, or 0 for lifetime
            $table->timestamps();
        });
    }

    /**
 * Reverse the migrations.
 */
    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
