<?php

namespace Mollsoft\LaravelMoneroModule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Mollsoft\LaravelMoneroModule\Casts\BigDecimalCast;
use Mollsoft\LaravelMoneroModule\Facades\Monero;

class MoneroAccount extends Model
{
    protected $fillable = [
        'wallet_id',
        'base_address',
        'title',
        'account_index',
        'balance',
        'unlocked_balance',
    ];

    protected $casts = [
        'account_index' => 'integer',
        'balance' => BigDecimalCast::class,
        'unlocked_balance' => BigDecimalCast::class,
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Monero::getModelWallet(), 'wallet_id');
    }

    public function primaryAddress(): HasOne
    {
        return $this->hasOne(Monero::getModelAddress(), 'account_id')
            ->ofMany('address_index', 'min');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Monero::getModelAddress(), 'account_id');
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(Monero::getModelDeposit(), 'account_id');
    }
}
