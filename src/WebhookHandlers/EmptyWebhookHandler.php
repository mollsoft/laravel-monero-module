<?php

namespace Mollsoft\LaravelMoneroModule\WebhookHandlers;

use Mollsoft\LaravelMoneroModule\Models\MoneroAddress;
use Illuminate\Support\Facades\Log;
use Mollsoft\LaravelMoneroModule\Models\MoneroAccount;
use Mollsoft\LaravelMoneroModule\Models\MoneroDeposit;
use Mollsoft\LaravelMoneroModule\Models\MoneroIntegratedAddress;
use Mollsoft\LaravelMoneroModule\Models\MoneroWallet;

class EmptyWebhookHandler implements WebhookHandlerInterface
{
    public function handle(MoneroDeposit $deposit): void {
        Log::error('Monero Wallet '.$deposit->wallet->name.', account '.$deposit->account->base_address.', address '.$deposit->address->address);
    }
}