<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposits', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->decimal('amount', 24, 8);
            $table->string('currency', 10);
            $table->string('txid', 191)->unique();
            $table->string('address', 255)->nullable();
            $table->string('network', 50)->nullable();
            $table->enum('status', ['pending', 'confirmed', 'rejected'])->default('pending');
            $table->decimal('fees', 24, 8)->default(0);
            $table->integer('confirmations')->default(0);
            $table->unsignedBigInteger('wallet_admin_id')->nullable();
            $table->timestamps();

            $table->foreign('wallet_admin_id')->references('id')->on('wallet_admin')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposits');
    }
};