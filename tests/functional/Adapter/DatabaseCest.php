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

namespace Phalcon\Incubator\Logger\Tests\Functional\Adapter;

use FunctionalTester;
use Phalcon\Db\Adapter\Pdo\Sqlite;
use Phalcon\Incubator\Logger\Adapter\Database;
use Phalcon\Logger\Logger;
use Phalcon\Logger\Adapter\AbstractAdapter;

final class DatabaseCest
{
    private $connection;

    public function __construct()
    {
        $dbFile = codecept_output_dir('test.sqlite');
        if (file_exists($dbFile)) {
            unlink($dbFile);
        }

        $this->connection = new Sqlite([
            'dbname' => $dbFile,
        ]);

        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `logs` (
  `id` INT,
  `name` VARCHAR(32) DEFAULT NULL,
  `type` INT NOT NULL,
  `content` text,
  `created_at` INT NOT NULL,
  PRIMARY KEY (`id`)
);
SQL;

        $this->connection->execute($sql);
    }

    public function construct(FunctionalTester $I): void
    {
        $class = new Database($this->connection, 'test', 'logs');

        $I->assertInstanceOf(AbstractAdapter::class, $class);
    }

    public function insertLog(FunctionalTester $I): void
    {
        $this->connection->execute('DELETE FROM logs');

        $message = 'Insert log';

        $class = new Database($this->connection, 'test', 'logs');
        $logger = new Logger('test', [
            'main' => $class,
        ]);
        $logger->info($message);

        $row = $this->connection->fetchOne('SELECT content FROM logs');

        $I->assertStringContainsString($message, $row['content']);
    }

    public function insertTransactionLogs(FunctionalTester $I): void
    {
        $this->connection->execute('DELETE FROM logs');

        $class = new Database($this->connection, 'test', 'logs');
        $logger = new Logger('test', [
            'main' => $class,
        ]);

        $logger->getAdapter('main')->begin();
        $logger->error('Error message #1');
        $logger->error('Error message #2');
        $logger->error('Error message #3');
        $logger->getAdapter('main')->commit();

        $row = $this->connection->fetchOne('SELECT COUNT(*) as total FROM logs');

        $I->assertSame(3, (int)$row['total']);
    }
}
