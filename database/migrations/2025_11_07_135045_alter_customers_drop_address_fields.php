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
        Schema::table('customers', function (Blueprint $table) {
            // Drop indexes that reference the columns first
            $table->dropIndex(['state']);
            $table->dropIndex(['postcode']);

            // Drop legacy embedded address columns
            $table->dropColumn([
                'address_line1',
                'address_line2',
                'city',
                'postcode',
                'state',
                'country_code',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Recreate legacy embedded address columns
            $table->string('address_line1', 120);
            $table->string('address_line2', 120)->nullable();
            $table->string('city', 80);
            $table->string('postcode', 10);
            $table->string('state', 30);
            $table->char('country_code', 2)->default('MY');

            // Recreate indexes
            $table->index('state');
            $table->index('postcode');
        });
    }
};
