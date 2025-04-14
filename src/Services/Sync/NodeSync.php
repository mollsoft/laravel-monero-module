<?php

namespace Mollsoft\LaravelMoneroModule\Services\Sync;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Date;
use Mollsoft\LaravelMoneroModule\Api\Api;
use Mollsoft\LaravelMoneroModule\Models\MoneroNode;
use Mollsoft\LaravelMoneroModule\Models\MoneroWallet;
use Mollsoft\LaravelMoneroModule\Services\BaseConsole;

class NodeSync extends BaseConsole
{
    protected MoneroNode $node;
    protected ?Api $api = null;

    protected bool $touchEnable;
    protected int $touchSeconds;

    public function __construct(MoneroNode $node)
    {
        $this->node = $node;

        $this->touchEnable = (bool)config('monero.touch.enabled', false);
        $this->touchSeconds = (int)config('monero.touch.waiting_seconds', 300);
    }

    public function run(): void
    {
        parent::run();

        $this->log("Начинаем синхронизацию ноды {$this->node->name}...");

        $this->node->update([
            'sync_at' => Date::now(),
        ]);

        try {
            $this->api = $this->node->api();
        }
        catch(\Exception $e) {
            $this->log("Ошибка: {$e->getMessage()}", "error");
            return;
        }

        $this->node
            ->wallets()
            ->orderBy('id')
            ->each(function(MoneroWallet $wallet) {
                if( !$this->touchEnable || $wallet->touch_at >= Date::now()->subSeconds($this->touchSeconds) ) {
                    $service = App::make(WalletSync::class, [
                        'wallet' => $wallet,
                        'node' => $this->node,
                        'api' => $this->api,
                    ]);
                    $service->setLogger($this->logger);
                    $service->run();
                }
                else {
                    $this->log("Кошелек {$wallet->name} не требует синхронизации, так как не touch.");
                }
            });

        $this->node->update([
            'sync_at' => Date::now(),
        ]);

        $this->log("Синхронизация ноды {$this->node->name} завершена!");
    }
}