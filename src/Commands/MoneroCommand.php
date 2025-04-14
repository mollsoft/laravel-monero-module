<?php

namespace Mollsoft\LaravelMoneroModule\Commands;

use Illuminate\Console\Command;
use Mollsoft\LaravelMoneroModule\Services\SupervisorService;

class MoneroCommand extends Command
{
    protected $signature = 'monero';

    protected $description = 'Monero supervisor process';

    public function handle(SupervisorService $service): void
    {
        $service
            ->setLogger(fn(string $message, ?string $type) => $this->{$type ? ($type === 'success' ? 'info' : $type) : 'line'}($message))
            ->run();
    }
}
