<?php

namespace Mollsoft\LaravelMoneroModule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Mollsoft\LaravelMoneroModule\Api\Api;
use Mollsoft\LaravelMoneroModule\Facades\Monero;

class MoneroNode extends Model
{
    protected ?Api $_api = null;

    protected $fillable = [
        'name',
        'title',
        'host',
        'port',
        'username',
        'password',
        'daemon',
        'pid',
        'sync_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'port' => 'integer',
        'password' => 'encrypted',
        'daemon' => 'array',
        'pid' => 'integer',
        'sync_at' => 'datetime',
    ];

    public function wallets(): HasMany
    {
        return $this->hasMany(Monero::getModelWallet(), 'node_id');
    }

    public function isLocal(): bool
    {
        return !empty($this->daemon);
    }

    public function api(): Api
    {
        if( !$this->_api ) {
            /** @var class-string<Api> $model */
            $model = config('monero.models.api');
            $api = new $model(
                host: $this->host,
                port: $this->port,
                username: $this->username,
                password: $this->password,
            );

            $api->getVersion();

            $this->_api = $api;
        }

        return $this->_api;
    }
}
