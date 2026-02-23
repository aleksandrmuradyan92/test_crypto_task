<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crypto_balance_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('currency', 20);
            $table->string('type', 20); // credit, debit
            $table->decimal('amount', 24, 8)->unsigned();
            $table->decimal('balance_after', 24, 8);
            $table->string('reference', 100)->nullable(); // withdrawal, payment, fee, deposit
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->timestamps();

            $table->index(['user_id', 'currency', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crypto_balance_ledger');
    }
};
