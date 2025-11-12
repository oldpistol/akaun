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
        Schema::table('quotations', function (Blueprint $table) {
            // Rename discount_percentage to discount_rate
            $table->renameColumn('discount_percentage', 'discount_rate');
            // Add converted_at timestamp
            $table->timestamp('converted_at')->nullable()->after('declined_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            // Revert changes
            $table->renameColumn('discount_rate', 'discount_percentage');
            $table->dropColumn('converted_at');
        });
    }
};
