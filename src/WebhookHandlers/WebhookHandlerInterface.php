<?php

namespace Mollsoft\LaravelMoneroModule\WebhookHandlers;

use Mollsoft\LaravelMoneroModule\Models\MoneroDeposit;

interface WebhookHandlerInterface
{
    public function handle(MoneroDeposit $deposit): void;
}