<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mollsoft\LaravelMoneroModule\Models\MoneroNode;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('monero_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(MoneroNode::class, 'node_id')
                ->constrained('monero_nodes')
                ->cascadeOnDelete();
            $table->string('name')
                ->unique();
            $table->string('title')
                ->nullable();
            $table->text('password')
                ->nullable();
            $table->text('mnemonic');
            $table->decimal('balance', 30, 12)
                ->nullable();
            $table->decimal('unlocked_balance', 30, 12)
                ->nullable();
            $table->timestamp('sync_at')
                ->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monero_wallets');
    }
};
