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

use Phalcon\Incubator\Logger\Adapter\Slack;
use Phalcon\Logger\Adapter\AbstractAdapter;

class SlackTest extends \Codeception\Test\Unit
{
    public function testImplementation(): void
    {
        $class = $this->createMock(Slack::class);

        $this->assertInstanceOf(AbstractAdapter::class, $class);
    }

    public function testClose(): void
    {
        $adapter = new Slack('token', 'channel');

        $this->assertTrue($adapter->close());
    }
}
