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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->morphs('addressable');
            $table->string('label', 50)->nullable();
            $table->string('line1', 120);
            $table->string('line2', 120)->nullable();
            $table->string('city', 80);
            $table->string('postcode', 10);
            $table->foreignId('state_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->char('country_code', 2)->default('MY');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['postcode', 'is_primary']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
