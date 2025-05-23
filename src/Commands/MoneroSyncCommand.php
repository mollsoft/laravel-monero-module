<?php

namespace Mollsoft\LaravelMoneroModule\Commands;

use Illuminate\Console\Command;
use Mollsoft\LaravelMoneroModule\Services\Sync\MoneroSync;

class MoneroSyncCommand extends Command
{
    protected $signature = 'monero:sync';

    protected $description = 'Monero sync nodes & wallets';

    public function handle(MoneroSync $service): void
    {
        $service
            ->setLogger(fn(string $message, ?string $type) => $this->{$type ? ($type === 'success' ? 'info' : $type) : 'line'}($message))
            ->run();
    }
}
