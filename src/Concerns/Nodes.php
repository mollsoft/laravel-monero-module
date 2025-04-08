<?php

namespace Mollsoft\LaravelMoneroModule\Concerns;

use Mollsoft\LaravelMoneroModule\Facades\Monero;
use Mollsoft\LaravelMoneroModule\Models\MoneroNode;

trait Nodes
{
    public function createNode(
        string $name,
        ?string $title,
        string $host,
        int $port = 8332,
        string $username = null,
        string $password = null
    ): MoneroNode {
        $model = Monero::getModelRPC();
        $api = new $model($host, $port, $username, $password);
        $api->request('get_version');

        $model = Monero::getModelNode();

        return $model::create([
            'name' => $name,
            'title' => $title,
            'host' => $host,
            'port' => $port,
            'username' => $username,
            'password' => $password,
        ]);
    }
}