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

use Aws\CloudWatchLogs\CloudWatchLogsClient;
use Phalcon\Logger\Adapter\AbstractAdapter;
use Phalcon\Logger\Adapter\AdapterInterface;
use Phalcon\Logger\Item;

/**
 * Amazon (AWS) CloudWatch Logger
 */
class CloudWatch extends AbstractAdapter
{
    /**
     * @var CloudWatchLogsClient
     */
    protected $client;

    /**
     * Some kind of "folder" inside CloudWatch
     *
     * @var string
     */
    protected $groupName;

    /**
     * Some kind of "file" inside CloudWatch
     *
     * @var string
     */
    protected $streamName;

    /**
     * CloudWatch constructor.
     *
     * @param CloudWatchLogsClient $client
     * @param string $groupName
     * @param string $streamName
     */
    public function __construct(
        CloudWatchLogsClient $client,
        string $groupName,
        string $streamName
    ) {
        $this->client = $client;
        $this->groupName = $groupName;
        $this->streamName = $streamName;
    }

    /**
     * Processes the message in the adapter
     *
     * @param Item $item
     */
    public function process(Item $item): void
    {
        $this->client->putLogEvents([
            'logGroupName' => $this->groupName,
            'logStreamName' => $this->streamName,
            'logEvents' => [
                'message' => $item->getMessage(),
                'timestamp' => $item->getTime(),
            ],
        ]);
    }

    /**
     * Commits the internal transaction
     *
     * @return AdapterInterface
     */
    public function commit(): AdapterInterface
    {
        $this->client->putLogEvents([
            'logGroupName' => $this->groupName,
            'logStreamName' => $this->streamName,
            'logEvents' => $this->queue,
        ]);

        $this->queue = [];
        $this->inTransaction = false;

        return $this;
    }

    /**
     * Closes the logger
     *
     * @return bool
     */
    public function close(): bool
    {
        return true;
    }
}
