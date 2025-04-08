<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mollsoft\LaravelMoneroModule\Models\MoneroAccount;
use Mollsoft\LaravelMoneroModule\Models\MoneroWallet;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('monero_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(MoneroWallet::class, 'wallet_id')
                ->constrained('monero_wallets')
                ->cascadeOnDelete();
            $table->foreignIdFor(MoneroAccount::class, 'account_id')
                ->constrained('monero_accounts')
                ->cascadeOnDelete();
            $table->string('address');
            $table->integer('address_index');
            $table->string('title')
                ->nullable();
            $table->decimal('balance', 30, 12)
                ->nullable();
            $table->decimal('unlocked_balance', 30, 12)
                ->nullable();
            $table->timestamps();

            $table->unique(['account_id', 'address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monero_addresses');
    }
};
