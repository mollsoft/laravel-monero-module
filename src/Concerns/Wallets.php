<?php

namespace Mollsoft\LaravelMoneroModule\Concerns;

use Illuminate\Support\Facades\Process;
use Mollsoft\LaravelMoneroModule\DTO\BIP39Convert;
use Mollsoft\LaravelMoneroModule\Facades\Monero;
use Mollsoft\LaravelMoneroModule\Models\MoneroNode;
use Mollsoft\LaravelMoneroModule\Models\MoneroWallet;

trait Wallets
{
    public function convertBIP39(string|array $mnemonic, ?string $passphrase = null): BIP39Convert
    {
        $binaryPath = config('monero.node.binary_path');
        $scriptPath = config('monero.node.script_path');
        if( !$binaryPath ) {
            throw new \Exception('Monero node configs is empty.');
        }
        $scriptPath = $scriptPath ?: __DIR__.'/../../bip39.cjs';

        if (is_array($mnemonic)) {
            $mnemonic = implode(' ', $mnemonic);
        }
        $process = Process::run([$binaryPath, $scriptPath, $mnemonic, $passphrase]);
        $output = $process->failed() ? $process->errorOutput() : $process->output();
        $json = @json_decode($output, true);

        if ((!$json['success'] ?? false)) {
            throw new \Exception($json['error'] ?? $process->output());
        }

        return new BIP39Convert(
            address: $json['address'],
            spendKey: $json['spendKey'],
            viewKey: $json['viewKey'],
            mnemonic: $json['mnemonic']
        );
    }

    public function createWallet(
        MoneroNode $node,
        string $name,
        ?string $password = null,
        ?string $language = null
    ): MoneroWallet {
        return Monero::nodeAtomicLock($node, function () use ($node, $name, $password, $language) {
            $api = $node->api();

            $api->createWallet($name, $password, $language);
            $api->openWallet($name, $password);

            $mnemonic = $api->queryKey('mnemonic');
            $restoreHeight = $api->request('get_address')['restore_height'] ?? $api->getHeight();

            $wallet = $node->wallets()
                ->create([
                    'name' => $name,
                    'password' => $password,
                    'mnemonic' => $mnemonic,
                    'restore_height' => $restoreHeight,
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
    ): MoneroWallet {
        return Monero::nodeAtomicLock(
            $node,
            function () use ($node, $name, $mnemonic, $password, $language, $restoreHeight) {
                $api = $node->api();

                $restoreHeight = $restoreHeight ?? $api->getHeight();

                try {
                    $api->openWallet($name, $password);
                } catch (\Exception) {
                    $api->restoreDeterministicWallet($name, $password, $mnemonic, $restoreHeight, $language);
                }

                $wallet = $node->wallets()->create([
                    'name' => $name,
                    'password' => $password,
                    'mnemonic' => $mnemonic,
                    'restore_height' => $restoreHeight
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
            }
        );
    }

    public function restoreWalletFromBIP39(
        MoneroNode $node,
        string $name,
        string $mnemonic,
        ?string $passphrase = null,
        ?string $password = null,
        ?int $restoreHeight = null
    ): MoneroWallet {
        $convertBIP39 = $this->convertBIP39($mnemonic, $passphrase);

        return $this->restoreWallet($node, $name, $convertBIP39->mnemonic, $password, $restoreHeight, 'English');
    }
}