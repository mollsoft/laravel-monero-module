<?php

namespace Mollsoft\LaravelMoneroModule\Concerns;

use Mollsoft\LaravelMoneroModule\Facades\Monero;
use Mollsoft\LaravelMoneroModule\Models\MoneroAccount;
use Mollsoft\LaravelMoneroModule\Models\MoneroAddress;
use Mollsoft\LaravelMoneroModule\Models\MoneroNode;

trait Addresses
{
    public function createAddress(MoneroAccount $account, ?string $title = null): MoneroAddress
    {
        return Monero::nodeAtomicLock($account->wallet->node, function () use ($account, $title) {
            $wallet = $account->wallet;
            $api = $wallet->node->api();

            $api->openWallet($wallet->name, $wallet->password);

            $createAddress = $api->createAddress($account->account_index);

            return $account->addresses()->create([
                'wallet_id' => $wallet->id,
                'address' => $createAddress['address'],
                'address_index' => $createAddress['address_index'],
                'title' => $title,
            ]);
        });
    }

    public function validateAddress(MoneroNode $node, string $address): bool
    {
        $api = $node->api();

        $details = $api->validateAddress($address);

        return (bool)$details['valid'];
    }
}