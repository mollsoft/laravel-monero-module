<?php

namespace Mollsoft\LaravelMoneroModule\Facades;

use Illuminate\Support\Facades\Facade;

class Monero extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Mollsoft\LaravelMoneroModule\Monero::class;
    }
}
