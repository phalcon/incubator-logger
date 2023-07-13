# Phalcon\Incubator\Logger

[![Discord](https://img.shields.io/discord/310910488152375297?label=Discord)](http://phalcon.link/discord)
[![Packagist Version](https://img.shields.io/packagist/v/phalcon/incubator-logger)](https://packagist.org/packages/phalcon/incubator-logger)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/phalcon/incubator-logger)](https://packagist.org/packages/phalcon/incubator-logger)
[![codecov](https://codecov.io/gh/phalcon/incubator-logger/branch/master/graph/badge.svg)](https://codecov.io/gh/phalcon/incubator-logger)
[![Packagist](https://img.shields.io/packagist/dd/phalcon/incubator-logger)](https://packagist.org/packages/phalcon/incubator-logger/stats)

Extra Adapters for Phalcon Logger component.

## Installation

```
composer require phalcon/incubator-logger
```

## Amazon CloudWatch

Adapter to store logs in AWS ClodWatch:

Note: group name and stream name must be created before call any logs.
You can make it inside AWS console. 

```php
use Aws\CloudWatchLogs\CloudWatchLogsClient;
use Phalcon\Incubator\Logger\Adapter\CloudWatch;
use Phalcon\Logger\Logger;

$di->set(
    'logger',
    function () {
        $client = new CloudWatchLogsClient([
            'credentials' => [
                'key' => 'AMAZON_CLOUDWATCH_KEY',
                'secret' => 'AMAZON_CLOUDWATCH_SECRET',
            ],
            'region' => 'AMAZON_CLOUDWATCH_REGION',
            'version' => 'latest', // Or any specific
        ]);

        $adapter = new CloudWatch($client, '/group/name', 'stream-name');

        return new Logger('messages', ['main' => $adapter]);
    }
);
```

## Database

Adapter to store logs in a database table:

```php
use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\Incubator\Logger\Adapter\Database as DbLogger;

$di->set(
    'logger',
    function () {
        $connection = new Mysql(
            [
                'host'     => 'localhost',
                'username' => 'root',
                'password' => 'secret',
                'dbname'   => 'audit',
            ]
        );

        $logsName  = 'errors';
        $tableName = 'logs';

        return new DbLogger($connection, $logsName, $tableName);
    }
);
```

The following table used to store the logs:

```sql
CREATE TABLE IF NOT EXISTS `logs` (
  `id` INT(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(32) DEFAULT NULL,
  `type` INT(3) NOT NULL,
  `content` text,
  `created_at` BIGINT unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
```

## UDP logger

Adapter to send messages by UDP protocol to external server

```php
use Phalcon\Incubator\Logger\Adapter\Udp as UdpLogger;

$di->set(
    'logger',
    function () {
        $host = '192.168.1.2';
        $port = 65000;

        return new UdpLogger('errors', $host, $port);
    }
);
```

## Slack logger

Adapter to write messages into a Slack channel

![slack-log](.assets/slack-log.png)

```php
$adapter = new \Phalcon\Incubator\Logger\Adapter\Slack('api-token','channel-name');

$logger = new \Phalcon\Logger\Logger('logger-name', ['main-slack' => $adapter]);
$logger->info('Information message to log in the channel channel-name');
```

### Creating an application in Slack API

Access this [page](https://api.slack.com/apps) to create an `application` (after choosing/creating a workspace)

### Giving rights to post messages
Go to `Features > OAuth & Permissions` and in the `Scopes` section -> `Add an oauth scope` and select the following rights :

- chat:write
- chat:write.public

### Get the token
You can now generate a token for your bot/user in the `Install App` section which looks something like this :
```
xoxp-1111111-22222222-33333333-aaaaaaaaaaaaaaaaaaaaaa
```
