<?php

namespace Mollsoft\LaravelMoneroModule\Api;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Mollsoft\LaravelMoneroModule\Models\MoneroNode;

class Api
{
    protected string $host;
    protected int $port;
    protected ?string $username;
    protected ?string $password;
    protected ?int $pid;

    public function __construct(string $host, int $port, ?string $username = null, ?string $password = null)
    {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
    }

    public function request(string $method, array $params = []): mixed
    {
        $requestId = Str::uuid()->toString();

        $response = Http::withDigestAuth($this->username ?? '', $this->password ?? '')
            ->timeout(60)
            ->connectTimeout(10)
            ->post('http://'.$this->host.':'.$this->port.'/json_rpc', [
                'jsonrpc' => '2.0',
                'id' => $requestId,
                'method' => $method,
                'params' => $params
            ]);

        $result = $response->json();
        if (empty($result)) {
            throw new \Exception($response->body());
        }

        if ($result['id'] !== $requestId) {
            throw new \Exception('Request ID is not correct');
        }

        if (isset($result['error'])) {
            throw new \Exception($result['error']['message']);
        }

        if (count($result ?? []) === 0) {
            throw new \Exception($response->body());
        }

        return $result['result'];
    }

    public function getHeight(): int
    {
        $data = $this->request('get_height');
        if( !isset( $data['height'] ) ) {
            throw new \Exception(print_r($data, true));
        }

        return $data['height'];
    }

    public function openWallet(string $name, ?string $password = null): void
    {
        $this->request('open_wallet', [
            'filename' => $name,
            'password' => $password,
        ]);
    }

    public function refresh(): void
    {
        $this->request('refresh');
    }

    public function getAllBalance(): array
    {
        return $this->request('get_balance', [
            'all_accounts' => true,
        ]);
    }

    public function getAccountBalance(int $index): array
    {
        return $this->request('get_balance', [
            'account_index' => $index,
        ]);
    }

    public function createAccount(): array
    {
        return $this->request('create_account');
    }

    public function createAddress(int $accountIndex): array
    {
        return $this->request('create_address', [
            'account_index' => $accountIndex,
        ]);
    }

    public function validateAddress(string $address): array
    {
        return $this->request('validate_address', [
            'address' => $address,
        ]);
    }

    public function getVersion(): array
    {
        return $this->request('get_version');
    }

    public function createWallet(string $name, ?string $password = null, ?string $language = null): void
    {
        $language = $language ?? 'English';

        $this->request('create_wallet', [
            'filename' => $name,
            'password' => $password,
            'language' => $language
        ]);
    }

    public function queryKey(string $keyType): mixed
    {
        return $this->request('query_key', ['key_type' => $keyType])['key'] ?? null;
    }

    public function getAccounts(): array
    {
        return $this->request('get_accounts');
    }

    public function restoreDeterministicWallet(
        string $name,
        ?string $password,
        string $mnemonic,
        ?int $restoreHeight = null,
        ?string $language = null
    ): void {
        $language = $language ?? 'English';

        $this->request('restore_deterministic_wallet', [
            'filename' => $name,
            'password' => $password,
            'seed' => $mnemonic,
            'restore_height' => $restoreHeight,
            'language' => $language,
        ]);
    }

    public function getAddress(int $accountIndex): array
    {
        return $this->request('get_address', [
            'account_index' => $accountIndex,
        ]);
    }
}
