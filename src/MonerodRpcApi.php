<?php

namespace Mollsoft\LaravelMoneroModule;

use GuzzleHttp\Client;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface;

class MonerodRpcApi
{
    protected readonly Client $client;
    protected ?ResponseInterface $response = null;

    public function __construct(
        protected readonly string $host,
        protected readonly int $port,
        protected readonly ?string $username,
        protected readonly ?string $password,
    ) {
        $this->client = new Client([
            'base_uri' => 'http://'.$this->host.':'.$this->port.'/json_rpc',
            'auth' => [$this->username, $this->password, 'digest'],
            'timeout' => 30,
            'connect_timeout' => 10,
            'http_errors' => false,
        ]);
    }

    public function request(string $method, array $params = []): array
    {
        $requestId = Str::uuid()->toString();

        $this->response = $this->client->post('', [
            'json' => [
                'jsonrpc' => '2.0',
                'id' => $requestId,
                'method' => $method,
                'params' => $params
            ],
        ]);

        $body = $this->response->getBody()->getContents();
        $body = json_decode($body, true);

        if( !isset($body['id']) || $body['id'] !== $requestId ) {
            throw new \Exception('Request ID is not correct');
        }

        if( $body['error'] ?? false ) {
            throw new \Exception('Monerod '.$method.' '.$body['error']['code'].' - '.$body['error']['message']);
        }

        return isset( $body['result'] ) && is_array($body['result']) ? $body['result'] : $body;
    }
}
