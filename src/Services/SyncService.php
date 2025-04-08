<?php

namespace Mollsoft\LaravelMoneroModule\Services;

use Brick\Math\BigDecimal;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Mollsoft\LaravelMoneroModule\Api\Api;
use Mollsoft\LaravelMoneroModule\Facades\Monero;
use Mollsoft\LaravelMoneroModule\Models\MoneroAccount;
use Mollsoft\LaravelMoneroModule\Models\MoneroDeposit;
use Mollsoft\LaravelMoneroModule\Models\MoneroWallet;
use Mollsoft\LaravelMoneroModule\WebhookHandlers\WebhookHandlerInterface;

class SyncService extends BaseSync
{
    protected readonly Api $api;
    protected readonly WebhookHandlerInterface $webhookHandler;

    /** @var MoneroDeposit[] */
    protected array $webhooks = [];

    public function __construct(
        protected readonly MoneroWallet $wallet
    ) {
        $this->api = $this->wallet->node->api();

        /** @var class-string<WebhookHandlerInterface> $model */
        $model = Monero::getModelWebhook();
        $this->webhookHandler = App::make($model);

    }

    public function run(): void
    {
        parent::run();

        try {
            Monero::nodeAtomicLock($this->wallet->node, function () {
                $this
                    ->openWallet()
                    ->refreshWallet()
                    ->getBalances()
                    ->incomingTransfers()
                    ->runWebhooks();
            });
        }
        catch( LockTimeoutException $e ) {
            $this->log('На данный момент нода занята другим сервисом!', 'error');
        }
    }

    protected function openWallet(): self
    {
        $this->log("Открываем кошелек {$this->wallet->name}...");
        $this->api->openWallet($this->wallet->name, $this->wallet->password);
        $this->log('Кошелек успешно открыт!');

        return $this;
    }

    protected function refreshWallet(): self
    {
        $this->log('Выполняем функцию refresh...');
        $this->api->refresh();
        $this->log('Функция refresh выполнена!');

        return $this;
    }

    protected function getBalances(): self
    {
        $this->log('Запрашиваем общий баланс через метод get_balance...');
        $getBalances = $this->api->getAllBalance();
        $this->log('Баланс успешно получен: '.json_encode($getBalances));

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
                $this->log("Запрашиваем баланс по Account Index $account->account_index...");
                $getBalances = $this->api->getAccountBalance($account->account_index);
                $this->log("Баланс успешно получен: ".json_encode($getBalances));

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
        $this->log("Запрашиваем историю входящих переводов...");
        $getTransfers = $this->api->request(
            'get_transfers',
            ['in' => true, 'pending' => true, 'pool' => true, 'all_accounts' => true]
        );
        $this->log('История получена: '.json_encode($getTransfers));

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
                $this->log('Запускаем Webhook на новый Deposit ID#'.$item->id.'...');
                $this->webhookHandler->handle($item);
                $this->log('Webhook успешно обработан!');
            } catch (\Exception $e) {
                $this->log('Ошибка обработки Webhook: '.$e->getMessage());
                Log::error('Monero WebHook for deposit '.$item->id.' - '.$e->getMessage());
            }
        }

        return $this;
    }
}