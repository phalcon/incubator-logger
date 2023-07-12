<?php

/**
 * This file is part of the Phalcon Framework.
 *
 * (c) Phalcon Team <team@phalcon.io>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phalcon\Incubator\Logger\Adapter;

use Phalcon\Logger\Adapter\AbstractAdapter;
use Phalcon\Logger\Adapter\AdapterInterface;
use Phalcon\Logger\Item;

/**
 * Sends messages using UDP protocol to external server
 */
class Udp extends AbstractAdapter
{
    /**
     * Name
     *
     * @var string
     */
    protected $name = 'phalcon';

    /**
     * IP address of the remote host.
     *
     * @var string
     */
    protected $host;

    /**
     * @var int
     */
    protected $port;

    /**
     * @var resource|\Socket|false
     */
    protected $socket = false;

    /**
     * Storage for holding all messages until they are ready to be sent to server.
     *
     * @var array
     */
    protected $logs = [];

    /**
     * Flag for the transaction
     *
     * @var boolean
     */
    protected $isTransaction = false;

    /**
     * Class constructor.
     *
     * @param string $name
     * @param string $host
     * @param int $port
     */
    public function __construct(string $name, string $host, int $port)
    {
        $this->name = $name;
        $this->host = $host;
        $this->port = $port;

        register_shutdown_function([$this, 'commit']);
        register_shutdown_function([$this, 'close']);
    }

    /**
     * Writes the log.
     *
     * @param Item $item
     */
    public function process(Item $item): void
    {
        $this->logs[] = [
            'message' => $item->getMessage(),
            'type' => $item->getLevelName(),
            'time' => $item->getDateTime()->getTimestamp(),
            'context' => $item->getContext(),
        ];

        if (!$this->isTransaction) {
            $this->send();
        }
    }

    /**
     * @return bool
     */
    public function close(): bool
    {
        if ($this->socket) {
            socket_close($this->socket);
        }

        return true;
    }

    /**
     * @return AdapterInterface
     */
    public function begin(): AdapterInterface
    {
        $this->commit();
        $this->isTransaction = true;

        return $this;
    }

    /**
     * @return AdapterInterface
     */
    public function commit(): AdapterInterface
    {
        if (!$this->isTransaction || empty($this->logs)) {
            $this->isTransaction = false;
        } else {
            $this->send();
            $this->isTransaction = false;
        }

        return $this;
    }

    /**
     * Send logs via Socket
     *
     * @return void
     */
    protected function send(): void
    {
        if (empty($this->logs)) {
            return;
        }

        $message = json_encode($this->logs);

        if (!$this->socket) {
            $this->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        }

        socket_sendto(
            $this->socket,
            $message,
            strlen($message),
            0,
            $this->host,
            $this->port
        );

        $this->logs = [];
    }
}
