<?php

namespace Mollsoft\LaravelMoneroModule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Mollsoft\LaravelMoneroModule\Facades\Monero;
use Mollsoft\LaravelMoneroModule\MonerodRpcApi;

class MoneroNode extends Model
{
    protected $fillable = [
        'name',
        'title',
        'host',
        'port',
        'username',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'port' => 'integer',
        'password' => 'encrypted',
    ];

    public function wallets(): HasMany
    {
        return $this->hasMany(Monero::getModelWallet(), 'node_id');
    }

    public function api(): MonerodRpcApi
    {
        /** @var class-string<MonerodRpcApi> $model */
        $model = Monero::getModelRPC();

        return new $model(
            host: $this->host,
            port: $this->port,
            username: $this->username,
            password: $this->password,
        );
    }
}
