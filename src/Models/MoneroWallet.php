<?php

namespace Mollsoft\LaravelMoneroModule\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Mollsoft\LaravelMoneroModule\Casts\BigDecimalCast;
use Mollsoft\LaravelMoneroModule\Facades\Monero;

class MoneroWallet extends Model
{
    protected $fillable = [
        'node_id',
        'name',
        'title',
        'password',
        'mnemonic',
        'balance',
        'unlocked_balance',
        'sync_at',
        'touch_at',
    ];

    protected $hidden = [
        'password',
        'mnemonic'
    ];

    protected $casts = [
        'password' => 'encrypted',
        'mnemonic' => 'encrypted',
        'balance' => BigDecimalCast::class,
        'unlocked_balance' => BigDecimalCast::class,
        'touch_at' => 'datetime',
    ];

    public function node(): BelongsTo
    {
        return $this->belongsTo(Monero::getModelNode(), 'node_id');
    }

    public function primaryAccount(): HasOne
    {
        return $this->hasOne(Monero::getModelAccount(), 'wallet_id')
            ->ofMany('account_index', 'min');
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Monero::getModelAccount(), 'wallet_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Monero::getModelAddress(), 'wallet_id');
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(Monero::getModelDeposit(), 'wallet_id');
    }
}
