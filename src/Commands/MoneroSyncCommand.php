<?php

namespace Mollsoft\LaravelMoneroModule\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Mollsoft\LaravelMoneroModule\Models\MoneroWallet;
use Mollsoft\LaravelMoneroModule\Services\SyncService;

class MoneroSyncCommand extends Command
{
    protected $signature = 'monero:sync';

    protected $description = 'Monero sync wallets';

    public function handle(): void
    {
        /** @var class-string<MoneroWallet> $model */
        $model = config('monero.models.wallet');

        $model::orderBy('id')
            ->each(function (MoneroWallet $wallet) {
                $this->info("Monero Wallet $wallet->name starting sync...");

                try {
                    App::make(SyncService::class, [
                        'wallet' => $wallet
                    ])->run();

                    $this->info("Monero Wallet $wallet->name successfully sync finished!");
                }
                catch(\Exception $e) {
                    $this->error("Error: {$e->getMessage()}");
                }
            });
    }
}
