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
        Schema::create('log_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('order_id')->unique();
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->string('status');
            $table->string('type');
            $table->text('payload');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_transactions');
    }
};
