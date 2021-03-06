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

namespace Phalcon\Incubator\Logger\Tests\Unit\Adapter;

use Codeception\Test\Unit;
use Phalcon\Incubator\Logger\Adapter\Database;
use Phalcon\Logger\Adapter\AbstractAdapter;

final class DatabaseTest extends Unit
{
    public function testImplementation(): void
    {
        $class = $this->createMock(Database::class);

        $this->assertInstanceOf(AbstractAdapter::class, $class);
    }
}
