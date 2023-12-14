<?php

namespace Mollsoft\LaravelMoneroModule\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Mollsoft\LaravelMoneroModule\Casts\DecimalCast;

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
    ];

    protected $hidden = [
        'password',
        'mnemonic'
    ];

    protected $casts = [
        'password' => 'encrypted',
        'mnemonic' => 'encrypted',
        'balance' => DecimalCast::class,
        'unlocked_balance' => DecimalCast::class,
    ];

    public function node(): BelongsTo
    {
        return $this->belongsTo(MoneroNode::class, 'node_id');
    }

    public function primaryAccount(): HasOne
    {
        /** @var class-string<MoneroAccount> $accountModel */
        $accountModel = config('monero.models.account');

        return $this->hasOne($accountModel, 'wallet_id')
            ->ofMany('account_index', 'min');
    }

    public function accounts(): HasMany
    {
        /** @var class-string<MoneroAccount> $accountModel */
        $accountModel = config('monero.models.account');

        return $this->hasMany($accountModel, 'wallet_id');
    }

    public function addresses(): HasMany
    {
        /** @var class-string<MoneroAddress> $addressModel */
        $addressModel = config('monero.models.address');

        return $this->hasMany($addressModel, 'wallet_id');
    }

    public function deposits(): HasMany
    {
        /** @var class-string<MoneroDeposit> $model */
        $model = config('monero.models.deposit');

        return $this->hasMany($model, 'wallet_id');
    }
}
