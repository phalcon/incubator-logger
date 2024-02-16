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
use Phalcon\Logger\Item;

/**
 * Amazon (AWS) CloudWatch Logger
 */
class CloudWatch extends AbstractAdapter
{
    /**
     * CloudWatch requires sequence token to add new logs with order.
     */
    private ?string $sequenceToken = null;

    /**
     * CloudWatch constructor.
     *
     * @param string $groupName Some kind of "folder" inside CloudWatch
     * @param string $streamName Some kind of "file" inside CloudWatch
     */
    public function __construct(
        protected CloudWatchLogsClient $client,
        protected string $groupName,
        protected string $streamName
    ) {
        $this->retrieveSequenceToken();
    }

    /**
     * Processes the message in the adapter
     */
    public function process(Item $item): void
    {
        $formatterMessage = $this->getFormatter()->format($item);

        $args = [
            'logGroupName'  => $this->groupName,
            'logStreamName' => $this->streamName,
            'logEvents'     => [
                [
                    'message'   => $formatterMessage,
                    'timestamp' => $item->getDateTime()->getTimestamp() * 1000
                ]
            ]
        ];

        if ($this->sequenceToken !== null) {
            $args['sequenceToken'] = $this->sequenceToken;
        }

        $response = $this->client->putLogEvents($args);
        $this->sequenceToken = $response->get('nextSequenceToken');
    }

    /**
     * Commits the internal transaction
     */
    public function commit(): self
    {
        $args = [
            'logGroupName'  => $this->groupName,
            'logStreamName' => $this->streamName,
            'logEvents'     => $this->queue
        ];

        if ($this->sequenceToken !== null) {
            $args['sequenceToken'] = $this->sequenceToken;
        }

        $response = $this->client->putLogEvents($args);

        $this->queue         = [];
        $this->inTransaction = false;
        $this->sequenceToken = $response->get('nextSequenceToken');

        return $this;
    }

    /**
     * Closes the logger
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
            'logGroupName'        => $this->groupName,
            'logStreamNamePrefix' => $this->streamName
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
