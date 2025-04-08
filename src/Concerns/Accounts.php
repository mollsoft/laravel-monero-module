<?php

namespace Mollsoft\LaravelMoneroModule\Concerns;

use Mollsoft\LaravelMoneroModule\Facades\Monero;
use Mollsoft\LaravelMoneroModule\Models\MoneroAccount;
use Mollsoft\LaravelMoneroModule\Models\MoneroWallet;

trait Accounts
{
    public function createAccount(MoneroWallet $wallet, ?string $title = null): MoneroAccount
    {
        return Monero::nodeAtomicLock($wallet->node, function() use ($wallet, $title) {
            $api = $wallet->node->api();

            $api->openWallet($wallet->name, $wallet->password);

            $createAccount = $api->createAccount();

            $account = $wallet->accounts()->create([
                'title' => $title,
                'base_address' => $createAccount['address'],
                'account_index' => $createAccount['account_index'],
            ]);

            $account->addresses()->create([
                'wallet_id' => $wallet->id,
                'address' => $createAccount['address'],
                'address_index' => 0,
                'title' => 'Primary Address',
            ]);

            return $account;
        });
    }
}