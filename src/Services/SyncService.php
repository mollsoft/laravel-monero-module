<?php

namespace Mollsoft\LaravelMoneroModule\Services;

use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Mollsoft\LaravelMoneroModule\Facades\Monero;
use Mollsoft\LaravelMoneroModule\Models\MoneroAccount;
use Mollsoft\LaravelMoneroModule\Models\MoneroDeposit;
use Mollsoft\LaravelMoneroModule\Models\MoneroWallet;
use Mollsoft\LaravelMoneroModule\MonerodRpcApi;
use Mollsoft\LaravelMoneroModule\WebhookHandlers\WebhookHandlerInterface;

class SyncService
{
    protected readonly MonerodRpcApi $api;
    protected readonly WebhookHandlerInterface $webhookHandler;
    protected readonly string $lockName;
    protected readonly int $lockTimeout;

    /** @var MoneroDeposit[] */
    protected array $webhooks = [];

    public function __construct(
        protected readonly MoneroWallet $wallet
    ) {
        $this->api = $this->wallet->node->api();

        /** @var class-string<WebhookHandlerInterface> $model */
        $model = Monero::getModelWebhook();
        $this->webhookHandler = App::make($model);

        $this->lockName = config('monero.atomic_lock.name');
        $this->lockTimeout = config('monero.atomic_lock.timeout');
    }

    public function run(): void
    {
        Cache::lock($this->lockName, $this->lockTimeout)->block(5, function () {
            $this
                ->openWallet()
                ->refreshWallet()
                ->getBalances()
                ->incomingTransfers()
                ->runWebhooks();
        });
    }

    protected function openWallet(): self
    {
        $this->api->request('open_wallet', [
            'filename' => $this->wallet->name,
            'password' => $this->wallet->password,
        ]);

        return $this;
    }

    protected function refreshWallet(): self
    {
        $this->api->request('refresh');

        return $this;
    }

    protected function getBalances(): self
    {
        $getBalances = $this->api->request('get_balance', [
            'all_accounts' => true,
        ]);

        $balance = BigDecimal::of($getBalances['balance'] ?: '0')->dividedBy(pow(10, 12), 12);
        $unlockedBalance = BigDecimal::of($getBalances['unlocked_balance'] ?: '0')->dividedBy(pow(10, 12), 12);

        $this->wallet->update([
            'sync_at' => Date::now(),
            'balance' => $balance,
            'unlocked_balance' => $unlockedBalance,
        ]);

        $this->wallet
            ->addresses()
            ->update([
                'balance' => 0,
                'unlocked_balance' => 0,
            ]);

        foreach ($getBalances['per_subaddress'] as $item) {
            $balance = (BigDecimal::of($item['balance'] ?: '0'))->dividedBy(pow(10, 12), 12);
            $unlockedBalance = (BigDecimal::of($item['unlocked_balance'] ?: '0'))->dividedBy(pow(10, 12), 12);

            $this->wallet
                ->addresses()
                ->whereAddress($item['address'])
                ->update([
                    'balance' => $balance,
                    'unlocked_balance' => $unlockedBalance,
                ]);
        }

        $this->wallet
            ->accounts()
            ->each(function (MoneroAccount $account) {
                $getBalances = $this->api->request('get_balance', [
                    'account_index' => $account->account_index,
                ]);
                $balance = (BigDecimal::of($getBalances['balance'] ?: '0'))->dividedBy(pow(10, 12), 12);
                $unlockedBalance = (BigDecimal::of($getBalances['unlocked_balance'] ?: '0'))->dividedBy(pow(10, 12), 12);

                $account->update([
                    'balance' => $balance,
                    'unlocked_balance' => $unlockedBalance,
                ]);
            });

        return $this;
    }

    protected function incomingTransfers(): self
    {
        $getTransfers = $this->api->request(
            'get_transfers',
            ['in' => true, 'pending' => true, 'pool' => true, 'all_accounts' => true]
        );

        $transfers = array_merge($getTransfers['pool'] ?? [], $getTransfers['in'] ?? []);

        foreach ($transfers as $item) {
            $amount = (BigDecimal::of($item['amount'] ?: '0'))->dividedBy(pow(10, 12), 12);

            $address = $this->wallet
                ->addresses()
                ->whereAddress($item['address'])
                ->first();

            $deposit = $address?->deposits()->updateOrCreate([
                'txid' => $item['txid']
            ], [
                'wallet_id' => $this->wallet->id,
                'account_id' => $address->account_id,
                'amount' => $amount,
                'block_height' => ($item['height'] ?? 0) ?: null,
                'confirmations' => $item['confirmations'] ?? 0,
                'time_at' => Date::createFromTimestamp($item['timestamp']),
            ]);

            if ($deposit?->wasRecentlyCreated) {
                $this->webhooks[] = $deposit;
            }
        }

        return $this;
    }

    protected function runWebhooks(): self
    {
        foreach ($this->webhooks as $item) {
            try {
                $this->webhookHandler->handle($item);
            } catch (\Exception $e) {
                Log::error('Monero WebHook for deposit '.$item->id.' - '.$e->getMessage());
            }
        }

        return $this;
    }
}