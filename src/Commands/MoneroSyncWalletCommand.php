<?php

namespace Mollsoft\LaravelMoneroModule\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Mollsoft\LaravelMoneroModule\Models\MoneroWallet;
use Mollsoft\LaravelMoneroModule\Services\SyncService;

class MoneroSyncWalletCommand extends Command
{
    protected $signature = 'monero:sync-wallet {wallet_id}';

    protected $description = 'Sync Monero Wallet';

    public function handle(): void
    {
        $walletId = $this->argument('wallet_id');

        /** @var class-string<MoneroWallet> $model */
        $model = config('monero.models.wallet');
        $wallet = $model::findOrFail($walletId);

        $this->info("Monero Wallet $wallet->name starting sync...");

        App::make(SyncService::class, [
            'wallet' => $wallet
        ])->run();

        $this->info("Monero Wallet $wallet->name successfully sync finished!");
    }
}
