<?php

namespace Mollsoft\LaravelMoneroModule\Services\Sync;


use Illuminate\Process\InvokedProcess;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Process;
use Mollsoft\LaravelMoneroModule\Facades\Monero;
use Mollsoft\LaravelMoneroModule\Models\MoneroNode;
use Mollsoft\LaravelMoneroModule\Services\BaseConsole;

class MoneroSync extends BaseConsole
{
    /** @var array<string, InvokedProcess> */
    protected array $processes = [];
    protected bool $touchEnable;
    protected int $touchSeconds;

    public function __construct()
    {
        $this->touchEnable = (bool)config('monero.touch.enabled', false);
        $this->touchSeconds = (int)config('monero.touch.waiting_seconds', 300);
    }

    public function run(): void
    {
        parent::run();

        $model = Monero::getModelNode();
        $model::query()
            ->orderBy('sync_at')
            ->each(function(MoneroNode $node) {
                if( $this->touchEnable ) {
                    $exists = $node->wallets()
                        ->whereNotNull('touch_at')
                        ->where('touch_at', '>', Date::now()->subSeconds($this->touchSeconds))
                        ->exists();
                    if( $exists ) {
                        $this->runProcess($node);
                    }
                    else {
                        $this->log("Синхронизация Node $node->name не требуется, нет touch адресов.");
                    }
                }
                else {
                    $this->runProcess($node);
                }
            });

        foreach ($this->processes as $name => $process) {
            $result = $process->wait();
            if ($result->successful()) {
                $this->log("Node $name успешно синхронизирована!", "success");
            } else {
                $this->log("Node $name не смогло синхронизироваться!", "error");
            }
        }
    }

    protected function runProcess(MoneroNode $node): void
    {
        $this->log("Запускаем процесс синхронизации Node $node->name...", "info");

        $process = Process::start(['php', 'artisan', 'monero:node-sync', $node->id], function ($type, $output) {
            $output = explode("\n", $output);
            $output = array_map('trim', $output);
            $output = array_filter($output);
            foreach ($output as $line) {
                $this->log("Node: $line");
            }
        });
        $this->processes[$node->name] = $process;

        $this->log("Процесс успешно запущен: {$process->id()}", "info");
    }
}