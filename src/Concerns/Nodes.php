<?php

namespace Mollsoft\LaravelMoneroModule\Concerns;

use Illuminate\Support\Str;
use Mollsoft\LaravelMoneroModule\Facades\Monero;
use Mollsoft\LaravelMoneroModule\Models\MoneroNode;
use Mollsoft\LaravelMoneroModule\Services\SupervisorService;
use Symfony\Component\Process\Process;

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
        $model = Monero::getModelNode();
        $node = new $model([
            'name' => $name,
            'title' => $title,
            'host' => $host,
            'port' => $port,
            'username' => $username,
            'password' => $password,
        ]);
        $node->api();
        $node->save();

        return $node;
    }

    public function createLocalNode(string $name, string $daemon, ?string $title = null): MoneroNode
    {
        /** @var class-string<MoneroNode> $model */
        $model = Monero::getModelNode();

        // Check wallet name
        $exists = $model::where('name', $name)->exists();
        if( $exists ) {
            throw new \Exception('Node name is already exists.');
        }

        // Определяем PORT
        $minPort = (int)config('monero.wallet_rpc.ports.min', 10240);
        $maxPort = (int)config('monero.wallet_rpc.ports.max', 32767);
        for ($i = 0; $i < 50; $i++) {
            $port = mt_rand($minPort, $maxPort);
            $connection = @fsockopen('127.0.0.1', $port);
            if ($connection) {
                fclose($connection);
                $port = null;
                continue;
            }
            break;
        }
        if (!$port) {
            throw new \Exception('Not found free port.');
        }

        // Create model
        $node = new $model([
            'name' => $name,
            'title' => $title,
            'host' => '127.0.0.1',
            'port' => $port,
            'username' => Str::random(),
            'password' => Str::random(),
            'daemon' => $daemon,
        ]);
        $process = SupervisorService::startProcess($node);
        $node->pid = $process->getPid();
        $node->api();
        $node->save();

        return $node;
    }
}