<?php

namespace Mollsoft\LaravelMoneroModule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Mollsoft\LaravelMoneroModule\Casts\DecimalCast;

class MoneroDeposit extends Model
{
    protected $fillable = [
        'wallet_id',
        'account_id',
        'address_id',
        'txid',
        'amount',
        'block_height',
        'confirmations',
        'time_at',
    ];

    protected $casts = [
        'amount' => DecimalCast::class,
        'confirmations' => 'integer',
        'time_at' => 'datetime',
    ];

    public function wallet(): BelongsTo
    {
        /** @var class-string<MoneroWallet> $model */
        $model = config('monero.models.wallet');

        return $this->belongsTo($model, 'wallet_id');
    }

    public function account(): BelongsTo
    {
        /** @var class-string<MoneroAccount> $model */
        $model = config('monero.models.account');

        return $this->belongsTo($model, 'address_id');
    }

    public function address(): BelongsTo
    {
        /** @var class-string<MoneroAddress> $model */
        $model = config('monero.models.address');

        return $this->belongsTo($model, 'address_id');
    }
}
