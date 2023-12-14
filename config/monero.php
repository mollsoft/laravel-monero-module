<?php

return [
    /*
     * Sets the handler to be used when Monero Wallet has a new deposit.
     */
    'webhook_handler' => \Mollsoft\LaravelMoneroModule\WebhookHandlers\EmptyWebhookHandler::class,

    /*
     * Set model class to allow more customization.
     *
     * MonerodRpcApi model must be or extend `Mollsoft\LaravelMoneroModule\MonerodRpcApi::class`
     * MoneroNode model must be or extend `Mollsoft\LaravelMoneroModule\Models\MoneroNode::class`
     * MoneroWallet model must be or extend `Mollsoft\LaravelMoneroModule\Models\MoneroWallet::class`
     * MoneroAccount model must be or extend `Mollsoft\LaravelMoneroModule\Models\MoneroAccount::class`
     * MoneroAddress model must be or extend `Mollsoft\LaravelMoneroModule\Models\MoneroAddress::class`
     * MoneroDeposit model must be or extend `Mollsoft\LaravelMoneroModule\Models\MoneroDeposit::class`
     */
    'models' => [
        'rpc_client' => \Mollsoft\LaravelMoneroModule\MonerodRpcApi::class,
        'node' => \Mollsoft\LaravelMoneroModule\Models\MoneroNode::class,
        'wallet' => \Mollsoft\LaravelMoneroModule\Models\MoneroWallet::class,
        'account' => \Mollsoft\LaravelMoneroModule\Models\MoneroAccount::class,
        'address' => \Mollsoft\LaravelMoneroModule\Models\MoneroAddress::class,
        'deposit' => \Mollsoft\LaravelMoneroModule\Models\MoneroDeposit::class,
    ],

    /*
     * You cannot work with multiple wallets in parallel.
     * These settings are intended to be limiting.
     */
    'atomic_lock' => [
        'name' => '\Mollsoft\LaravelMoneroModule',
        'timeout' => 300,
    ],
];
