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

use CurlHandle;
use Phalcon\Logger\AbstractLogger;
use Phalcon\Logger\Adapter\AbstractAdapter;
use Phalcon\Logger\Item;
use Phalcon\Logger\Exception;

use function function_exists;
use function curl_init;
use function curl_setopt_array;
use function json_encode;
use function curl_exec;

/**
 * Logger adapter to log messages into a Slack channel
 *
 * Uses the Slack API to post the logged message
 *
 * @see https://api.slack.com/methods/chat.postMessage
 */
class Slack extends AbstractAdapter
{
    /**
     * Slack endpoint url to post a message
     */
    public const SLACK_URL = 'https://slack.com/api/chat.postMessage';

    protected ?CurlHandle $curl = null;

    /**
     * Slack adapter constructor managing to log content in a Slack channel
     *
     * @param string $token Required token for the API
     * @param string $channel Channel name to write the message
     *
     * @throws Exception If cURL extension is not loaded/curl_init returned false
     */
    public function __construct(
        protected string $token,
        protected string $channel
    ) {
        $this->curlInit();

        $this->token   = $token;
        $this->channel = $channel;
    }

    /**
     * Calls the Slack API to log the message into the channel
     */
    public function process(Item $item): void
    {
        // Curl instance might be closed
        $this->curlInit();

        curl_setopt_array($this->curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => [
                'token'       => $this->token,
                'channel'     => $this->channel,
                'attachments' => json_encode([[
                    'title' => 'Message | ' . $item->getLevelName(),
                    'text'  => $item->getMessage(),
                    'color' => $this->levelToColor($item->getLevel())
                ]])
            ]
        ]);

        curl_exec($this->curl);
    }

    /**
     * Destroys the cURL instance
     */
    public function close(): bool
    {
        $this->curl = null;

        return true;
    }

    /**
     * Creates the CurlHandle instance
     *
     * @throws Exception If cURL extension is not loaded/curl_init returned false
     */
    protected function curlInit(): void
    {
        // Doesn't need to create the instance if already created
        if ($this->curl) {
            return;
        }

        if (!function_exists('curl_init')) {
            throw new Exception('cURL extension is not enabled');
        } elseif (!$this->curl = curl_init(self::SLACK_URL)) {
            throw new Exception('curl_init() returned false');
        }
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
