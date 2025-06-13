<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flares', function (Blueprint $table) {
            $table->uuid('known_place_id')->nullable()->after('id');
            
            $table->foreign('known_place_id')
                  ->references('id')
                  ->on('known_places')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('flares', function (Blueprint $table) {
            $table->dropForeign(['known_place_id']);
            $table->dropColumn('known_place_id');
        });
    }
};