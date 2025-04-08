<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mollsoft\LaravelMoneroModule\Models\MoneroWallet;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('monero_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(MoneroWallet::class, 'wallet_id')
                ->constrained('monero_wallets')
                ->cascadeOnDelete();
            $table->string('base_address');
            $table->string('title')
                ->nullable();
            $table->tinyInteger('account_index');
            $table->decimal('balance', 30, 12)
                ->nullable();
            $table->decimal('unlocked_balance', 30, 12)
                ->nullable();
            $table->timestamps();

            $table->unique(['wallet_id', 'base_address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monero_accounts');
    }
};
