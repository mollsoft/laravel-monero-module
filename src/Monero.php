<?php

namespace Mollsoft\LaravelMoneroModule;


use Decimal\Decimal;
use Mollsoft\LaravelMoneroModule\Models\MoneroAccount;
use Mollsoft\LaravelMoneroModule\Models\MoneroAddress;
use Mollsoft\LaravelMoneroModule\Models\MoneroNode;
use Mollsoft\LaravelMoneroModule\Models\MoneroWallet;

class Monero
{
    public function createNode(
        string $name,
        ?string $title,
        string $host,
        int $port = 8332,
        string $username = null,
        string $password = null
    ): MoneroNode {
        /** @var class-string<MonerodRpcApi> $model */
        $model = config('monero.models.rpc_client');
        $api = new $model($host, $port, $username, $password);
        $api->request('get_version');

        /** @var class-string<MoneroNode> $model */
        $model = config('monero.models.node');

        return $model::create([
            'name' => $name,
            'title' => $title,
            'host' => $host,
            'port' => $port,
            'username' => $username,
            'password' => $password,
        ]);
    }

    public function createWallet(
        MoneroNode $node,
        string $name,
        ?string $password = null,
        ?string $title = null,
        string $language = 'English'
    ): MoneroWallet {
        $api = $node->api();

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

        $wallet = $node->wallets()->create([
            'name' => $name,
            'title' => $title,
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
        ?string $title = null,
        int $restoreHeight = 0,
        string $language = 'English'
    ) {
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

        $walletMnemonic = $api->request('query_key', ['key_type' => 'mnemonic'])['key'];
        if ($walletMnemonic !== $mnemonic) {
            throw new \Exception('Wallet found, but mnemonic is changed!');
        }

        $wallet = $node->wallets()->create([
            'name' => $name,
            'title' => $title,
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

    public function createAccount(MoneroWallet $wallet, ?string $title = null): MoneroAccount
    {
        $api = $wallet->node->api();

        $api->request('open_wallet', [
            'filename' => $wallet->name,
            'password' => $wallet->password,
        ]);

        $createAccount = $api->request('create_account');

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
    }

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

    public function send(MoneroAccount $account, string $address, int|float|string|Decimal $amount): string
    {
        if (!($amount instanceof Decimal)) {
            $amount = new Decimal((string)$amount);
        }

        $wallet = $account->wallet;
        $api = $wallet->node->api();

        $api->request('open_wallet', [
            'filename' => $wallet->name,
            'password' => $wallet->password,
        ]);

        return $api->request('transfer', [
            'destinations' => [
                [
                    'amount' => $amount->mul(pow(10, 12))->toInt(),
                    'address' => $address,
                ]
            ],
            'account_index' => $account->account_index,
        ])['tx_hash'];
    }

    public function sendAll(MoneroAccount $account, string $address): string
    {
        $wallet = $account->wallet;
        $api = $wallet->node->api();

        $api->request('open_wallet', [
            'filename' => $wallet->name,
            'password' => $wallet->password,
        ]);

        $getBalance = $api->request('get_balance', [
            'account_index' => $account->account_index,
        ]);
        $unlockedBalance = (new Decimal($getBalance['unlocked_balance'] ?: '0'))->div(pow(10, 12));

        if( $unlockedBalance <= 0.0001 ) {
            throw new \Exception('Balance is zero');
        }

        $preview = $api->request('transfer', [
            'destinations' => [
                [
                    'amount' => $unlockedBalance->sub('0.0001')->mul(pow(10, 12))->toInt(),
                    'address' => $address,
                ]
            ],
            'account_index' => $account->account_index,
            'do_not_relay' => true,
        ]);

        $fee = (new Decimal($preview['fee'] ?: '0'))->div(pow(10, 12));
        $sendAmount = $unlockedBalance->sub($fee);

        return $this->send($account, $address, $sendAmount);
    }
}
