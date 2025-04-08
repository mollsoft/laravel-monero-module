<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mollsoft\LaravelMoneroModule\Models\MoneroAccount;
use Mollsoft\LaravelMoneroModule\Models\MoneroAddress;
use Mollsoft\LaravelMoneroModule\Models\MoneroWallet;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('monero_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(MoneroWallet::class, 'wallet_id')
                ->constrained('monero_wallets')
                ->cascadeOnDelete();
            $table->foreignIdFor(MoneroAccount::class, 'account_id')
                ->constrained('monero_accounts')
                ->cascadeOnDelete();
            $table->foreignIdFor(MoneroAddress::class, 'address_id')
                ->constrained('monero_addresses')
                ->cascadeOnDelete();
            $table->string('txid');
            $table->decimal('amount', 30, 12);
            $table->integer('block_height')
                ->nullable();
            $table->integer('confirmations');
            $table->timestamp('time_at');
            $table->timestamps();

            $table->unique(['address_id', 'txid'], 'unique_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monero_deposits');
    }
};
