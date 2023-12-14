<?php

namespace Mollsoft\LaravelMoneroModule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Mollsoft\LaravelMoneroModule\Casts\DecimalCast;

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
        'balance' => DecimalCast::class,
        'unlocked_balance' => DecimalCast::class,
    ];

    public function wallet(): BelongsTo
    {
        /** @var class-string<MoneroWallet> $model */
        $model = config('monero.models.wallet');

        return $this->belongsTo($model, 'wallet_id');
    }

    public function account(): BelongsTo
    {
        /** @var class-string<MoneroWallet> $model */
        $model = config('monero.models.account');

        return $this->belongsTo($model, 'account_id');
    }

    public function deposits(): HasMany
    {
        /** @var class-string<MoneroDeposit> $model */
        $model = config('monero.models.deposit');

        return $this->hasMany($model, 'address_id');
    }
}
