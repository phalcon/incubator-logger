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

use Phalcon\Db\Adapter\AbstractAdapter as DbAbstractAdapter;
use Phalcon\Db\Column;
use Phalcon\Logger\Adapter\AbstractAdapter;
use Phalcon\Logger\Item;

/**
 * Database Logger
 *
 * Adapter to store logs in a database table
 */
class Database extends AbstractAdapter
{
    protected DbAbstractAdapter $db;

    protected string $name;

    protected string $tableName;

    /**
     * Database adapter constructor.
     *
     * @param DbAbstractAdapter $db
     * @param string $name
     * @param string $tableName
     */
    public function __construct(DbAbstractAdapter $db, string $name, string $tableName)
    {
        $this->db        = $db;
        $this->name      = $name;
        $this->tableName = $tableName;
    }

    /**
     * Closes DB connection
     *
     * Do nothing, DB connection close can't be done here.
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * Opens DB Transaction
     */
    public function begin(): self
    {
        $this->db->begin();

        return $this;
    }

    /**
     * Commit transaction
     */
    public function commit(): self
    {
        $this->db->commit();

        return $this;
    }

    /**
     * Rollback transaction (happens automatically if commit never reached)
     */
    public function rollback(): self
    {
        $this->db->rollback();

        return $this;
    }

    /**
     * Writes the log into DB table
     */
    public function process(Item $item): void
    {
        $this->db->execute(
            'INSERT INTO ' . $this->tableName . ' VALUES (null, ?, ?, ?, ?)',
            [
                $this->name,
                $item->getLevel(),
                $this->getFormatter()->format($item),
                $item->getDateTime()->getTimestamp()
            ],
            [
                Column::BIND_PARAM_STR,
                Column::BIND_PARAM_INT,
                Column::BIND_PARAM_STR,
                Column::BIND_PARAM_INT
            ]
        );
    }
}
