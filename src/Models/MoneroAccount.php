<?php

namespace Mollsoft\LaravelMoneroModule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Mollsoft\LaravelMoneroModule\Casts\DecimalCast;

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
        'balance' => DecimalCast::class,
        'unlocked_balance' => DecimalCast::class,
    ];

    public function wallet(): BelongsTo
    {
        /** @var class-string<MoneroWallet> $model */
        $model = config('monero.models.wallet');

        return $this->belongsTo($model, 'wallet_id');
    }

    public function primaryAddress(): HasOne
    {
        /** @var class-string<MoneroAddress> $model */
        $model = config('monero.models.address');

        return $this->hasOne($model, 'account_id')
            ->ofMany('address_index', 'min');
    }

    public function addresses(): HasMany
    {
        /** @var class-string<MoneroAddress> $model */
        $model = config('monero.models.address');

        return $this->hasMany($model, 'account_id');
    }

    public function deposits(): HasMany
    {
        /** @var class-string<MoneroDeposit> $model */
        $model = config('monero.models.deposit');

        return $this->hasMany($model, 'account_id');
    }
}
