<?php declare(strict_types=1);

namespace App;

class Server
{
    protected $listener;

    /**
     * @param $listener callable
     */
    public function __construct(callable $listener)
    {
        $this->listener = $listener;
    }

    /**
     * Run server via stream sockets
     * @param string $host
     * @param int $port
     * @throws \Exception
     */
    public function run(string $host = '0.0.0.0', int $port = 80)
    {
        $listeningAddress = sprintf('tcp://%s:%s', $host, $port);
        $socket = stream_socket_server($listeningAddress, $errno, $errstr);
        if (!$socket) {
            throw new \Exception(sprintf('Error #%s: %s', $errno, $errstr));
        }
        printf("Server ready: %s:%d\n", $host, $port);

        while ($client = stream_socket_accept($socket, -1)) {
            echo 'Connection accepted from ' . stream_socket_get_name($client, false) . PHP_EOL;
            $stream = new Stream($client);

            $listener = $this->listener;
            $listener(Request::fromStream($stream), Response::init($stream));

            fclose($client);
        }

        fclose($socket);
    }
}
