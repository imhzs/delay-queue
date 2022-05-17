<?php

namespace Lazy\DelayQueue;

use Lazy\DelayQueue\Process\Timer;
use SplFileObject;

class Worker
{
    /**
     * @var int
     */
    protected $workerNum = 2;

    /**
     * @var \Swoole\Process\Pool $pool
     */
    protected $pool;

    /**
     * @var Container
     */
    protected static $container;

    /**
     * @var callable
     */
    public $onWorkerStart;

    /**
     * Worker constructor.
     * @throws \ReflectionException
     */
    public function __construct()
    {
        $this->initialize();
    }

    /**
     * @param int $num
     */
    public function setWorkerNum(int $num)
    {
        $this->workerNum = $num;
    }

    /**
     * @return int
     */
    public function getWorkerNum(): int
    {
        return $this->workerNum;
    }

    /**
     * @throws \ReflectionException
     */
    public function run()
    {
        $this->parseCommand();
    }

    /**
     * @throws \ReflectionException
     */
    public function stopAll()
    {
        $this->parseCommand();
    }

    /**
     * @param $srv
     * @param $worker_id
     * @throws Exceptions\ServiceNotFoundException
     * @throws \ReflectionException
     */
    public function onWorkerStart($srv, $worker_id)
    {
        $masterPid = empty($srv->master_pid) ? posix_getpid() : $srv->master_pid;
        $this->setMasterPid($masterPid);

        while (true) {
            if (is_callable($this->onWorkerStart)) {
                call_user_func($this->onWorkerStart, self::$container);
            }

            Timer::tick();
            self::$container->make(Manager::class)->handleReadyQueue();
        }
    }

    /**
     * @return Container
     */
    public function getContainer(): Container
    {
        return self::$container;
    }

    /**
     * @throws \ReflectionException
     */
    protected function initialize()
    {
        self::$container = Container::getInstance();
        self::$container->singleton(Manager::class);
        self::$container->make(Manager::class);
        self::$container->singleton(RedisLock::class);
    }

    protected function start()
    {
        $pid = $this->getMasterPid();

        if ($pid > 0 && \swoole_process::kill($pid, 0)) {
            print("Running\n");
            return;
        }

        print("Start......\n");

        $this->daemonize();
        $this->pool = new \Swoole\Process\Pool($this->workerNum);

        $this->pool->on('workerStart', [$this, "onWorkerStart"]);
        $this->pool->start();

        print("Success......\n");
    }

    protected function stop()
    {
        $pid = $this->getMasterPid();

        if ($pid <= 0 || !\swoole_process::kill($pid, 0)) {
            print("Not running\n");
            return;
        }

        print("Stop...\n");
        print("PID: {$pid}\n");

        \swoole_process::kill($pid);
        $this->removeMasterPid();

        print("Stop success...\n");
    }

    /**
     * @throws \ReflectionException
     */
    protected function restart()
    {
        $this->stop();
        $this->start();
    }

    protected function daemonize(): void
    {
        /**
         * 重设文件权限掩码
         * 子进程从父进程继承了文件权限
         * 若子进程不涉及到文件创建，可取消
         */
        umask(0);

        // 创建子进程
        $pid = pcntl_fork();
        if (-1 === $pid) {
            throw new \RuntimeException('fork fail');
        } elseif ($pid > 0) {
            // 父进程退出
            exit(0);
        }

        /**
         * 更改子进程为进程组长
         * 使子进程摆脱父进程控制
         */
        if (-1 === posix_setsid()) {
            throw new \RuntimeException("setsid fail");
        }

        // Fork again avoid SVR4 system regain the control of terminal.
        $pid = pcntl_fork();
        if (-1 === $pid) {
            throw new \RuntimeException("fork fail");
        } elseif (0 !== $pid) {
            exit(0);
        }
    }

    protected function getMasterPid(): int
    {
        $file = $this->openMasterPidFile();

        return (int)$file->fgets();
    }

    /**
     * @param int $pid
     */
    protected function setMasterPid(int $pid): void
    {
        $file = $this->openMasterPidFile();
        if ($file->flock(LOCK_EX)) {
            $file->ftruncate(0);
            $file->fwrite($pid);
        }

        $file->flock(LOCK_UN);
    }

    protected function removeMasterPid(): void
    {
        $file = $this->openMasterPidFile();

        if ($file->flock(LOCK_EX)) {
            $file->ftruncate(0);
            $file->fwrite("");
        }

        $file->flock(LOCK_UN);
    }

    /**
     * @return SplFileObject
     */
    protected function openMasterPidFile(): SplFileObject
    {
        $filename = __DIR__ . '/../unique-prefix.pid';

        if (!file_exists($filename)) {
            touch($filename);
        }

        return new \SplFileObject($filename, "a+");
    }

    /**
     * @throws \ReflectionException
     */
    protected function parseCommand()
    {
        global $argv;

        // Check argv;
        $availableCommands = ['start', 'stop', 'restart'];

        if (!isset($argv[1]) || !\in_array($argv[1], $availableCommands)) {
            if (isset($argv[1])) {
                printf("Unknown command: %s\n", $argv[1]);
            }
            exit();
        }

        // Get command.
        $command  = \trim($argv[1]);

        // execute command.
        switch ($command) {
            case 'start':
                $this->start();
                break;
            case 'restart':
                $this->restart();
                break;
            case 'stop':
                $this->stop();
                break;
            default :
                if (isset($command)) {
                    printf("Unknown command: %s\n", $command);
                }
                exit();
        }
    }
}
