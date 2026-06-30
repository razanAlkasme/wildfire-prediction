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
        Schema::table('fire_predictions', function (Blueprint $table) {
    $table->double('prediction')->nullable()->after('rainfall'); 
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fire_predictions', function (Blueprint $table) {
            //
        });
    }
};
