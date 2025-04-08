<?php

namespace Mollsoft\LaravelMoneroModule\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Mollsoft\LaravelMoneroModule\Facades\Monero;
use Mollsoft\LaravelMoneroModule\Models\MoneroWallet;
use Mollsoft\LaravelMoneroModule\Services\SyncService;

class MoneroSyncCommand extends Command
{
    protected $signature = 'monero:sync';

    protected $description = 'Monero sync wallets';

    public function handle(): void
    {
        $model = Monero::getModelWallet();

        $model::orderBy('id')
            ->each(function (MoneroWallet $wallet) {
                $this->info("Начинаем синхронизацию кошелька $wallet->name...");

                try {
                    $service = App::make(SyncService::class, [
                        'wallet' => $wallet
                    ]);

                    $service->setLogger(fn(string $message, ?string $type) => $this->{$type ? ($type === 'success' ? 'info' : $type) : 'line'}($message));

                    $service->run();

                    $this->info("Кошелек $wallet->name успешно синхронизирован!");
                }
                catch(\Exception $e) {
                    $this->error("Ошибка: {$e->getMessage()}");
                }
            });
    }
}
