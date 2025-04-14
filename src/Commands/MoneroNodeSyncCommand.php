<?php

namespace Mollsoft\LaravelMoneroModule\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Mollsoft\LaravelMoneroModule\Facades\Monero;
use Mollsoft\LaravelMoneroModule\Services\Sync\NodeSync;

class MoneroNodeSyncCommand extends Command
{
    protected $signature = 'monero:node-sync {node_id}';

    protected $description = 'Monero sync node process';

    public function handle(): void
    {
        $nodeId = (int)$this->argument('node_id');
        $node = Monero::getModelNode()::findOrFail($nodeId);

        $service = App::make(NodeSync::class, compact('node'));
        $service
            ->setLogger(fn(string $message, ?string $type) => $this->{$type ? ($type === 'success' ? 'info' : $type) : 'line'}($message))
            ->run();
    }
}
