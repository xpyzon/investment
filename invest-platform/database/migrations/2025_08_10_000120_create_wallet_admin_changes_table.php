<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_admin_changes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('wallet_admin_id');
            $table->unsignedBigInteger('admin_id');
            $table->string('change_type', 50);
            $table->json('change_payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('wallet_admin_id')->references('id')->on('wallet_admin')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_admin_changes');
    }
};