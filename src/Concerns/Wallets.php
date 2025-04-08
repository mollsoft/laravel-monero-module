<?php

namespace Mollsoft\LaravelMoneroModule\Concerns;

use Mollsoft\LaravelMoneroModule\Models\MoneroNode;
use Mollsoft\LaravelMoneroModule\Models\MoneroWallet;

trait Wallets
{
    public function createWallet(
        MoneroNode $node,
        string $name,
        ?string $password = null,
        ?string $language = null
    ): MoneroWallet {
        $api = $node->api();

        $language = $language ?? 'English';

        $api->request('create_wallet', [
            'filename' => $name,
            'password' => $password,
            'language' => $language
        ]);

        $api->request('open_wallet', [
            'filename' => $name,
            'password' => $password,
        ]);

        $mnemonic = $api->request('query_key', ['key_type' => 'mnemonic'])['key'];

        $wallet = $node->wallets()
            ->create([
                'name' => $name,
                'password' => $password,
                'mnemonic' => $mnemonic,
            ]);

        $getAccounts = $api->request('get_accounts');
        foreach ($getAccounts['subaddress_accounts'] as $item) {
            $account = $wallet->accounts()->create([
                'base_address' => $item['base_address'],
                'account_index' => $item['account_index'],
                'title' => 'Primary Account',
            ]);

            $account->addresses()->create([
                'wallet_id' => $wallet->id,
                'address' => $item['base_address'],
                'address_index' => 0,
                'title' => 'Primary Address',
            ]);
        }

        return $wallet;
    }

    public function restoreWallet(
        MoneroNode $node,
        string $name,
        string $mnemonic,
        ?string $password = null,
        ?int $restoreHeight = null,
        ?string $language = null
    ) {
        $restoreHeight = $restoreHeight ?? 0;
        $language = $language ?? 'English';

        $api = $node->api();

        try {
            $api->request('open_wallet', [
                'filename' => $name,
                'password' => $password,
            ]);
        } catch (\Exception) {
            $api->request('restore_deterministic_wallet', [
                'filename' => $name,
                'password' => $password,
                'seed' => $mnemonic,
                'restore_height' => $restoreHeight,
                'language' => $language,
            ]);
        }

//        $walletMnemonic = $api->request('query_key', ['key_type' => 'mnemonic'])['key'];
//        if ($walletMnemonic !== $mnemonic) {
//            throw new \Exception('Wallet found, but mnemonic is changed!');
//        }

        $wallet = $node->wallets()->create([
            'name' => $name,
            'password' => $password,
            'mnemonic' => $mnemonic,
        ]);

        $getAccounts = $api->request('get_accounts');
        foreach ($getAccounts['subaddress_accounts'] as $item) {
            $account = $wallet->accounts()->create([
                'base_address' => $item['base_address'],
                'account_index' => $item['account_index'],
                'title' => 'Primary Account',
            ]);

            $getAddress = $api->request('get_address', [
                'account_index' => $account->account_index,
            ]);

            foreach ($getAddress['addresses'] as $addressItem) {
                $account->addresses()->create([
                    'wallet_id' => $wallet->id,
                    'address' => $addressItem['address'],
                    'address_index' => $addressItem['address_index'],
                    'title' => $addressItem['label'] ?: null,
                ]);
            }
        }

        return $wallet;
    }
}