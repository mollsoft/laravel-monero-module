<?php

namespace Mollsoft\LaravelMoneroModule;

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
            ->discoversMigrations()
            ->hasCommands([
                MoneroSyncCommand::class,
                MoneroSyncWalletCommand::class,
            ])
            ->hasInstallCommand(function(InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('mollsoft/laravel-monero-module');
            });

        $this->app->singleton(Monero::class);
    }
}