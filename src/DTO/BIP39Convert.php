<?php

namespace Mollsoft\LaravelMoneroModule\DTO;

readonly class BIP39Convert
{
    public string $address;
    public string $spendKey;
    public string $viewKey;
    public string $mnemonic;

    public function __construct(string $address, string $spendKey, string $viewKey, string $mnemonic)
    {
        $this->address = $address;
        $this->spendKey = $spendKey;
        $this->viewKey = $viewKey;
        $this->mnemonic = $mnemonic;
    }

    public function toArray(): array
    {
        return [
            'address' => $this->address,
            'spendKey' => $this->spendKey,
            'viewKey' => $this->viewKey,
            'mnemonic' => $this->mnemonic,
        ];
    }
}