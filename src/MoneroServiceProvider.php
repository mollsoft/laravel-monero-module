<?php

namespace Mollsoft\LaravelMoneroModule;

use Mollsoft\LaravelLitecoinModule\Commands\LitecoinSyncCommand;
use Mollsoft\LaravelLitecoinModule\Commands\LitecoinSyncWalletCommand;
use Mollsoft\LaravelLitecoinModule\Commands\LitecoinWebhookCommand;
use Mollsoft\LaravelLitecoinModule\Litecoin;
use Mollsoft\LaravelMoneroModule\Commands\MoneroSyncCommand;
use Mollsoft\LaravelMoneroModule\Commands\MoneroSyncWalletCommand;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MoneroServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('monero')
            ->hasConfigFile()
            ->hasMigrations([
                'create_monero_nodes_table',
                'create_monero_wallets_table',
                'create_monero_accounts_table',
                'create_monero_addresses_table'
            ])
            ->hasCommands([
                MoneroSyncCommand::class,
                MoneroSyncWalletCommand::class,
            ])
            ->hasInstallCommand(function(InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations();
            });

        $this->app->singleton(Monero::class);
    }
}