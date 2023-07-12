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
     * CloudWatch requires sequence token to add new logs with order.
     *
     * @var string|null
     */
    private $sequenceToken = null;

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

        $this->retrieveSequenceToken();
    }

    /**
     * Processes the message in the adapter
     *
     * @param Item $item
     */
    public function process(Item $item): void
    {
        $formatterMessage = $this->getFormatter()->format($item);

        $args = [
            'logGroupName' => $this->groupName,
            'logStreamName' => $this->streamName,
            'logEvents' => [
                [
                    'message' => $formatterMessage,
                    'timestamp' => $item->getDateTime()->getTimestamp() * 1000,
                ],
            ],
        ];

        if ($this->sequenceToken !== null) {
            $args['sequenceToken'] = $this->sequenceToken;
        }

        $response = $this->client->putLogEvents($args);
        $this->sequenceToken = $response->get('nextSequenceToken');
    }

    /**
     * Commits the internal transaction
     *
     * @return AdapterInterface
     */
    public function commit(): AdapterInterface
    {
        $args = [
            'logGroupName' => $this->groupName,
            'logStreamName' => $this->streamName,
            'logEvents' => $this->queue,
        ];

        if ($this->sequenceToken !== null) {
            $args['sequenceToken'] = $this->sequenceToken;
        }

        $response = $this->client->putLogEvents($args);

        $this->queue = [];
        $this->inTransaction = false;
        $this->sequenceToken = $response->get('nextSequenceToken');

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

    /**
     * Find latest sequence token
     */
    private function retrieveSequenceToken(): void
    {
        /** @var array $streams */
        $streams = $this->client->describeLogStreams([
            'logGroupName' => $this->groupName,
            'logStreamNamePrefix' => $this->streamName,
        ])->get('logStreams');

        /**
         * Set sequence token
         */
        foreach ($streams as $stream) {
            if ($stream['logStreamName'] === $this->streamName && isset($stream['uploadSequenceToken'])) {
                $this->sequenceToken = $stream['uploadSequenceToken'];
            }
        }
    }
}
