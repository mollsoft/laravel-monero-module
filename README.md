# Laravel Monero Module

Organization of payment acceptance and automation of payments of XMR coins on the Monero blockchain.

### Installation

You can install the package via composer:
```bash
composer require mollsoft/laravel-monero-module
```

After you can run installer using command:
```bash
php artisan monero:install
```

Optional, you can install Monero Wallet RPC using command:
```bash
php artisan monero:wallet-rpc
```

And run migrations:
```bash
php artisan migrate
```

Register Service Provider and Facade in app, edit `config/app.php`:
```php
'providers' => ServiceProvider::defaultProviders()->merge([
    ...,
    \Mollsoft\LaravelMoneroModule\MoneroServiceProvider::class,
])->toArray(),

'aliases' => Facade::defaultAliases()->merge([
    ...,
    'Monero' => \Mollsoft\LaravelMoneroModule\Facades\Monero::class,
])->toArray(),
```

Add cron job, in file `app/Console/Kernel` in method `schedule(Schedule $schedule)` add
```
Schedule::command('monero:sync')
    ->everyMinute()
    ->runInBackground();
```

You must setup Supervisor, create file `/etc/supervisor/conf.d/laravel-monero-module.conf` with content (change user and paths):
```
[program:laravel-monero-module]
process_name=%(program_name)s
command=php /home/forge/example.com/artisan monero
autostart=true
autorestart=true
user=forge
redirect_stderr=true
stdout_logfile=/home/forge/example.com/monero.log
stopwaitsecs=3600
```

### Commands
Monero Node sync with all wallets in here.
```bash
php artisan monero:node-sync [NODE ID]
```

Monero Wallet sync.
```bash
php artisan monero:wallet-sync [WALLET ID]
```


### For Developers
Command for build JS script:
```bash
npm i
npm run build
```