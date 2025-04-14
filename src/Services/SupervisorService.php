<?php

namespace Mollsoft\LaravelMoneroModule\Services;

use Mollsoft\LaravelMoneroModule\Facades\Monero;
use Mollsoft\LaravelMoneroModule\Models\MoneroNode;
use Symfony\Component\Process\Process;

class SupervisorService extends BaseConsole
{
    protected bool $shouldRun = true;
    /** @var class-string<MoneroNode> */
    protected string $model = MoneroNode::class;
    protected array $processes = [];
    protected int $watcherPeriod;

    public function __construct()
    {
        $this->model = Monero::getModelNode();
        $this->watcherPeriod = (int)config('monero.wallet_rpc.watcher_period', 30);
    }

    protected function log(string $message, ?string $type = null): void
    {
        if ($this->logger) {
            call_user_func($this->logger, $message, $type);
        }
    }

    public function run(): void
    {
        parent::run();

        $this->log("Starting monero worker service...");

        $this
            ->sigterm()
            ->while()
            ->closeProcesses();

        $this->log("Monero worker stopped.");
    }

    protected function sigterm(): static
    {
        pcntl_async_signals(true);

        pcntl_signal(SIGTERM, function () {
            $this->log("SIGTERM received. Shutting down gracefully...");
            $this->shouldRun = false;
        });

        pcntl_signal(SIGINT, function () {
            $this->log("SIGINT (Ctrl+C) received. Exiting...");
            $this->shouldRun = false;
        });

        return $this;
    }

    protected function while(): static
    {
        while ($this->shouldRun) {
            $this->thread();

            sleep($this->watcherPeriod);
        }

        return $this;
    }

    protected function thread(): void
    {
        $nodes = $this->model::query()
            ->whereNotNull('daemon')
            ->get();

        $activeNodesIDs = [];
        foreach( $nodes as $node ) {
            $activeNodesIDs[] = $node->id;

            if( !$this->isPortFree($node->host, $node->port) ) {
                continue;
            }

            if( $node->pid ) {
                $this->killPid($node->pid);
                $node->update(['pid' => null]);
            }

            $this->log("Starting monero-wallet-rpc for node {$node->name}...");
            try {
                $process = static::startProcess($node);
                $this->processes[$node->id] = $process;
                $node->update([
                    'pid' => $process->getPid(),
                ]);
                $this->log("Started process with PID {$node->pid} for node {$node->name}");
            }
            catch(\Exception $e) {
                $this->log("Error: {$e->getMessage()}", 'error');
            }
        }

        foreach ($this->processes as $nodeId => $process) {
            if (!in_array($nodeId, $activeNodesIDs)) {
                $this->log("Node #{$nodeId} no longer active, stopping process");
                $this->killProcess($nodeId);
            }
        }
    }

    public static function startProcess(MoneroNode $node): Process
    {
        $executePath = config('monero.wallet_rpc.execute_path') ?? 'monero-wallet-rpc';

        $args = [
            $executePath,
            '--wallet-dir', storage_path('app/monero'),
            '--rpc-bind-port', $node->port,
            '--daemon-address', $node->daemon,
            '--log-file', storage_path("logs/monero/$node->name.log"),
            '--non-interactive',
        ];
        if( $node->username ) {
            $args[] = '--rpc-login';
            $args[] = $node->username.':'.$node->password;
        }
        $process = new Process($args);
        $process->start();

        sleep(3);

        if( $error = $process->getErrorOutput() ) {
            if( $process->isRunning() ) {
                $process->stop();
            }
            throw new \Exception($error);
        }

        return $process;
    }

    protected function killProcess(int $nodeId): void
    {
        if (isset($this->processes[$nodeId])) {
            $process = $this->processes[$nodeId];

            if ($process->isRunning()) {
                $process->stop(3);
                $this->log("Stopped process for node #{$nodeId}");
            }

            unset($this->processes[$nodeId]);
        }

        $this->model::where('id', $nodeId)->update(['pid' => null]);
    }

    protected function closeProcesses(): static
    {
        foreach ($this->processes as $nodeId => $process) {
            $this->killProcess($nodeId);
        }

        return $this;
    }

    protected function isPortFree(string $host, int $port): bool
    {
        $connection = @fsockopen($host, $port);
        if (is_resource($connection)) {
            fclose($connection);
            return false;
        }
        return true;
    }

    protected function killPid(int $pid): void
    {
        if (posix_kill($pid, 0)) {
            exec("kill -9 {$pid}");
            $this->log("Killed process with PID {$pid}");
        }
    }
}