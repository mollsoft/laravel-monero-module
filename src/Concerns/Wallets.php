<?php

namespace Mollsoft\LaravelMoneroModule\Concerns;

use Mollsoft\LaravelMoneroModule\Facades\Monero;
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
        return Monero::nodeAtomicLock($node, function() use ($node, $name, $password, $language) {
            $api = $node->api();

            $api->createWallet($name, $password, $language);
            $api->openWallet($name, $password);

            $mnemonic = $api->queryKey('mnemonic');

            $wallet = $node->wallets()
                ->create([
                    'name' => $name,
                    'password' => $password,
                    'mnemonic' => $mnemonic,
                ]);

            $getAccounts = $api->getAccounts();
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
        });
    }

    public function restoreWallet(
        MoneroNode $node,
        string $name,
        string $mnemonic,
        ?string $password = null,
        ?int $restoreHeight = null,
        ?string $language = null
    ) {
        return Monero::nodeAtomicLock($node, function() use ($node, $name, $mnemonic, $password, $language, $restoreHeight) {
            $restoreHeight = $restoreHeight ?? 0;

            $api = $node->api();

            try {
                $api->openWallet($name, $password);
            } catch (\Exception) {
                $api->restoreDeterministicWallet($name, $password, $mnemonic, $restoreHeight, $language);
            }

            $wallet = $node->wallets()->create([
                'name' => $name,
                'password' => $password,
                'mnemonic' => $mnemonic,
            ]);

            $getAccounts = $api->getAccounts();
            foreach ($getAccounts['subaddress_accounts'] as $item) {
                $account = $wallet->accounts()->create([
                    'base_address' => $item['base_address'],
                    'account_index' => $item['account_index'],
                    'title' => 'Primary Account',
                ]);

                $getAddress = $api->getAddress($account->account_index);

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
        });
    }
}