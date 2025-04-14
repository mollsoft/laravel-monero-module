<?php

namespace Mollsoft\LaravelMoneroModule\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\App;
use Mollsoft\LaravelMoneroModule\Facades\Monero;
use Mollsoft\LaravelMoneroModule\Models\MoneroWallet;
use Mollsoft\LaravelMoneroModule\Services\Sync\WalletSync;

class MoneroWalletSyncCommand extends Command
{
    protected $signature = 'monero:wallet-sync {wallet_id}';

    protected $description = 'Monero sync wallet process';

    public function handle(): void
    {
        $walletId = $this->argument('wallet_id');
        /** @var MoneroWallet $wallet */
        $wallet = Monero::getModelWallet()::findOrFail($walletId);

        try {
            Monero::nodeAtomicLock($wallet->node, fn() => $this->runSync($wallet), 5);
        }
        catch(LockTimeoutException) {
            $this->warn("Node сейчас занята другим процессом");
        }
    }

    protected function runSync(MoneroWallet $wallet): void
    {
        $service = App::make(WalletSync::class, compact('wallet'));
        $service
            ->setLogger(fn(string $message, ?string $type) => $this->{$type ? ($type === 'success' ? 'info' : $type) : 'line'}($message))
            ->run();
    }
}
