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
use Phalcon\Incubator\Logger\Adapter\Udp;
use Phalcon\Logger\Adapter\AbstractAdapter;

final class UdpTest extends Unit
{
    public function testImplementation(): void
    {
        $class = $this->createMock(Udp::class);

        $this->assertInstanceOf(AbstractAdapter::class, $class);
    }
}
