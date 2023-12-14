<?php

namespace Mollsoft\LaravelMoneroModule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Mollsoft\LaravelLitecoinModule\LitecoindRpcApi;
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
        /** @var class-string<MoneroWallet> $model */
        $model = config('monero.models.wallet');

        return $this->hasMany($model, 'node_id');
    }

    public function api(): MonerodRpcApi
    {
        /** @var class-string<MonerodRpcApi> $model */
        $model = config('monero.models.rpc_client');

        return new $model(
            host: $this->host,
            port: $this->port,
            username: $this->username,
            password: $this->password,
        );
    }
}
