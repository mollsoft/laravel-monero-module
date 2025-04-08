<?php

namespace Mollsoft\LaravelMoneroModule\Concerns;

use Mollsoft\LaravelMoneroModule\Models\MoneroAccount;
use Mollsoft\LaravelMoneroModule\Models\MoneroAddress;
use Mollsoft\LaravelMoneroModule\Models\MoneroNode;

trait Addresses
{
    public function createAddress(MoneroAccount $account, ?string $title = null): MoneroAddress
    {
        $wallet = $account->wallet;
        $api = $wallet->node->api();

        $api->request('open_wallet', [
            'filename' => $wallet->name,
            'password' => $wallet->password,
        ]);

        $createAddress = $api->request('create_address', [
            'account_index' => $account->account_index,
        ]);

        return $account->addresses()->create([
            'wallet_id' => $wallet->id,
            'address' => $createAddress['address'],
            'address_index' => $createAddress['address_index'],
            'title' => $title,
        ]);
    }

    public function validateAddress(MoneroNode $node, string $address): bool
    {
        $api = $node->api();

        $details = $api->request('validate_address', [
            'address' => $address,
        ]);

        return (bool)$details['valid'];
    }
}