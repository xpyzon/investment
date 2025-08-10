<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('investor')->after('password');
            $table->string('kyc_status')->default('unverified')->after('role');
            $table->string('two_factor_secret')->nullable()->after('kyc_status');
            $table->boolean('is_active')->default(true)->after('two_factor_secret');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'kyc_status', 'two_factor_secret', 'is_active']);
        });
    }
};