<?php

namespace Mollsoft\LaravelMoneroModule;

use Mollsoft\LaravelMoneroModule\Concerns\Accounts;
use Mollsoft\LaravelMoneroModule\Concerns\Addresses;
use Mollsoft\LaravelMoneroModule\Concerns\Nodes;
use Mollsoft\LaravelMoneroModule\Concerns\Transfers;
use Mollsoft\LaravelMoneroModule\Concerns\Wallets;
use Mollsoft\LaravelMoneroModule\Models\MoneroAccount;
use Mollsoft\LaravelMoneroModule\Models\MoneroDeposit;
use Mollsoft\LaravelMoneroModule\Models\MoneroNode;
use Mollsoft\LaravelMoneroModule\Models\MoneroWallet;
use Mollsoft\LaravelMoneroModule\WebhookHandlers\WebhookHandlerInterface;

class Monero
{
    use Nodes, Wallets, Accounts, Addresses, Transfers;

    /**
     * @return class-string<MonerodRpcApi>
     */
    public function getModelRPC(): string
    {
        return config('monero.models.rpc_client');
    }

    /**
     * @return class-string<MoneroNode>
     */
    public function getModelNode(): string
    {
        return config('monero.models.node');
    }

    /**
     * @return class-string<MoneroWallet>
     */
    public function getModelWallet(): string
    {
        return config('monero.models.wallet');
    }

    /**
     * @return class-string<MoneroAccount>
     */
    public function getModelAccount(): string
    {
        return config('monero.models.account');
    }

    /**
     * @return class-string<MoneroAccount>
     */
    public function getModelAddress(): string
    {
        return config('monero.models.address');
    }

    /**
     * @return class-string<MoneroDeposit>
     */
    public function getModelDeposit(): string
    {
        return config('monero.models.deposit');
    }
    /**
     * @return class-string<WebhookHandlerInterface>
     */
    public function getModelWebhook(): string
    {
        return config('monero.webhook_handler');
    }
}
