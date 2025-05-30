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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('transaction_id')->nullable(); // Ditusi transaction ID
            $table->string('reference_id')->unique(); // Internal reference ID
            $table->string('product_code');
            $table->string('product_name')->nullable();
            $table->integer('amount');
            $table->decimal('price', 10, 2);
            $table->enum('status', ['PENDING', 'PROCESS', 'SUCCESS', 'EXPIRED', 'REJECTED'])->default('PENDING');
            $table->json('additional_information')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
