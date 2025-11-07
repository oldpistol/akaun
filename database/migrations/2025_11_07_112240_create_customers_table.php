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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 150);
            $table->string('email', 191)->nullable()->unique();
            $table->string('phone_primary', 20)->unique();
            $table->string('phone_secondary', 20)->nullable();
            $table->string('nric', 14)->nullable()->unique();
            $table->string('passport_no', 20)->nullable()->unique();
            $table->string('company_ssm_no', 20)->nullable()->unique();
            $table->string('gst_number', 25)->nullable()->unique();
            $table->string('customer_type', 20);
            $table->boolean('is_active')->default(true);
            $table->string('billing_attention', 120)->nullable();
            $table->decimal('credit_limit', 12, 2)->nullable();
            $table->string('risk_level', 20)->nullable();
            $table->text('notes')->nullable();
            $table->string('address_line1', 120);
            $table->string('address_line2', 120)->nullable();
            $table->string('city', 80);
            $table->string('postcode', 10);
            $table->string('state', 30);
            $table->char('country_code', 2)->default('MY');
            $table->timestamp('email_verified_at')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['customer_type', 'is_active']);
            $table->index('state');
            $table->index('postcode');
            $table->index('risk_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
