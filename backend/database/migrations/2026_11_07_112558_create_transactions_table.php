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
            $table->date('date')->index();
            $table->foreignId('coa_id')->constrained('coa')->restrictOnDelete();
            $table->foreignId('wallet_id')->constrained('wallets')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('reference_no', 100)->nullable();
            $table->string('description')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->enum('status', ['draft', 'posted', 'void'])->default('posted');
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->timestamps();

            $table->index('coa_id');
            $table->index('wallet_id');
            $table->index('status');
        });

        DB::statement('ALTER TABLE transactions ADD CONSTRAINT transactions_debit_credit_check
            CHECK ((debit > 0 AND credit = 0) OR (credit > 0 AND debit = 0))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
