<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_wallets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('wallet_admin_id');
            $table->string('deposit_address', 255)->nullable();
            $table->string('deposit_tag', 100)->nullable();
            $table->boolean('address_generated')->default(false);
            $table->enum('status', ['active', 'disabled'])->default('active');
            $table->timestamps();

            $table->unique(['user_id', 'wallet_admin_id'], 'user_wallet_unique');
            $table->foreign('wallet_admin_id')->references('id')->on('wallet_admin')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_wallets');
    }
};