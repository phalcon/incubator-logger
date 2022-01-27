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
use Phalcon\Logger\Adapter\AdapterInterface;
use Phalcon\Logger\Logger\Item;

/**
 * Database Logger
 *
 * Adapter to store logs in a database table
 */
class Database extends AbstractAdapter
{
    /**
     * @var DbAbstractAdapter
     */
    protected DbAbstractAdapter $db;

    /**
     * @var string
     */
    protected string $name;

    /**
     * @var string
     */
    protected string $tableName;

    /**
     * Class constructor.
     *
     * @param string $name
     * @param DbAbstractAdapter $db
     * @param string $tableName
     */
    public function __construct(DbAbstractAdapter $db, string $name, string $tableName)
    {
        $this->db = $db;
        $this->name = $name;
        $this->tableName = $tableName;
    }

    /**
     * Closes DB connection
     *
     * Do nothing, DB connection close can't be done here.
     *
     * @return bool
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * Opens DB Transaction
     *
     * @return AdapterInterface
     */
    public function begin(): AdapterInterface
    {
        $this->db->begin();

        return $this;
    }

    /**
     * Commit transaction
     *
     * @return AdapterInterface
     */
    public function commit(): AdapterInterface
    {
        $this->db->commit();

        return $this;
    }

    /**
     * Rollback transaction
     * (happens automatically if commit never reached)
     *
     * @return AdapterInterface
     */
    public function rollback(): AdapterInterface
    {
        $this->db->rollback();

        return $this;
    }

    /**
     * Writes the log into DB table
     *
     * @param Item $item
     * @return void
     */
    public function process(Item $item): void
    {
        $this->db->execute(
            'INSERT INTO ' . $this->tableName . ' VALUES (null, ?, ?, ?, ?)',
            [
                $this->name,
                $item->getType(),
                $this->getFormatter()->format($item),
                $item->getTime(),
            ],
            [
                Column::BIND_PARAM_STR,
                Column::BIND_PARAM_INT,
                Column::BIND_PARAM_STR,
                Column::BIND_PARAM_INT,
            ]
        );
    }
}
