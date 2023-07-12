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

use Phalcon\Logger\AbstractLogger;
use Phalcon\Logger\Item;

/**
 * Logger adapter to log messages into a Slack channel
 *
 * Uses the Slack API to write the logged message
 *
 * @see https://api.slack.com/methods/chat.postMessage
 */
class Slack extends \Phalcon\Logger\Adapter\AbstractAdapter
{
    /**
     * @var resource
     */
    protected $curl;

    /**
     * API token
     */
    protected string $token;

    /**
     * Slack channel name
     */
    protected string $channel;

    /**
     * Slack adapter constructor magaging to log content in a Slack channel
     *
     * @param string $token Required token for the API
     * @param string $channel Channel name to write the message
     *
     * @throws \Phalcon\Logger\Exception If curl_init returned false
     */
    public function __construct(string $token, string $channel)
    {
        if (!$this->curl = curl_init('https://slack.com/api/chat.postMessage')) {
            throw new \Phalcon\Logger\Exception('curl_init() returned false');
        }

        $this->token   = $token;
        $this->channel = $channel;
    }

    /**
     * Calls the Slack API to log the message into the channel
     */
    public function process(Item $item): void
    {
        curl_setopt_array($this->curl, [
            CURLOPT_POSTFIELDS => [
                'token'   => $this->token,
                'channel' => $this->channel,
                'attachments' => json_encode([[
                    'title'     => 'Message | ' . $item->getLevelName(),
                    'text'      => $item->getMessage(),
                    'color'     => $this->levelToColor($item->getLevel())
                ]])
            ]
        ]);

        curl_exec($this->curl);
    }

    /**
     * Closes the cURL connection
     */
    public function close(): bool
    {
        curl_close($this->curl);

        return true;
    }

    /**
     * Returns the bloc color of the message according to the item level
     */
    protected function levelToColor(int $level): string
    {
        switch ($level) {
            case AbstractLogger::ALERT:
            case AbstractLogger::CRITICAL:
            case AbstractLogger::EMERGENCY:
            case AbstractLogger::ERROR:
                return 'danger';

            case AbstractLogger::WARNING:
                return 'warning';

            default:
                return '#e3e4e6';
        }
    }
}
