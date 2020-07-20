# Phalcon\Incubator\Logger

Usage examples of the adapters available here:

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

        $logsName = 'errors';
        $tableName = 'logs';
        return new DbLogger($logsName, $connection, $tableName);
    }
);
```

The following table used to store the logs:

```sql
CREATE TABLE `logs` (
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
