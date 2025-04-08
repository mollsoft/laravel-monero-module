<?php

namespace Mollsoft\LaravelMoneroModule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Mollsoft\LaravelMoneroModule\Casts\BigDecimalCast;
use Mollsoft\LaravelMoneroModule\Facades\Monero;

class MoneroAddress extends Model
{
    protected $fillable = [
        'wallet_id',
        'account_id',
        'address',
        'address_index',
        'title',
        'balance',
        'unlocked_balance',
    ];

    protected $casts = [
        'address_index' => 'integer',
        'balance' => BigDecimalCast::class,
        'unlocked_balance' => BigDecimalCast::class,
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Monero::getModelWallet(), 'wallet_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Monero::getModelAccount(), 'account_id');
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(Monero::getModelDeposit(), 'address_id');
    }
}
