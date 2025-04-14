<?php

namespace Mollsoft\LaravelMoneroModule\Concerns;

use Brick\Math\BigDecimal;
use Mollsoft\LaravelMoneroModule\Facades\Monero;
use Mollsoft\LaravelMoneroModule\Models\MoneroAccount;

trait Transfers
{
    public function send(MoneroAccount $account, string $address, int|float|string|BigDecimal $amount): string
    {
        return Monero::generalAtomicLock($account->wallet, function() use ($account, $address, $amount) {
            if (!($amount instanceof BigDecimal)) {
                $amount = BigDecimal::of($amount);
            }

            $wallet = $account->wallet;
            $api = $wallet->node->api();

            $api->openWallet($wallet->name, $wallet->password);

            return $api->request('transfer', [
                'destinations' => [
                    [
                        'amount' => $amount->multipliedBy(pow(10, 12))->toInt(),
                        'address' => $address,
                    ]
                ],
                'account_index' => $account->account_index,
            ])['tx_hash'];
        });
    }

    public function sendAll(MoneroAccount $account, string $address): string
    {
        return Monero::generalAtomicLock($account->wallet, function() use ($account, $address) {
            $wallet = $account->wallet;
            $api = $wallet->node->api();

            $api->openWallet($wallet->name, $wallet->password);

            $getBalance = $api->getAccountBalance($account->account_index);
            $unlockedBalance = BigDecimal::of($getBalance['unlocked_balance'] ?: '0')->dividedBy(pow(10, 12), 12);

            if( $unlockedBalance->isLessThanOrEqualTo(0.0001) ) {
                throw new \Exception('Balance is zero');
            }

            $preview = $api->request('transfer', [
                'destinations' => [
                    [
                        'amount' => $unlockedBalance->minus('0.0001')->multipliedBy(pow(10, 12))->toInt(),
                        'address' => $address,
                    ]
                ],
                'account_index' => $account->account_index,
                'do_not_relay' => true,
            ]);

            $fee = BigDecimal::of($preview['fee'] ?: '0')->dividedBy(pow(10, 12));
            $sendAmount = $unlockedBalance->minus($fee);

            return $this->send($account, $address, $sendAmount);
        });
    }
}