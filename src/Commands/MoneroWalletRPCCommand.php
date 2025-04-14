<?php

namespace Mollsoft\LaravelMoneroModule\Commands;

use Illuminate\Console\Command;
use Mollsoft\LaravelMoneroModule\Services\WalletRPCInstaller;

class MoneroWalletRPCCommand extends Command
{
    protected $signature = 'monero:wallet-rpc';

    protected $description = 'Download and install monero-wallet-rpc binary based on the current OS';

    public function handle(WalletRPCInstaller $service): void
    {
        $service->setLogger(fn(string $message, ?string $type) => $this->{$type ? ($type === 'success' ? 'info' : $type) : 'line'}($message));

        $service->run();
    }
}
