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
$schedule->command('monero:sync')
    ->everyMinute()
    ->runInBackground();
```
