<?php

namespace Mollsoft\LaravelMoneroModule;

use Illuminate\Support\Facades\Cache;
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

    public function atomicLock(string $name, ?callable $callback, ?int $wait = null): mixed
    {
        $lockName = config('monero.atomic_lock.prefix').'_'.$name;
        $lockTimeout = (int)config('monero.atomic_lock.timeout', 300);
        $wait = $wait ?? (int)config('monero.atomic_lock.wait', 15);

        return Cache::lock($lockName, $lockTimeout)->block($wait, $callback);
    }

    public function nodeAtomicLock(MoneroNode $node, ?callable $callback, ?int $wait = null): mixed
    {
        if( $node->isLocal() ) {
            return call_user_func($callback);
        }

        return $this->atomicLock('node_'.$node->id, $callback, $wait);
    }

    public function walletAtomicLock(MoneroWallet $wallet, ?callable $callback, ?int $wait = null): mixed
    {
        if( !$wallet->node->isLocal() ) {
            return call_user_func($callback);
        }

        return $this->atomicLock('wallet_'.$wallet->id, $callback, $wait);
    }

    public function generalAtomicLock(MoneroWallet $wallet, ?callable $callback, ?int $wait = null): mixed
    {
        return $this->nodeAtomicLock($wallet->node, fn() => $this->walletAtomicLock($wallet, $callback, $wait), $wait);
    }
}
