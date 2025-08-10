<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_admin', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('currency', 10);
            $table->string('network', 50);
            $table->text('address_template')->nullable();
            $table->boolean('requires_tag')->default(false);
            $table->string('tag_label', 50)->nullable();
            $table->integer('confirmations')->default(3);
            $table->string('icon_url', 255)->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_admin');
    }
};