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
use Phalcon\Logger\Item;
use Socket;

use function register_shutdown_function;
use function socket_close;
use function json_encode;
use function socket_create;
use function socket_sendto;
use function strlen;

/**
 * Sends messages using UDP protocol to external server
 */
class Udp extends AbstractAdapter
{
    protected false|Socket $socket = false;

    /**
     * Storage for holding all messages until they are ready to be sent to server.
     */
    protected array $logs = [];

    /**
     * Class constructor.
     *
     * @param string $name Name
     * @param string $host IP address of the remote host
     * @param int $port
     */
    public function __construct(
        protected string $name,
        protected string $host,
        protected int $port
    ) {
        register_shutdown_function([$this, 'commit']);
        register_shutdown_function([$this, 'close']);
    }

    /**
     * Writes the log.
     */
    public function process(Item $item): void
    {
        $this->logs[] = [
            'message' => $item->getMessage(),
            'type'    => $item->getLevelName(),
            'time'    => $item->getDateTime()->getTimestamp(),
            'context' => $item->getContext()
        ];

        if (!$this->inTransaction) {
            $this->send();
        }
    }

    public function close(): bool
    {
        if ($this->socket) {
            socket_close($this->socket);
        }

        return true;
    }

    public function begin(): self
    {
        $this->commit();
        $this->inTransaction = true;

        return $this;
    }

    public function commit(): self
    {
        if (!$this->inTransaction || empty($this->logs)) {
            $this->inTransaction = false;
        } else {
            $this->send();
            $this->inTransaction = false;
        }

        return $this;
    }

    /**
     * Send logs via Socket
     */
    protected function send(): void
    {
        if (empty($this->logs)) {
            return;
        }

        $message = json_encode($this->logs);

        $this->socket ?: socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

        if (!$this->socket) {
            return;
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
